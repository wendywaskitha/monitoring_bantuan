<?php

namespace App\Filament\Widgets;

use App\Models\DistribusiBantuan;
use App\Models\Kelompok;
use App\Models\PelaporanHasil;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat; // Ganti Card menjadi Stat
use Illuminate\Support\Number; // Helper untuk format angka

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1; // Urutan widget di dashboard

    protected function getStats(): array // Ganti getCards menjadi getStats
    {
        // Hitung total kelompok
        $totalKelompok = Kelompok::count();

        // Hitung total nilai estimasi bantuan yang didistribusikan
        // Hanya hitung yang statusnya 'Diterima Kelompok' ? Sesuaikan jika perlu
        $totalNilaiDistribusi = DistribusiBantuan::where('status_pemberian', 'Diterima Kelompok')->sum('harga_awal_total');

        // Hitung total hasil penjualan yang dilaporkan
        $totalHasilPenjualan = PelaporanHasil::sum('total_penjualan');

        return [
            Stat::make('Total Kelompok', $totalKelompok)
                ->description('Jumlah kelompok penerima terdaftar')
                ->descriptionIcon('heroicon-m-user-group') // Gunakan ikon solid (m) atau outline (o)
                ->color('primary'),

            Stat::make('Total Nilai Bantuan Disalurkan', 'Rp ' . Number::format($totalNilaiDistribusi, precision: 0, locale: 'id'))
                ->description('Estimasi total nilai bantuan yang diterima kelompok')
                ->descriptionIcon('heroicon-m-gift')
                ->color('warning'),

            Stat::make('Total Hasil Penjualan Dilaporkan', 'Rp ' . Number::format($totalHasilPenjualan, precision: 0, locale: 'id'))
                ->description('Total penjualan dari hasil bantuan yang dilaporkan')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
        ];
    }

    /**
     * Cek apakah widget bisa ditampilkan
     * Contoh: Hanya tampilkan jika user adalah Admin
     */
    // public static function canView(): bool
    // {
    //     return auth()->user()->hasRole('Admin');
    // }
}
