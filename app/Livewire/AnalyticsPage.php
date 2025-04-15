<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\PelaporanHasil;
use App\Models\DistribusiBantuan;
use App\Models\MonitoringPemanfaatan; // Import MonitoringPemanfaatan
use App\Models\Kecamatan;
use App\Models\Kelompok;
use App\Models\Bantuan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;
use Illuminate\Support\Collection;

class AnalyticsPage extends Component
{
    // Properti publik untuk state filter
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?string $filterKecamatan = null; // Menyimpan ID Kecamatan
    public ?string $filterDesa = null;      // Menyimpan ID Desa
    public ?string $filterBantuan = null;   // Menyimpan ID Bantuan Spesifik

    public string $search = ''; // Properti untuk input pencarian kelompok

    // Properti untuk menyimpan data chart (inisialisasi dengan struktur dasar)
    public array $salesChartData = ['labels' => [], 'datasets' => []];
    public array $aidCompositionChartData = ['labels' => [], 'datasets' => []];
    public array $groupPerformanceChartData = ['labels' => [], 'datasets' => []];
    public array $monitoringTrendChartData = ['labels' => [], 'datasets' => []]; // Data chart baru


    public array $overviewData = [
        'total_kelompok' => 0,
        'total_nilai_bantuan' => 0,
        'total_penjualan' => 0,
        'avg_perkembangan' => 0,
        'count_distribusi' => 0,
        'count_monitoring' => 0,
        'count_pelaporan' => 0,
    ];

    /**
     * Mount komponen: Inisialisasi filter & load data awal.
     */
    public function mount(): void
    {
        // Default rentang tanggal: 12 bulan terakhir
        $this->startDate = now()->subMonths(11)->startOfMonth()->toDateString();
        $this->endDate = now()->endOfMonth()->toDateString();
        // Filter lain default null

        // Panggil method untuk mengisi properti data chart saat mount
        $this->prepareChartData();
    }

    // Method prepare...ChartData() perlu diupdate untuk menggunakan $this->search
    private function applySearchFilter(Builder $query): Builder // Helper baru
    {
        if (!empty($this->search)) {
            // Sesuaikan logika pencarian ini jika perlu
            $query->where(function ($q) {
                $q->where('nama_kelompok', 'like', '%' . $this->search . '%');
                // Tambahkan pencarian lain jika perlu (misal: desa, petugas)
                // ->orWhereHas('desa', fn($dq) => $dq->where('nama_desa', 'like', '%' . $this->search . '%'))
                // ->orWhereHas('petugas', fn($pq) => $pq->where('name', 'like', '%' . $this->search . '%'));
            });
        }
        return $query;
    }

