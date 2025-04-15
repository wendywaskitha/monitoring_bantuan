<?php

namespace App\Filament\Widgets;

use App\Models\DistribusiBantuan;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class AidValueByTypeChart extends ChartWidget
{
    protected static ?string $heading = 'Estimasi Nilai Bantuan per Jenis';
    protected static ?int $sort = 3;
    protected static string $color = 'warning';

    protected function getData(): array
    {
        $data = DistribusiBantuan::query()
            ->join('bantuans', 'distribusi_bantuans.bantuan_id', '=', 'bantuans.id')
            // ->where('distribusi_bantuans.status_pemberian', 'Diterima Kelompok') // Filter jika perlu
            ->select('bantuans.jenis_bantuan', DB::raw('sum(distribusi_bantuans.harga_awal_total) as total_value'))
            ->groupBy('bantuans.jenis_bantuan')
            ->orderBy('bantuans.jenis_bantuan') // Urutkan jenis
            ->pluck('total_value', 'jenis_bantuan') // Hasil: ['Pertanian' => 50000000, ...]
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Total Nilai Bantuan (Rp)',
                    'data' => array_values($data),
                    'backgroundColor' => '#F59E0B', // Warna warning
                    'borderColor' => '#F59E0B',
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // Tipe chart batang
    }

     /**
     * Opsi tambahan untuk chart (misal format tooltip)
     */
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'ticks' => [
                        // Format angka di sumbu Y sebagai Rupiah
                        'callback' => <<<JS
                            function(value, index, values) {
                                return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                            }
                        JS,
                    ],
                ],
            ],
            'plugins' => [
                'tooltip' => [
                    'callbacks' => [
                        // Format angka di tooltip sebagai Rupiah
                        'label' => <<<JS
                            function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                                }
                                return label;
                            }
                        JS,
                    ],
                ],
            ],
        ];
    }
}
