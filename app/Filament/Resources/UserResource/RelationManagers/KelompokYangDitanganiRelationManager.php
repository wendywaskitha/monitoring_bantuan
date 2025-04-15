<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Actions\DetachAction;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Kelompok; // Import Kelompok
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\AssociateAction; // Import AssociateAction
use Filament\Tables\Actions\DissociateAction; // Import DissociateAction
use Filament\Tables\Actions\DissociateBulkAction; // Import DissociateBulkAction

class KelompokYangDitanganiRelationManager extends RelationManager
{
    // Nama relasi dari model User ke Kelompok
    protected static string $relationship = 'kelompokYangDitangani';

    protected static ?string $recordTitleAttribute = 'nama_kelompok';
    protected static ?string $modelLabel = 'Kelompok Ditangani';
    protected static ?string $pluralModelLabel = 'Kelompok yang Ditangani';

    // --- HAPUS METHOD form() KARENA KITA TIDAK CREATE KELOMPOK DARI SINI ---
    // public function form(Form $form): Form
    // {
    //     return $form->schema([ /* ... */ ]);
    // }

    // Helper untuk schema form (digunakan di action tambah)
    protected function getAssociateFormSchema(): array
    {
        return [
             Select::make('kelompok_id_to_associate') // Nama field unik untuk form action
                ->label('Kelompok')
                ->options(function () {
                    // Ambil kelompok yang belum ditugaskan
                    return Kelompok::whereNull('user_id')
                                 ->with('desa')
                                 ->get()
                                 ->mapWithKeys(function (Kelompok $kelompok) {
                                     $label = $kelompok->nama_kelompok . ' (Desa: ' . ($kelompok->desa?->nama_desa ?? 'N/A') . ')';
                                     return [$kelompok->id => $label];
                                 });
                })
                ->searchable()
                ->preload(false) // Jangan preload
                ->getSearchResultsUsing(function(string $search): array {
                     return Kelompok::whereNull('user_id')
                        ->where(function ($query) use ($search) {
                            $query->where('nama_kelompok', 'like', "%{$search}%")
                                  ->orWhereHas('desa', fn($q) => $q->where('nama_desa', 'like', "%{$search}%"));
                        })
                        ->with('desa')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(function (Kelompok $kelompok) {
                            $label = $kelompok->nama_kelompok . ' (Desa: ' . ($kelompok->desa?->nama_desa ?? 'N/A') . ')';
                            return [$kelompok->id => $label];
                        })->toArray();
                })
                ->placeholder('Cari dan pilih kelompok...')
                ->required(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            // ->recordTitleAttribute('nama_kelompok') // Bisa diaktifkan
            ->columns([
                TextColumn::make('id')->label('ID Kelompok')->sortable(),
                TextColumn::make('nama_kelompok')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('desa.nama_desa') // Tampilkan desa
                    ->label('Desa/Kelurahan')
                    ->searchable() // Pencarian desa mungkin perlu whereHas jika ingin lebih akurat
                    ->sortable(),
                TextColumn::make('desa.kecamatan.nama_kecamatan') // Tampilkan kecamatan
                    ->label('Kecamatan')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                BadgeColumn::make('jenis_kelompok') // Tampilkan jenis
                    ->label('Jenis')
                    ->colors([
                        'success' => 'Pertanian',
                        'warning' => 'Peternakan',
                        'info' => 'Perikanan',
                        'default' => 'gray',
                    ])

                    ->sortable(),
            ])

            ->filters([
                // Filter mungkin tidak terlalu perlu di sini
            ])
            ->headerActions([
                // --- Gunakan Action Kustom untuk Menambah Penugasan ---
                Action::make('associateKelompok')
                    ->label('Tugaskan Kelompok Baru')
                    ->icon('heroicon-o-plus')
                    ->form($this->getAssociateFormSchema()) // Panggil schema form
                    ->action(function (array $data) {
                        try {
                            $kelompokId = $data['kelompok_id_to_associate'];
                            /** @var User $petugas */
                            $petugas = $this->getOwnerRecord(); // Dapatkan user (petugas) saat ini

                            Kelompok::where('id', $kelompokId)
                                    // Pastikan lagi belum ditugaskan (safety check)
                                    ->whereNull('user_id')
                                    ->update(['user_id' => $petugas->id]);

                            Notification::make()->title('Kelompok berhasil ditugaskan')->success()->send();
                        } catch (\Exception $e) {
                            Log::error("Gagal associate kelompok: " . $e->getMessage());
                            Notification::make()->title('Gagal menugaskan kelompok')->body($e->getMessage())->danger()->send();
                        }
                    })
                    ->modalWidth('lg'),
            ])
            ->actions([
                // --- Gunakan Action Kustom untuk Hapus Penugasan ---
                Action::make('hapusPenugasan')
                    ->label('Hapus Penugasan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Penugasan Petugas?')
                    ->action(function (Kelompok $record) { // Terima model Kelompok
                        try {
                            $record->update(['user_id' => null]);
                            Notification::make()->title('Penugasan Dihapus')->success()->send();
                        } catch (\Exception $e) {
                            Log::error("Gagal hapus penugasan: " . $e->getMessage());
                            Notification::make()->title('Gagal Menghapus Penugasan')->body($e->getMessage())->danger()->send();
                        }
                    }),
                // Anda bisa tambahkan ViewAction jika ingin link ke detail kelompok
                Tables\Actions\ViewAction::make()->url(fn (Kelompok $record): string => \App\Filament\Resources\KelompokResource::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // --- Gunakan BulkAction Kustom untuk Hapus Penugasan ---
                    BulkAction::make('hapusPenugasanMassal')
                        ->label('Hapus Penugasan Terpilih')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            try {
                                $recordIds = $records->pluck('id');
                                Kelompok::whereIn('id', $recordIds)->update(['user_id' => null]);
                                Notification::make()->title('Penugasan Dihapus')->body($records->count() . ' penugasan berhasil dihapus.')->success()->send();
                            } catch (\Exception $e) {
                                Log::error("Gagal hapus penugasan massal: " . $e->getMessage());
                                Notification::make()->title('Gagal Menghapus Penugasan')->body($e->getMessage())->danger()->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->striped();
    }

    /**
     * Batasi agar Relation Manager ini hanya muncul di halaman User
     * yang memiliki role 'Petugas Lapangan' dan hanya bisa dilihat oleh 'Admin'.
     * (Atau sesuaikan logikanya)
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        // $ownerRecord adalah instance User yang sedang dilihat
        // Hanya tampilkan jika user yang dilihat adalah Petugas Lapangan
        // dan user yang sedang login adalah Admin
        return $ownerRecord->hasRole('Petugas Lapangan') && auth()->user()->hasRole('Admin');
    }
}