    /**
     * Menghitung data ringkasan untuk overview berdasarkan filter.
     */
    private function prepareOverviewData(): void
    {
        $user = Auth::user();
        $is_admin = $user?->hasRole('Admin') ?? false;
        $start = Carbon::parse($this->startDate ?? now()->subMonths(11)->startOfMonth())->startOfDay();
        $end = Carbon::parse($this->endDate ?? now()->endOfMonth())->endOfDay();
        $kecamatanId = $this->filterKecamatan;
        $desaId = $this->filterDesa;
        $bantuanId = $this->filterBantuan;

        Log::info("Preparing Overview Data with filters...");

        try {
            // --- Query dasar dengan filter umum ---
            $baseQueryBuilder = function (string $modelClass) use ($user, $is_admin, $kecamatanId, $desaId, $bantuanId) {
                $query = $modelClass::query();

                // Filter berdasarkan relasi kelompok (jika model punya relasi ke Distribusi atau langsung ke Kelompok)
                if (method_exists($modelClass, 'distribusiBantuan')) { // Untuk Monitoring, Pelaporan
                    $query->whereHas('distribusiBantuan.kelompok', function (Builder $q) use ($user, $is_admin, $kecamatanId, $desaId) {
                        if ($kecamatanId) { $q->whereHas('desa', fn($dq) => $dq->where('kecamatan_id', $kecamatanId)); }
                        if ($desaId) { $q->where('desa_id', $desaId); }
                        if (!$is_admin) { $q->where('user_id', $user->id); }
                    });
                    if ($bantuanId) { $query->whereHas('distribusiBantuan', fn(Builder $q) => $q->where('bantuan_id', $bantuanId)); }

                } elseif (method_exists($modelClass, 'kelompok')) { // Untuk Distribusi
                    $query->whereHas('kelompok', function (Builder $q) use ($user, $is_admin, $kecamatanId, $desaId) {
                        if ($kecamatanId) { $q->whereHas('desa', fn($dq) => $dq->where('kecamatan_id', $kecamatanId)); }
                        if ($desaId) { $q->where('desa_id', $desaId); }
                        if (!$is_admin) { $q->where('user_id', $user->id); }
                    });
                    if ($bantuanId) { $query->where('bantuan_id', $bantuanId); } // Langsung filter bantuan_id

                } elseif ($modelClass === Kelompok::class) { // Untuk Kelompok
                    if ($kecamatanId) { $query->whereHas('desa', fn(Builder $q) => $q->where('kecamatan_id', $kecamatanId)); }
                    if ($desaId) { $query->where('desa_id', $desaId); }
                    if (!$is_admin) { $query->where('user_id', $user->id); }
                    // Filter bantuan untuk kelompok agak rumit, mungkin di-skip di overview kelompok
                }
                return $query;
            };
            // ------------------------------------

            // --- Hitung Metrik ---
            // Total Kelompok Terdampak (kelompok yg menerima distribusi sesuai filter)
            $kelompokIds = $baseQueryBuilder(DistribusiBantuan::class)
                            ->whereBetween('tanggal_distribusi', [$start, $end]) // Filter tanggal distribusi
                            ->where('status_pemberian', 'Diterima Kelompok')
                            ->distinct()
                            ->pluck('kelompok_id');
            $totalKelompok = $kelompokIds->count();

            // Total Nilai Bantuan
            $totalNilaiBantuan = $baseQueryBuilder(DistribusiBantuan::class)
                                ->whereBetween('tanggal_distribusi', [$start, $end])
                                ->where('status_pemberian', 'Diterima Kelompok')
                                ->sum('harga_awal_total') ?? 0;

            // Total Penjualan
            $totalPenjualan = $baseQueryBuilder(PelaporanHasil::class)
                                ->whereBetween('tanggal_pelaporan', [$start, $end])
                                ->sum('total_penjualan') ?? 0;

            // Rata-rata Perkembangan
            $avgPerkembangan = $baseQueryBuilder(MonitoringPemanfaatan::class)
                                ->whereBetween('tanggal_monitoring', [$start, $end])
                                ->avg('persentase_perkembangan'); // Bisa null jika tidak ada data

            // Jumlah Record
            $countDistribusi = $baseQueryBuilder(DistribusiBantuan::class)
                                ->whereBetween('tanggal_distribusi', [$start, $end])
                                ->count();
            $countMonitoring = $baseQueryBuilder(MonitoringPemanfaatan::class)
                                ->whereBetween('tanggal_monitoring', [$start, $end])
                                ->count();
            $countPelaporan = $baseQueryBuilder(PelaporanHasil::class)
                                ->whereBetween('tanggal_pelaporan', [$start, $end])
                                ->count();
            // ---------------------


            // Isi properti overviewData
            $this->overviewData = [
                'total_kelompok' => $totalKelompok,
                'total_nilai_bantuan' => $totalNilaiBantuan,
                'total_penjualan' => $totalPenjualan,
                'avg_perkembangan' => $avgPerkembangan !== null ? round($avgPerkembangan, 1) : null,
                'count_distribusi' => $countDistribusi,
                'count_monitoring' => $countMonitoring,
                'count_pelaporan' => $countPelaporan,
            ];
            Log::info('Overview data prepared:', $this->overviewData);

        } catch (\Exception $e) {
            Log::error("Error preparing overview data: " . $e->getMessage());
            Notification::make()->title('Gagal Memuat Ringkasan Overview')->danger()->send();
            // Reset ke default jika error
            $this->overviewData = array_fill_keys(array_keys($this->overviewData), 0);
            $this->overviewData['avg_perkembangan'] = null;
        }
    }

