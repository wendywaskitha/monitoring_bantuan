<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\KelompokResource;
use App\Models\Kelompok;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth; // Import Auth

class GroupSalesPerformanceWidget extends BaseWidget
{
    protected static ?int $sort = 5;
    // protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Kinerja Penjualan Kelompok (Top 10)';

    // Properti untuk menyimpan data kelompok yang akan ditampilkan
    // Tidak perlu filter interaktif di widget ini untuk awal
    // public ?string $filterJenis = null;

    public function table(Table $table): Table
    {
        $user = Auth::user(); // Dapatkan user saat ini

        return $table
            ->query(function (Kelompok $model) use ($user): Builder {
                // Query dasar untuk kelompok
                $query = $model->query()
                    ->select('kelompoks.*') // Pilih semua kolom kelompok
                    // Hitung total penjualan dari relasi HasManyThrough allPelaporanHasils
                    // Pastikan relasi allPelaporanHasils() ada di model Kelompok
                    ->withSum('allPelaporanHasils as total_penjualan_agg', 'total_penjualan');

                // --- PEMBATASAN UNTUK PETUGAS LAPANGAN ---
                if ($user && !$user->hasRole('Admin')) {
                    // Hanya tampilkan kelompok yang ditangani oleh petugas ini
                    $query->where('user_id', $user->id);
                }
                // -----------------------------------------

                // Urutkan berdasarkan total penjualan (descending) dan batasi
                return $query->orderByDesc('total_penjualan_agg')
                             ->limit(10);
            })
            ->columns([
                TextColumn::make('nama_kelompok')
                    ->label('Nama Kelompok')
                    ->searchable() // Search di hasil top 10
                    ->sortable(), // Sortable di hasil top 10
                TextColumn::make('desa.nama_desa')
                    ->label('Desa')
                    ->sortable(),
                // Tampilkan Petugas PJ (hanya untuk Admin)
                TextColumn::make('petugas.name')
                    ->label('Petugas PJ')
                    ->placeholder('-')
                    ->visible(fn (): bool => $user?->hasRole('Admin') ?? false),
                TextColumn::make('total_penjualan_agg') // Gunakan kolom agregat
                    ->label('Total Penjualan Dilaporkan')
                    ->money('IDR', 0)
                    ->sortable(),
                // Kolom total bantuan sebagai perbandingan
                TextColumn::make('total_nilai_bantuan_diterima') // Gunakan accessor
                    ->label('Total Bantuan Diterima')
                    ->money('IDR', 0)
                    ->sortable(),
            ])
            ->recordUrl( // Link ke detail kelompok
                fn (Kelompok $record): string => KelompokResource::getUrl('view', ['record' => $record]),
            )
            ->paginated(false) // Tidak perlu pagination untuk top list
            ->heading(function () use ($user) { // Judul dinamis
                if ($user && !$user->hasRole('Admin')) {
                    return 'Kinerja Penjualan Kelompok Anda (Top 10)';
                }
                return 'Kinerja Penjualan Kelompok (Top 10)';
            });
    }

     /**
     * Widget ini hanya tampil jika user adalah Admin atau Petugas Lapangan
     * (Asumsi peran lain tidak perlu melihat ini)
     */
    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->hasAnyRole(['Admin', 'Petugas Lapangan']);
    }
}
