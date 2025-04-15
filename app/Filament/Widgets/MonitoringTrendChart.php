<?php

namespace App\Filament\Widgets;

use App\Models\MonitoringPemanfaatan; // Import model MonitoringPemanfaatan
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;   // Untuk filter (jika ditaruh di sini)
use Filament\Forms\Components\DatePicker; // Untuk filter (jika ditaruh di sini)
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder; // Import Builder

class MonitoringTrendChart extends ChartWidget
{
    // Konfigurasi Dasar
    // protected static ?string $heading = 'Tren Rata-rata Perkembangan Monitoring'; // Dioverride getHeading
    protected static ?int $sort = 9; // Urutan setelah widget lain
    protected static string $color = 'warning'; // Warna tema chart (misal: kuning/amber)
    protected int | string | array $columnSpan = 'full'; // Ambil lebar penuh

    // Properti Filter (jika ingin filter spesifik widget, tapi lebih baik pakai filter global halaman Analitik)
    // public ?string $startDate = null;
    // public ?string $endDate = null;

    // Filter yang dibaca dari state komponen Livewire Analitik (jika widget ini ada di sana)
    // Atau bisa juga dibuat filter internal widget jika berdiri sendiri
    public ?array $filterData = [];

    protected function getFilters(): ?array
    {
        // Jika ingin filter di widget ini (kurang direkomendasikan jika sudah ada di halaman)
        // return [
        //     'startDate' => DatePicker::make...
        //     'endDate' => DatePicker::make...
        // ];
        return null; // Tidak ada filter di widget ini, ambil dari halaman
    }

     /**
     * Mendapatkan judul chart secara dinamis.
     */
    public function getHeading(): string | Htmlable
    {
        // Ambil tanggal dari filter (jika ada) atau set default
        $start = Carbon::parse($this->filterData['startDate'] ?? now()->subMonths(11)->startOfMonth());
        $end = Carbon::parse($this->filterData['endDate'] ?? now()->endOfMonth());

        $title = 'Tren Rata-rata Perkembangan Monitoring (%)';

        // Tambahkan rentang waktu ke judul
        if ($start->year === $end->year) {
            $title .= ' (' . $start->year . ')';
        } else {
            $title .= ' (' . $start->translatedFormat('M Y') . ' - ' . $end->translatedFormat('M Y') . ')';
        }

        // Tambahkan info jika bukan admin
        if (Auth::check() && !Auth::user()->hasRole('Admin')) {
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

        // Ambil filter dari properti $filterData (yang diisi oleh halaman induk)
        // Atau dari $this->filter jika menggunakan getFilters() di widget ini
        $start = Carbon::parse($this->filterData['startDate'] ?? now()->subMonths(11)->startOfMonth())->startOfDay();
        $end = Carbon::parse($this->filterData['endDate'] ?? now()->endOfMonth())->endOfDay();
        $kecamatanId = $this->filterData['filterKecamatan'] ?? null;
        $desaId = $this->filterData['filterDesa'] ?? null;
        $bantuanId = $this->filterData['filterBantuan'] ?? null;

        \Log::info("Loading Monitoring Trend Chart with filters: ", $this->filterData); // Debugging filter

        try {
            // Query data monitoring pemanfaatan
            $query = MonitoringPemanfaatan::query()
                ->select(
                    DB::raw('YEAR(tanggal_monitoring) as year'),
                    DB::raw('MONTH(tanggal_monitoring) as month'),
                    // Hitung rata-rata persentase perkembangan
                    DB::raw('AVG(persentase_perkembangan) as average_progress')
                )
                ->whereBetween('tanggal_monitoring', [$start, $end]);

            // Terapkan filter
            if ($kecamatanId) { $query->whereHas('distribusiBantuan.kelompok.desa', fn (Builder $q) => $q->where('kecamatan_id', $kecamatanId)); }
            if ($desaId) { $query->whereHas('distribusiBantuan.kelompok', fn (Builder $q) => $q->where('desa_id', $desaId)); }
            if ($bantuanId) { $query->whereHas('distribusiBantuan', fn (Builder $q) => $q->where('bantuan_id', $bantuanId)); }
            if (!$is_admin) { $query->whereHas('distribusiBantuan.kelompok', fn (Builder $q) => $q->where('user_id', $user->id)); }

            $monitoringData = $query->groupBy('year', 'month')
                                    ->orderBy('year', 'asc')->orderBy('month', 'asc')
                                    ->get();

            // Siapkan data untuk chart
            $labels = []; $data = [];
            if ($start->lte($end)) {
                 $period = Carbon::parse($start)->monthsUntil($end->addDay());
                foreach ($period as $date) {
                    $year = $date->year; $month = $date->month;
                    $labels[] = $date->translatedFormat('M Y');
                    $monthlyAvg = $monitoringData->first(fn ($item) => $item->year == $year && $item->month == $month);
                    // Bulatkan rata-rata ke 1 desimal, atau 0 jika null
                    $data[] = $monthlyAvg ? round($monthlyAvg->average_progress, 1) : 0;
                }
            }

            return [
                'datasets' => [[
                    'label' => 'Rata-rata Perkembangan (%)',
                    'data' => $data,
                    'borderColor' => '#F59E0B', // Warna Amber/Warning
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => true,
                    'tension' => 0.2,
                ]],
                'labels' => $labels,
            ];

        } catch (\Exception $e) {
             \Log::error("Error loading monitoring trend chart data: " . $e->getMessage());
             Notification::make()->title('Gagal Memuat Chart Tren Monitoring')->danger()->send();
             return ['datasets' => [], 'labels' => []]; // Kembalikan data kosong
        }
    }

    protected function getType(): string
    {
        return 'line';
    }

     /**
     * Opsi tambahan untuk chart (format tooltip & sumbu Y persen).
     */
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'max' => 100, // Sumbu Y maksimal 100%
                    'ticks' => [
                        'callback' => <<<JS
                            function(value, index, values) {
                                return value + '%'; // Tambahkan simbol %
                            }
                        JS,
                    ],
                ],
                 'x' => [ // Opsi sumbu X jika perlu
                     'ticks' => [ 'autoSkip' => true, 'maxRotation' => 0 ]
                 ]
            ],
            'plugins' => [
                'tooltip' => [
                    'callbacks' => [
                        'label' => <<<JS
                            function(context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
                                if (context.parsed.y !== null) {
                                    // Format dengan 1 desimal
                                    label += new Intl.NumberFormat('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(context.parsed.y) + '%';
                                }
                                return label;
                            }
                        JS,
                    ],
                ],
                 'legend' => [ 'display' => true ] // Tampilkan legend
            ],
        ];
    }

     /**
     * Menentukan siapa yang bisa melihat widget ini.
     */
    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->hasAnyRole(['Admin', 'Petugas Lapangan']);
    }
}