    /**
     * Method utama untuk mengambil SEMUA data chart, mengisi properti,
     * dan mengirim satu event gabungan.
     */
    public function prepareChartData(): void
    {
        $this->prepareOverviewData(); // <-- Panggil ini dulu
        $this->prepareSalesChartData();
        $this->prepareAidCompositionChartData();
        $this->prepareGroupPerformanceChartData();
        $this->prepareMonitoringTrendChartData();

        // Dispatch event untuk chart (overview tidak perlu event terpisah, dirender langsung)
        $this->dispatch('updateCharts', [
            'sales' => $this->salesChartData,
            'aid' => $this->aidCompositionChartData,
            'group' => $this->groupPerformanceChartData,
            'monitoring' => $this->monitoringTrendChartData,
        ]);
        Log::info('Dispatching updateCharts with all data.');
    }

    /**
     * Mengambil dan MENYIAPKAN data untuk chart tren penjualan.
     */
    private function prepareSalesChartData(): void
    {
        $user = Auth::user();
        $is_admin = $user?->hasRole('Admin') ?? false;
        $start = Carbon::parse($this->startDate ?? now()->subMonths(11)->startOfMonth())->startOfDay();
        $end = Carbon::parse($this->endDate ?? now()->endOfMonth())->endOfDay();
        $kecamatanId = $this->filterKecamatan;
        $desaId = $this->filterDesa;
        $bantuanId = $this->filterBantuan;

        Log::info("Preparing Sales Chart: Start={$start}, End={$end}, Kecamatan={$kecamatanId}, Desa={$desaId}, Bantuan={$bantuanId}, IsAdmin={$is_admin}");

        try {
            $query = PelaporanHasil::query()
                ->select(
                    DB::raw('YEAR(tanggal_pelaporan) as year'),
                    DB::raw('MONTH(tanggal_pelaporan) as month'),
                    DB::raw('SUM(total_penjualan) as aggregate')
                )
                ->whereBetween('tanggal_pelaporan', [$start, $end]);

            // Terapkan semua filter
            if ($kecamatanId) {
                $query->whereHas('distribusiBantuan.kelompok.desa', fn (Builder $q) => $q->where('kecamatan_id', $kecamatanId));
            }
            if ($desaId) {
                $query->whereHas('distribusiBantuan.kelompok', fn (Builder $q) => $q->where('desa_id', $desaId));
            }
            if ($bantuanId) {
                $query->whereHas('distribusiBantuan', fn (Builder $q) => $q->where('bantuan_id', $bantuanId));
            }
            if (!$is_admin) {
                $query->whereHas('distribusiBantuan.kelompok', fn (Builder $q) => $q->where('user_id', $user->id));
            }

            // Terapkan search filter pada relasi kelompok
            if (!empty($this->search)) {
                $query->whereHas('distribusiBantuan.kelompok', function ($q) {
                    $this->applySearchFilter($q); // Gunakan helper
                });
           }

            $salesData = $query->groupBy('year', 'month')
                                ->orderBy('year', 'asc')->orderBy('month', 'asc')
                                ->get();

            $labels = []; $data = [];
            if ($start->lte($end)) {
                 $period = Carbon::parse($start)->monthsUntil($end->addDay());
                foreach ($period as $date) {
                    $year = $date->year; $month = $date->month;
                    $labels[] = $date->translatedFormat('M Y');
                    $monthlySale = $salesData->first(fn ($item) => $item->year == $year && $item->month == $month);
                    $data[] = $monthlySale ? $monthlySale->aggregate : 0;
                }
            }

            $this->salesChartData = [
                'labels' => $labels,
                'datasets' => [['label' => 'Total Penjualan (Rp)', 'data' => $data, 'borderColor' => '#10B981', 'backgroundColor' => 'rgba(16, 185, 129, 0.1)', 'fill' => true, 'tension' => 0.2,]],
            ];
             Log::info('Sales chart data prepared:', $this->salesChartData);

        } catch (\Exception $e) {
             Log::error("Error preparing sales chart data: " . $e->getMessage());
             Notification::make()->title('Gagal Memuat Chart Penjualan')->danger()->send();
             $this->salesChartData = ['labels' => [], 'datasets' => []];
        }
    }

