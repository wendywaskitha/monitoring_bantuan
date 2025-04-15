<?php

namespace App\Filament\Widgets;

use App\Models\PelaporanHasil;         // Import model PelaporanHasil
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;          // Import Carbon untuk manipulasi tanggal
use Illuminate\Support\Facades\DB;      // Import DB facade
use Illuminate\Support\Facades\Auth;    // Import Auth untuk pembatasan
use Filament\Forms\Components\Select;   // Import Select untuk filter
use Illuminate\Contracts\Support\Htmlable; // Import Htmlable untuk return type getHeading

class MonthlySalesChart extends ChartWidget
{
    // Properti statis untuk konfigurasi dasar widget
    // protected static ?string $heading = 'Tren Penjualan Bulanan'; // Judul default (opsional, akan dioverride getHeading)
    protected static ?int $sort = 7; // Urutan widget di dashboard
    protected static string $color = 'success'; // Warna tema chart

    // Properti publik untuk state filter
    public ?int $filterYear = null;

    /**
     * Inisialisasi state filter saat komponen pertama kali dimuat.
     */
    public function mount(): void
    {
        // Default ke tahun saat ini
        $this->filterYear = (int) now()->format('Y');
    }

    /**
     * Mendefinisikan skema form untuk filter di atas chart.
     */
    protected function getFilterFormSchema(): array
    {
        // Buat daftar tahun dari data pelaporan yang ada atau rentang tertentu
        $availableYears = PelaporanHasil::selectRaw('YEAR(tanggal_pelaporan) as year')
                            ->distinct()
                            ->orderBy('year', 'desc')
                            ->pluck('year')
                            ->mapWithKeys(fn ($year) => [$year => $year])
                            ->toArray();

        // Jika tidak ada data, setidaknya tampilkan tahun saat ini dan beberapa tahun sebelumnya
        if (empty($availableYears)) {
            $currentYear = now()->year;
            for ($i = 0; $i < 3; $i++) { // Tampilkan 3 tahun terakhir
                $year = $currentYear - $i;
                $availableYears[$year] = $year;
            }
            krsort($availableYears); // Urutkan dari terbaru
        }

        return [
            Select::make('filterYear')
                ->label('Pilih Tahun')
                ->options($availableYears)
                // ->default((int) now()->format('Y')) // Default sudah di-set di mount()
                ->live() // Update chart secara otomatis saat filter berubah
                ->native(false), // Gunakan select JS bawaan browser (opsional)
        ];
    }

     /**
     * Mendapatkan judul chart secara dinamis berdasarkan filter.
     */
    public function getHeading(): string | Htmlable
    {
        $user = Auth::user();
        $selectedYear = $this->filterYear ?? now()->year; // Ambil tahun terpilih

        $title = 'Tren Total Penjualan Bulanan (' . $selectedYear . ')';

        // Tambahkan info jika bukan admin
        if ($user && !$user->hasRole('Admin')) {
             $title .= ' - Kelompok Anda';
        }

        return $title;
    }

    /**
     * Mengambil dan memformat data untuk ditampilkan di chart.
     */
    protected function getData(): array
    {
        $user = Auth::user();
        $is_admin = $user?->hasRole('Admin') ?? false;

        // Gunakan tahun dari filter, default ke tahun saat ini jika null
        $selectedYear = $this->filterYear ?? now()->year;

        // Tentukan tanggal awal dan akhir berdasarkan tahun terpilih
        $startDate = Carbon::createFromDate($selectedYear, 1, 1)->startOfYear();
        $endDate = Carbon::createFromDate($selectedYear, 12, 31)->endOfYear();

        // Query data pelaporan hasil
        $query = PelaporanHasil::query()
            ->select(
                DB::raw('YEAR(tanggal_pelaporan) as year'), // Meskipun sudah difilter, tetap select untuk konsistensi
                DB::raw('MONTH(tanggal_pelaporan) as month'),
                DB::raw('SUM(total_penjualan) as aggregate')
            )
            ->whereBetween('tanggal_pelaporan', [$startDate, $endDate]); // Filter rentang tanggal

        // Pembatasan untuk Petugas Lapangan
        if (!$is_admin) {
            $query->whereHas('distribusiBantuan.kelompok', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $salesData = $query->groupBy('year', 'month')
                            ->orderBy('month', 'asc') // Urutkan berdasarkan bulan
                            ->get();

        // Siapkan data untuk chart (label bulan dan nilai penjualan)
        $labels = [];
        $data = [];
        // Buat array berisi 12 bulan untuk tahun terpilih
        $period = Carbon::parse($startDate)->monthsUntil($endDate);

        foreach ($period as $date) {
            $month = $date->month;
            $monthLabel = $date->translatedFormat('M'); // Format label bulan (Jan, Feb, ...)
            $labels[] = $monthLabel;

            // Cari data penjualan untuk bulan ini dari hasil query
            $monthlySale = $salesData->firstWhere('month', $month);

            // Masukkan nilai aggregate (total penjualan) atau 0 jika tidak ada data
            $data[] = $monthlySale ? $monthlySale->aggregate : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Penjualan (Rp)',
                    'data' => $data,
                    'borderColor' => '#10B981', // Warna success-500
                    'backgroundColor' => '#10B981',
                    'fill' => false,
                    'tension' => 0.1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * Menentukan tipe chart.
     */
    protected function getType(): string
    {
        return 'line';
    }

    /**
     * Opsi tambahan untuk chart (format tooltip & sumbu Y).
     */
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'ticks' => [
                        'callback' => <<<JS
                            function(value, index, values) {
                                if (value >= 1000000) { return 'Rp ' + (value / 1000000).toLocaleString('id-ID') + ' Jt'; } // Jutaan
                                if (value >= 1000) { return 'Rp ' + (value / 1000).toLocaleString('id-ID') + ' Rb'; } // Ribuan
                                return 'Rp ' + new Intl.NumberFormat('id-ID').format(value); // Default
                            }
                        JS,
                    ],
                ],
            ],
            'plugins' => [
                'tooltip' => [
                    'callbacks' => [
                        'label' => <<<JS
                            function(context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
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

     /**
     * Menentukan siapa yang bisa melihat widget ini.
     */
    public static function canView(): bool
    {
        $user = Auth::user();
        // Hanya tampilkan untuk Admin dan Petugas Lapangan
        return $user && $user->hasAnyRole(['Admin', 'Petugas Lapangan']);
    }
}
