<?php

namespace App\Filament\Resources\KelompokResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;
use App\Models\EvaluasiKelompok;
use Illuminate\Support\Facades\Log;
use Filament\Tables\Actions\Action; // Import Action
use Filament\Notifications\Notification; // Import Notifikasi
use Illuminate\Support\Facades\Auth; // Import Auth

class EvaluasiKelompoksRelationManager extends RelationManager
{
    protected static string $relationship = 'evaluasiKelompoks';
    protected static ?string $recordTitleAttribute = 'id'; // Tetap pakai ID
    protected static ?string $modelLabel = 'Evaluasi Perkembangan';
    protected static ?string $pluralModelLabel = 'Riwayat Evaluasi Perkembangan';

    // --- HAPUS METHOD form() KARENA FORM DIDEFINISIKAN DI ACTION ---
    // public function form(Form $form): Form
    // {
    //     // ...
    // }

    // Fungsi helper cleanMoneyValue tetap diperlukan
    private static function cleanNumericValue(?string $state): ?string
    {
        if ($state === null || trim($state) === '') { return null; }
        $cleaned = preg_replace('/[^\d]/', '', $state);
        return is_numeric($cleaned) ? $cleaned : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                // Kolom tabel tetap sama
                TextColumn::make('id')->label('ID Eval')->sortable()->searchable(),
                TextColumn::make('tanggal_evaluasi')->date('d M Y')->sortable(),
                TextColumn::make('periode_omset')->placeholder('-')->searchable(),
                TextColumn::make('nilai_aset')->label('Nilai Aset')->money('IDR', 0)->sortable(),
                TextColumn::make('omset_periode')->label('Omset Periode')->money('IDR', 0)->sortable(),
                TextColumn::make('catatan_evaluasi')->label('Catatan')->limit(40)->tooltip(fn ($state): ?string => $state)->wrap(),
                TextColumn::make('user.name')->label('Petugas')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Dicatat')->dateTime('d M Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filters
            ])
            ->headerActions([
                // --- GANTI CreateAction DENGAN ACTION MODAL KUSTOM ---
                Action::make('createEvaluasi')
                    ->label('Tambah Evaluasi')
                    ->icon('heroicon-o-plus')
                    ->form(fn (Form $form) => $this->getEvaluasiFormSchema($form)) // Panggil schema form
                    ->action(function (array $data) {
                        try {
                            // Bersihkan nilai numerik
                            $data['nilai_aset'] = self::cleanNumericValue($data['nilai_aset']);
                            $data['omset_periode'] = self::cleanNumericValue($data['omset_periode']);
                            $data['user_id'] = Auth::id();

                            // Buat record baru melalui relasi
                            $this->getOwnerRecord()->{$this->getRelationshipName()}()->create($data);

                            Notification::make()->title('Evaluasi berhasil ditambahkan')->success()->send();
                        } catch (\Exception $e) {
                            Log::error("Gagal tambah evaluasi: " . $e->getMessage());
                            Notification::make()->title('Gagal menambahkan evaluasi')->body($e->getMessage())->danger()->send();
                        }
                    })
                    ->modalWidth('2xl'), // Atur lebar modal
            ])
            ->actions([
                // --- GANTI EditAction DENGAN ACTION MODAL KUSTOM ---
                Action::make('editEvaluasi')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->form(fn (Form $form) => $this->getEvaluasiFormSchema($form)) // Panggil schema form yang sama
                    ->mountUsing(fn (Forms\ComponentContainer $form, EvaluasiKelompok $record) => $form->fill(
                        // Format data sebelum mengisi form
                        $this->formatDataForForm($record->toArray())
                    ))
                    ->action(function (EvaluasiKelompok $record, array $data) {
                        try {
                            // Bersihkan nilai numerik
                            $data['nilai_aset'] = self::cleanNumericValue($data['nilai_aset']);
                            $data['omset_periode'] = self::cleanNumericValue($data['omset_periode']);
                            // user_id tidak perlu diupdate saat edit, atau bisa diupdate jika perlu
                            // unset($data['user_id']);

                            $record->update($data);
                            Notification::make()->title('Evaluasi berhasil diperbarui')->success()->send();
                        } catch (\Exception $e) {
                            Log::error("Gagal update evaluasi ID {$record->id}: " . $e->getMessage());
                            Notification::make()->title('Gagal memperbarui evaluasi')->body($e->getMessage())->danger()->send();
                        }
                    })
                    ->modalWidth('2xl'), // Atur lebar modal

                // DeleteAction tetap sama
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->can('delete_evaluasi::kelompok')),
            ])
            ->bulkActions([
                 Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('delete_evaluasi::kelompok')),
                ]),
            ])
            ->striped()
            ->defaultSort('tanggal_evaluasi', 'desc');
    }

    /**
     * Mendefinisikan skema form untuk create/edit evaluasi.
     * Dipanggil oleh kedua Action Modal.
     */
    protected function getEvaluasiFormSchema(Form $form): Form
    {
         return $form
            ->schema([
                // kelompok_id tidak perlu karena sudah dalam konteks relasi
                DatePicker::make('tanggal_evaluasi')
                    ->label('Tanggal Evaluasi')
                    ->required()
                    ->native(false)
                    ->default(now()),
                TextInput::make('periode_omset')
                    ->label('Periode Omset')
                    ->helperText('Contoh: Januari 2024, Kuartal 1 2024, Tahun 2023')
                    ->nullable()
                    ->maxLength(100),
                TextInput::make('nilai_aset')
                    ->label('Estimasi Nilai Aset')
                    ->prefix('Rp')
                    ->numeric()
                    ->nullable()
                    ->minValue(0)
                    // Tidak perlu format/dehydrate di sini, ditangani saat mount/action
                    ->helperText('Estimasi total nilai aset...'),
                TextInput::make('omset_periode')
                    ->label('Omset Selama Periode')
                    ->prefix('Rp')
                    ->numeric()
                    ->nullable()
                    ->minValue(0)
                    // Tidak perlu format/dehydrate di sini
                    ->helperText('Total penjualan kotor selama periode.'),
                Textarea::make('catatan_evaluasi')
                    ->label('Catatan/Evaluasi Petugas')
                    ->rows(4)
                    ->columnSpanFull()
                    ->helperText('Deskripsikan perkembangan, kendala, atau rekomendasi.'),
            ])->columns(2);
    }

     /**
     * Memformat data dari model sebelum di-fill ke form edit.
     */
    protected function formatDataForForm(array $data): array
    {
        // Format nilai numerik ke string IDR untuk ditampilkan di form
        if (isset($data['nilai_aset']) && is_numeric($data['nilai_aset'])) {
            $data['nilai_aset'] = number_format((float)$data['nilai_aset'], 0, ',', '.');
        }
        if (isset($data['omset_periode']) && is_numeric($data['omset_periode'])) {
            $data['omset_periode'] = number_format((float)$data['omset_periode'], 0, ',', '.');
        }
        // Format tanggal jika perlu (meskipun DatePicker biasanya menangani ini)
        // if (isset($data['tanggal_evaluasi'])) {
        //     $data['tanggal_evaluasi'] = \Carbon\Carbon::parse($data['tanggal_evaluasi'])->format('Y-m-d');
        // }
        return $data;
    }


    // --- HAPUS getTableRecordKey() KARENA TIDAK DIPERLUKAN LAGI OLEH ACTION KUSTOM ---
    // public function getTableRecordKey(Model $record): string
    // {
    //     // ...
    // }

}