    /**
     * Mengambil dan MENYIAPKAN data untuk chart komposisi bantuan.
     */
    private function prepareAidCompositionChartData(): void
    {
         $user = Auth::user();
         $is_admin = $user?->hasRole('Admin') ?? false;
         $start = Carbon::parse($this->startDate ?? now()->subMonths(11)->startOfMonth())->startOfDay();
         $end = Carbon::parse($this->endDate ?? now()->endOfMonth())->endOfDay();
         $kecamatanId = $this->filterKecamatan;
         $desaId = $this->filterDesa;
         $bantuanId = $this->filterBantuan; // Filter bantuan spesifik

         Log::info("Preparing Aid Composition Chart: Start={$start}, End={$end}, Kecamatan={$kecamatanId}, Desa={$desaId}, Bantuan={$bantuanId}, IsAdmin={$is_admin}");

         try {
             $query = DistribusiBantuan::query()
                 ->join('bantuans', 'distribusi_bantuans.bantuan_id', '=', 'bantuans.id')
                 ->select('bantuans.jenis_bantuan', DB::raw('SUM(distribusi_bantuans.harga_awal_total) as total_value'))
                 ->where('distribusi_bantuans.status_pemberian', 'Diterima Kelompok')
                 ->whereBetween('distribusi_bantuans.tanggal_distribusi', [$start, $end]);

             // Terapkan filter
             if ($kecamatanId) { $query->whereHas('kelompok.desa', fn(Builder $q) => $q->where('kecamatan_id', $kecamatanId)); }
             if ($desaId) { $query->whereHas('kelompok', fn(Builder $q) => $q->where('desa_id', $desaId)); }
             if ($bantuanId) { $query->where('distribusi_bantuans.bantuan_id', $bantuanId); } // Filter bantuan spesifik
             if (!$is_admin) { $query->whereHas('kelompok', fn(Builder $q) => $q->where('user_id', $user->id)); }

             // Terapkan search filter pada relasi kelompok
             if (!empty($this->search)) {
                $query->whereHas('kelompok', function ($q) {
                    $this->applySearchFilter($q); // Gunakan helper
                });
            }

             $data = $query->groupBy('bantuans.jenis_bantuan')
                           ->orderBy('bantuans.jenis_bantuan')
                           ->pluck('total_value', 'jenis_bantuan');

             $colors = ['Pertanian' => '#10B981', 'Peternakan' => '#F59E0B', 'Perikanan' => '#3B82F6', 'Lainnya' => '#6B7280'];
             $backgroundColors = $data->keys()->map(fn ($jenis) => $colors[$jenis] ?? $colors['Lainnya'])->values()->toArray();

             $this->aidCompositionChartData = [
                 'labels' => $data->keys()->toArray(),
                 'datasets' => [['label' => 'Total Nilai Bantuan (Rp)', 'data' => $data->values()->toArray(), 'backgroundColor' => $backgroundColors, 'borderColor' => $backgroundColors, 'borderWidth' => 1,]],
             ];
              Log::info('Aid composition chart data prepared:', $this->aidCompositionChartData);

         } catch (\Exception $e) {
              Log::error("Error preparing aid composition chart data: " . $e->getMessage());
              Notification::make()->title('Gagal Memuat Chart Komposisi Bantuan')->danger()->send();
              $this->aidCompositionChartData = ['labels' => [], 'datasets' => []];
         }
    }

