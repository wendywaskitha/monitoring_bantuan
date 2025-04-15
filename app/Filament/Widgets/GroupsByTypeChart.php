<?php

namespace App\Filament\Widgets;

use App\Models\Kelompok;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB; // Import DB

class GroupsByTypeChart extends ChartWidget
{
    protected static ?string $heading = 'Jumlah Kelompok Berdasarkan Jenis';
    protected static ?int $sort = 2; // Urutan widget
    protected static string $color = 'info'; // Warna tema chart

    protected function getData(): array
    {
        $data = Kelompok::query()
            ->select('jenis_kelompok', DB::raw('count(*) as count'))
            ->groupBy('jenis_kelompok')
            ->pluck('count', 'jenis_kelompok') // Hasil: ['Pertanian' => 10, 'Peternakan' => 5, ...]
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Kelompok',
                    'data' => array_values($data), // Ambil nilai count [10, 5, ...]
                    // Atur warna per slice jika mau
                    'backgroundColor' => [
                        '#34D399', // success (Pertanian)
                        '#F59E0B', // warning (Peternakan)
                        '#3B82F6', // info (Perikanan)
                    ],
                    'borderColor' => '#ffffff',
                ],
            ],
            'labels' => array_keys($data), // Ambil nama jenis ['Pertanian', 'Peternakan', ...]
        ];
    }

    protected function getType(): string
    {
        return 'pie'; // Tipe chart: pie, doughnut, bar, line, etc.
    }
}
