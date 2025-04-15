<?php

namespace App\Filament\Resources\KelompokResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use App\Models\Anggota; // Import Anggota
use Illuminate\Database\Eloquent\Builder;
use App\Models\Kelompok; // Import Kelompok
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\Action; // Untuk action custom
use Filament\Tables\Columns\IconColumn; // Untuk ikon ketua
use Filament\Notifications\Notification; // Untuk notifikasi
use Illuminate\Support\Facades\DB; // Untuk transaction jika perlu

class AnggotasRelationManager extends RelationManager
{
    protected static string $relationship = 'anggotas';

    protected static ?string $recordTitleAttribute = 'nama_anggota';
    protected static ?string $modelLabel = 'Anggota';
    protected static ?string $pluralModelLabel = 'Anggota Kelompok';


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nama_anggota')
                    ->required()
                    ->maxLength(255),
                TextInput::make('nik')
                    ->label('NIK')
                    ->unique(ignoreRecord: true) // NIK harus unik global
                    ->numeric()
                    ->length(16) // NIK biasanya 16 digit
                    ->nullable(),
                TextInput::make('nomor_telepon')
                    ->label('Nomor Telepon')
                    ->tel()
                    ->nullable(),
                Forms\Components\Textarea::make('alamat')
                    ->rows(3)
                    ->columnSpanFull(),
                // is_ketua tidak diatur langsung di form ini
            ]);
    }

    public function table(Table $table): Table
    {
        /** @var Kelompok $kelompok */
        $kelompok = $this->getOwnerRecord(); // Dapatkan record Kelompok induk

        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('nama_anggota')
                    ->searchable()
                    ->sortable(),
                // Kolom untuk menandakan Ketua
                IconColumn::make('is_ketua') // Langsung gunakan kolom is_ketua
                    ->label('Ketua?')
                    ->boolean()
                    // ->getStateUsing(...) // Tidak perlu lagi getStateUsing
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                TextColumn::make('nik')->label('NIK')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nomor_telepon')->label('Telepon')->searchable(),
                TextColumn::make('alamat')->toggleable(isToggledHiddenByDefault: true)->wrap(), // Wrap text alamat panjang
            ])
            ->filters([
                // Filter anggota? Mungkin tidak terlalu perlu di sini
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Pastikan is_ketua tidak terkirim dari form ini
                        unset($data['is_ketua']);
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                 ->mutateFormDataUsing(function (array $data): array {
                        unset($data['is_ketua']);
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, Anggota $record) {
                        /** @var Kelompok $kelompok */
                        $kelompok = $this->getOwnerRecord();
                        // Gunakan flag is_ketua untuk cek
                        if ($record->is_ketua) {
                            // Jika yang dihapus adalah ketua, null-kan ketua_id di kelompok
                            $kelompok->update(['ketua_id' => null]);
                            Notification::make()
                                ->warning()
                                ->title('Ketua Kelompok Dihapus')
                                ->body('Ketua kelompok telah dihapus. Silakan tentukan ketua baru.')
                                ->send();
                        }
                    }),
                // Aksi Custom untuk Menjadikan Ketua
                Action::make('jadikanKetua')
                ->label('Jadikan Ketua')
                ->icon('heroicon-s-star')
                ->color('warning')
                ->visible(fn (Anggota $record): bool => !$record->is_ketua) // Hanya tampil jika bukan ketua
                ->requiresConfirmation()
                ->modalHeading('Jadikan Ketua Kelompok?')
                ->modalDescription(fn(Anggota $record) => "Apakah Anda yakin ingin menjadikan {$record->nama_anggota} sebagai ketua kelompok {$kelompok->nama_kelompok}?")
                ->action(function (Anggota $newKetua) use ($kelompok) {
                    // Cari ketua lama berdasarkan flag is_ketua = true di kelompok ini
                    // Kita perlu memastikan hanya ada satu ketua lama (atau tidak ada sama sekali)
                    $ketuaLamaQuery = $kelompok->anggotas()->where('is_ketua', true);
                    $oldKetua = $ketuaLamaQuery->first();
                    $jumlahKetuaLama = $ketuaLamaQuery->count(); // Hitung jumlah ketua saat ini

                    // Validasi awal (sebelum transaksi)
                    if ($newKetua->is_ketua) {
                        Notification::make()->warning()->title('Anggota ini sudah menjadi ketua.')->send();
                        return;
                    }
                    if ($jumlahKetuaLama > 1) {
                        // Ini kondisi data tidak konsisten, seharusnya tidak terjadi jika logika sebelumnya benar
                        Notification::make()->danger()->title('Error Data Tidak Konsisten')
                            ->body('Ditemukan lebih dari satu ketua aktif di kelompok ini. Harap perbaiki data secara manual.')
                            ->send();
                        Log::error("Inkonsistensi data: Kelompok ID {$kelompok->id} memiliki {$jumlahKetuaLama} ketua aktif.");
                        return;
                    }

                    try {
                        DB::transaction(function () use ($newKetua, $oldKetua, $kelompok) {
                            // Langkah 1: Set ketua LAMA (jika ada) menjadi is_ketua = false
                            // Ini mengosongkan slot 'true'
                            if ($oldKetua) {
                                // Pastikan kita tidak mencoba update anggota yang sama jika $oldKetua == $newKetua (seharusnya tidak terjadi krn validasi awal)
                                if ($oldKetua->id !== $newKetua->id) {
                                    $oldKetua->update(['is_ketua' => false]);
                                } else {
                                    // Jika ketua lama dan baru sama (seharusnya tidak mungkin), throw error
                                    throw new \Exception("Ketua lama dan baru tidak boleh sama.");
                                }
                            }

                            // Langkah 2: Set anggota BARU menjadi is_ketua = true
                            // Ini mengisi slot 'true' dan mengosongkan slot 'false' yang dipegang newKetua
                            $newKetua->update(['is_ketua' => true]);

                            // Langkah 3: Update foreign key ketua_id di tabel Kelompok
                            $kelompok->update(['ketua_id' => $newKetua->id]);

                        }, 2); // Angka 2 adalah jumlah percobaan ulang jika terjadi deadlock (opsional)

                        Notification::make()
                            ->success()
                            ->title('Ketua Kelompok Diperbarui')
                            ->body("{$newKetua->nama_anggota} berhasil ditetapkan sebagai ketua.")
                            ->send();

                    } catch (\Exception $e) {
                        // Rollback otomatis oleh transaction, kirim notifikasi error
                        Notification::make()
                            ->danger()
                            ->title('Gagal Menetapkan Ketua')
                            ->body(config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan pada database.')
                            ->send();
                        // Log error untuk diagnosis lebih lanjut
                        Log::error("Gagal menetapkan ketua untuk Kelompok ID {$kelompok->id}: " . $e->getMessage());
                    }
                }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                     ->visible(fn () => auth()->user()->can('delete_any_anggota')), // Sesuaikan permission jika perlu
                ]),
            ])
            ->striped();
    }
}
