<?php

// Pastikan namespace benar
namespace App\Filament\Resources\PendaftaranKelompokResource\Widgets;

use App\Models\PendaftaranKelompok;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder; // Import Builder
use App\Filament\Resources\PendaftaranKelompokResource; // Import Resource Induk

class PendaftaranKelompokStatsOverview extends BaseWidget
{
    // Agar widget ini merespon filter tabel di halaman List (opsional)
    // protected static bool $isLazy = false; // Load data saat filter berubah
    // protected function getFilters(): array
    // {
    //     return $this->filterData; // Ambil filter dari property jika halaman list mengirimnya
    // }
    // public ?array $filterData = []; // Properti untuk menerima filter

    protected static ?string $pollingInterval = null;

    // --- Atur jumlah kolom widget ---
    protected function getColumns(): int
    {
        return 4; // Atau 3, atau 2 sesuai keinginan
    }
    // -------------------------------

    protected function getStats(): array
    {

        // Ambil query dasar dari resource induk untuk konsistensi filter peran
        // (Meskipun resource ini mungkin hanya untuk Admin)
        $baseQuery = PendaftaranKelompokResource::getEloquentQuery();

        // Hitung jumlah per status
        $baruCount = (clone $baseQuery)->where('status', 'Baru')->count();
        $diprosesCount = (clone $baseQuery)->where('status', 'Diproses')->count();
        $diterimaCount = (clone $baseQuery)->where('status', 'Diterima')->count();
        $ditolakCount = (clone $baseQuery)->where('status', 'Ditolak')->count();
        $totalCount = (clone $baseQuery)->count(); // Hitung total dari query dasar

        return [
            Stat::make('Baru', $baruCount) // Label lebih singkat
                ->description('Menunggu verifikasi')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make('Diproses', $diprosesCount)
                ->description('Dalam tinjauan')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),
            Stat::make('Diterima', $diterimaCount)
                ->description('Kelompok dibuat')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Ditolak', $ditolakCount)
                ->description('Tidak memenuhi syarat')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            // Stat Total bisa ditambahkan jika perlu
            Stat::make('Total Pendaftaran', $totalCount)
                ->description('Jumlah seluruh pendaftaran')
                ->descriptionIcon('heroicon-m-list-bullet')
                ->color('gray'),
        ];
    }

     /**
     * Kontrol akses widget (mengikuti resource induk)
     */
    public static function canView(): bool
    {
        return PendaftaranKelompokResource::canViewAny();
    }
}