    /**
     * Mengambil dan MENYIAPKAN data untuk chart performa kelompok.
     */
    private function prepareGroupPerformanceChartData(): void
    {
        $user = Auth::user();
        $is_admin = $user?->hasRole('Admin') ?? false;
        $start = Carbon::parse($this->startDate ?? now()->subMonths(11)->startOfMonth())->startOfDay();
        $end = Carbon::parse($this->endDate ?? now()->endOfMonth())->endOfDay();
        $kecamatanId = $this->filterKecamatan;
        $desaId = $this->filterDesa;
        $bantuanId = $this->filterBantuan;

        Log::info("Preparing Group Performance Chart: Start={$start}, End={$end}, Kecamatan={$kecamatanId}, Desa={$desaId}, Bantuan={$bantuanId}, IsAdmin={$is_admin}");

         try {
             $query = Kelompok::query()
                 ->select('kelompoks.id', 'kelompoks.nama_kelompok')
                 ->withSum(['allPelaporanHasils as total_penjualan_agg' => function ($q) use ($start, $end, $bantuanId) {
                     $q->whereBetween('tanggal_pelaporan', [$start, $end]);
                     // Filter berdasarkan bantuan spesifik jika dipilih
                     if ($bantuanId) {
                         $q->whereHas('distribusiBantuan', fn(Builder $dq) => $dq->where('bantuan_id', $bantuanId));
                     }
                 }], 'total_penjualan')
                 ->whereHas('allPelaporanHasils', function ($q) use ($start, $end, $bantuanId) {
                      $q->whereBetween('tanggal_pelaporan', [$start, $end]);
                      if ($bantuanId) {
                          $q->whereHas('distribusiBantuan', fn(Builder $dq) => $dq->where('bantuan_id', $bantuanId));
                      }
                 });

             // Terapkan filter
             if ($kecamatanId) { $query->whereHas('desa', fn(Builder $q) => $q->where('kecamatan_id', $kecamatanId)); }
             if ($desaId) { $query->where('desa_id', $desaId); }
             // Filter bantuan sudah diterapkan di whereHas/withSum
             if (!$is_admin) { $query->where('user_id', $user->id); }

             // Terapkan search filter langsung ke query Kelompok
             $query = $this->applySearchFilter($query); // Gunakan helper

             $topGroups = $query->orderByDesc('total_penjualan_agg')->limit(10)->get();

             $this->groupPerformanceChartData = [
                 'labels' => $topGroups->pluck('nama_kelompok')->toArray(),
                 'datasets' => [['label' => 'Total Penjualan Dilaporkan (Rp)', 'data' => $topGroups->pluck('total_penjualan_agg')->toArray(), 'backgroundColor' => '#8B5CF6', 'borderColor' => '#8B5CF6', 'borderWidth' => 1,]],
             ];
              Log::info('Group performance chart data prepared:', $this->groupPerformanceChartData);

         } catch (\Exception $e) {
              Log::error("Error preparing group performance chart data: " . $e->getMessage());
              Notification::make()->title('Gagal Memuat Chart Performa Kelompok')->danger()->send();
              $this->groupPerformanceChartData = ['labels' => [], 'datasets' => []];
         }
    }

