<?php

// Pastikan namespace benar sesuai hasil generate
namespace App\Filament\Resources\PesanKontakResource\Widgets;

use App\Models\PesanKontak; // Import model
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PesanKontakStatsOverview extends BaseWidget
{
    // Widget ini tidak perlu polling data secara periodik
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        // Hitung jumlah pesan belum dibaca
        $belumDibacaCount = PesanKontak::where('sudah_dibaca', false)->count();

        // Hitung jumlah pesan sudah dibaca
        $sudahDibacaCount = PesanKontak::where('sudah_dibaca', true)->count();

        // Hitung total pesan
        $totalPesanCount = $belumDibacaCount + $sudahDibacaCount; // Lebih efisien daripada query ulang

        return [
            Stat::make('Pesan Baru (Belum Dibaca)', $belumDibacaCount)
                ->description('Jumlah pesan yang memerlukan perhatian')
                ->descriptionIcon('heroicon-m-envelope') // Ikon amplop solid
                ->color('warning'), // Warna warning untuk pesan baru

            Stat::make('Pesan Sudah Dibaca', $sudahDibacaCount)
                ->description('Jumlah pesan yang sudah ditinjau')
                ->descriptionIcon('heroicon-m-envelope-open') // Ikon amplop terbuka
                ->color('success'), // Warna success untuk yang sudah dibaca

            Stat::make('Total Pesan Masuk', $totalPesanCount)
                ->description('Jumlah keseluruhan pesan kontak')
                ->descriptionIcon('heroicon-m-archive-box') // Ikon arsip
                ->color('gray'), // Warna netral
        ];
    }

    /**
     * Kontrol akses widget (opsional, sesuaikan dengan resource)
     * Biasanya mengikuti akses resource induknya.
     */
    // public static function canView(): bool
    // {
    //     return auth()->user()?->can('view_any_pesan::kontak') ?? false;
    // }
}
