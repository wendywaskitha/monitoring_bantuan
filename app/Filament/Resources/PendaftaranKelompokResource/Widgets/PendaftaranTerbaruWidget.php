<?php

// Pastikan namespace benar
namespace App\Filament\Resources\PendaftaranKelompokResource\Widgets;

use App\Filament\Resources\PendaftaranKelompokResource; // Import resource
use App\Models\PendaftaranKelompok;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth; // Import Auth

class PendaftaranTerbaruWidget extends BaseWidget
{
    protected static ?int $sort = -2; // Tampilkan di dekat bagian atas dashboard
    protected int | string | array $columnSpan = 'full'; // Ambil lebar penuh
    protected static ?string $heading = 'Pendaftaran Kelompok Terbaru (Status: Baru)';

    // Jumlah baris yang ditampilkan
    protected static ?int $defaultPaginationPageOption = 5;
    protected static ?string $paginationPageOptionsPlacement = 'bottom'; // Posisi opsi per page

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Query hanya pendaftaran dengan status 'Baru'
                PendaftaranKelompokResource::getEloquentQuery() // Gunakan query dasar dari resource (jika ada pembatasan peran)
                    ->where('status', 'Baru')
                    ->latest('created_at') // Urutkan berdasarkan terbaru
            )
            ->columns([
                TextColumn::make('nama_kelompok_diajukan')
                    ->label('Nama Kelompok')
                    ->searchable()
                    ->sortable()
                    ->description(fn(PendaftaranKelompok $r) => "Kontak: " . $r->nama_kontak),
                TextColumn::make('jenis_kelompok')
                    ->badge() // Tampilkan sebagai badge
                    ->colors([
                        'success' => 'Pertanian',
                        'warning' => 'Peternakan',
                        'info' => 'Perikanan',
                        // default => 'gray',
                    ]),
                TextColumn::make('desa.nama_desa')
                    ->label('Lokasi')
                    ->sortable()
                    ->description(fn(PendaftaranKelompok $r) => "Kec. " . $r->kecamatan?->nama_kecamatan),
                TextColumn::make('created_at')
                    ->label('Tgl Daftar')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                // BadgeColumn::make('status') // Status pasti 'Baru', tidak perlu ditampilkan lagi?
                //     ->colors([ 'warning' => 'Baru' ]),
            ])
            // Aksi untuk langsung melihat detail pendaftaran
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->iconButton() // Buat jadi tombol ikon
                    ->tooltip('Lihat Detail Pendaftaran')
                    // Gunakan URL dari resource induk
                    ->url(fn (PendaftaranKelompok $record): string => PendaftaranKelompokResource::getUrl('view', ['record' => $record])),
            ])
            // Tidak perlu header actions, bulk actions, atau filter di widget ini
            ->headerActions([])
            ->bulkActions([])
            ->filters([])
            ->paginated([5, 10, 25]) // Opsi jumlah item per halaman
            ->defaultPaginationPageOption(5); // Default 5 item
    }

     /**
     * Widget ini hanya tampil jika user adalah Admin (atau peran yg boleh verifikasi)
     */
    public static function canView(): bool
    {
        // Sesuaikan dengan permission atau role yang benar
        // return auth()->user()?->can('view_any_pendaftaran::kelompok') ?? false;
        return auth()->user()?->hasRole('Admin') ?? false;
    }
}