    /**
     * Mengambil dan MENYIAPKAN data untuk chart tren perkembangan monitoring.
     */
    private function prepareMonitoringTrendChartData(): void
    {
        $user = Auth::user();
        $is_admin = $user?->hasRole('Admin') ?? false;
        $start = Carbon::parse($this->startDate ?? now()->subMonths(11)->startOfMonth())->startOfDay();
        $end = Carbon::parse($this->endDate ?? now()->endOfMonth())->endOfDay();
        $kecamatanId = $this->filterKecamatan;
        $desaId = $this->filterDesa;
        $bantuanId = $this->filterBantuan;

        Log::info("Preparing Monitoring Trend Chart: Start={$start}, End={$end}, Kecamatan={$kecamatanId}, Desa={$desaId}, Bantuan={$bantuanId}, IsAdmin={$is_admin}");

        try {
            $query = MonitoringPemanfaatan::query()
                ->select(
                    DB::raw('YEAR(tanggal_monitoring) as year'),
                    DB::raw('MONTH(tanggal_monitoring) as month'),
                    DB::raw('AVG(persentase_perkembangan) as average_progress')
                )
                ->whereBetween('tanggal_monitoring', [$start, $end]);

            // Terapkan semua filter
            if ($kecamatanId) { $query->whereHas('distribusiBantuan.kelompok.desa', fn (Builder $q) => $q->where('kecamatan_id', $kecamatanId)); }
            if ($desaId) { $query->whereHas('distribusiBantuan.kelompok', fn (Builder $q) => $q->where('desa_id', $desaId)); }
            if ($bantuanId) { $query->whereHas('distribusiBantuan', fn (Builder $q) => $q->where('bantuan_id', $bantuanId)); }
            if (!$is_admin) { $query->whereHas('distribusiBantuan.kelompok', fn (Builder $q) => $q->where('user_id', $user->id)); }

            // Terapkan search filter pada relasi kelompok
            if (!empty($this->search)) {
                $query->whereHas('distribusiBantuan.kelompok', function ($q) {
                    $this->applySearchFilter($q); // Gunakan helper
                });
           }

            $monitoringData = $query->groupBy('year', 'month')
                                    ->orderBy('year', 'asc')->orderBy('month', 'asc')
                                    ->get();

            $labels = []; $data = [];
            if ($start->lte($end)) {
                 $period = Carbon::parse($start)->monthsUntil($end->addDay());
                foreach ($period as $date) {
                    $year = $date->year; $month = $date->month;
                    $labels[] = $date->translatedFormat('M Y');
                    $monthlyAvg = $monitoringData->first(fn ($item) => $item->year == $year && $item->month == $month);
                    $data[] = $monthlyAvg ? round($monthlyAvg->average_progress, 1) : 0;
                }
            }

            $this->monitoringTrendChartData = [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Rata-rata Perkembangan (%)',
                    'data' => $data,
                    'borderColor' => '#F59E0B', // Amber
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => true,
                    'tension' => 0.2,
                ]],
            ];
             Log::info('Monitoring trend chart data prepared:', $this->monitoringTrendChartData);

        } catch (\Exception $e) {
             Log::error("Error preparing monitoring trend chart data: " . $e->getMessage());
             Notification::make()->title('Gagal Memuat Chart Tren Monitoring')->danger()->send();
             $this->monitoringTrendChartData = ['labels' => [], 'datasets' => []];
        }
    }

    /**
     * Hook yang dijalankan SETELAH properti filter diupdate.
     */
    public function updated($propertyName): void
    {
        // Tambahkan properti filter baru ke pengecekan
        if (in_array($propertyName, ['startDate', 'endDate', 'filterKecamatan', 'filterDesa', 'filterBantuan', 'search'])) {
            // Jika filter kecamatan berubah, reset filter desa
            if ($propertyName === 'filterKecamatan') {
                $this->filterDesa = null;
            }
            // Panggil method utama untuk load ulang SEMUA data dan dispatch event gabungan
            $this->prepareChartData();
        }
    }

    /**
     * Render view blade komponen.
     */
    public function render()
    {
        $kecamatans = Kecamatan::orderBy('nama_kecamatan')->pluck('nama_kecamatan', 'id');
        // Ambil desa berdasarkan kecamatan terpilih
        $desas = collect();
        if ($this->filterKecamatan) {
            $desas = \App\Models\Desa::where('kecamatan_id', $this->filterKecamatan)->orderBy('nama_desa')->pluck('nama_desa', 'id');
        }
        // Ambil opsi bantuan spesifik
        $bantuans = Bantuan::orderBy('nama_bantuan')->pluck('nama_bantuan', 'id');

        return view('livewire.analytics-page', [
            'kecamatans' => $kecamatans,
            'desas' => $desas,
            'bantuans' => $bantuans,
        ]);
    }
}
