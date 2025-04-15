<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Bantuan;
use App\Models\DistribusiBantuan;
use App\Models\Kecamatan;
use App\Models\Kelompok;
use App\Models\MonitoringPemanfaatan;
use App\Models\PelaporanHasil;
use App\Models\User;
use App\Models\Desa; // Import Desa
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    /**
     * Menampilkan halaman Analitik Admin.
     */
    public function index(Request $request)
    {
        // Ambil filter dari request, set default jika tidak ada
        $startDate = $request->input('start_date', now()->subMonths(11)->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
        $filterKecamatan = $request->input('kecamatan');
        $filterDesa = $request->input('desa');
        $filterBantuan = $request->input('bantuan');
        $filterPetugas = $request->input('petugas');
        $filterJenis = $request->input('jenis'); // Ambil filter jenis kelompok
        $search = $request->input('search');     // Ambil search kelompok

        // --- Persiapan Data Overview ---
        $overviewData = $this->getOverviewData($startDate, $endDate, $filterKecamatan, $filterDesa, $filterBantuan, $filterPetugas, $filterJenis, $search);

        // --- Persiapan Data Chart ---
        $salesTrendData = $this->getSalesTrendData($startDate, $endDate, $filterKecamatan, $filterDesa, $filterBantuan, $filterPetugas, $filterJenis, $search);
        $aidCompositionData = $this->getAidCompositionData($startDate, $endDate, $filterKecamatan, $filterDesa, $filterBantuan, $filterPetugas, $filterJenis, $search);
        $groupPerformanceData = $this->getGroupPerformanceData($startDate, $endDate, $filterKecamatan, $filterDesa, $filterBantuan, $filterPetugas, $filterJenis, $search);
        $monitoringTrendData = $this->getMonitoringTrendData($startDate, $endDate, $filterKecamatan, $filterDesa, $filterBantuan, $filterPetugas, $filterJenis, $search);

        // --- Query untuk Tabel Resume Kelompok (Pagination) ---
        // --- TAMBAHKAN KEMBALI QUERY INI ---
        $kelompokQuery = Kelompok::query()
            ->select('kelompoks.*')
            ->with(['desa.kecamatan', 'petugas', 'ketua'])
            ->withSum(['distribusiBantuans as total_nilai_bantuan_agg' => fn ($q) => $q->where('status_pemberian', 'Diterima Kelompok')], 'harga_awal_total')
            ->withSum(['allPelaporanHasils as total_penjualan_agg' => function ($q) use ($startDate, $endDate, $filterBantuan) {
                 $q->whereBetween('tanggal_pelaporan', [$startDate, $endDate]);
                 if ($filterBantuan) { $q->whereHas('distribusiBantuan', fn(Builder $dq) => $dq->where('bantuan_id', $filterBantuan)); }
            }], 'total_penjualan')
            ->withAvg(['allMonitoringPemanfaatans as avg_perkembangan_agg' => function ($q) use ($startDate, $endDate, $filterBantuan) {
                 $q->whereBetween('tanggal_monitoring', [$startDate, $endDate]);
                 if ($filterBantuan) { $q->whereHas('distribusiBantuan', fn(Builder $dq) => $dq->where('bantuan_id', $filterBantuan)); }
            }], 'persentase_perkembangan')
            ->addSelect(['monitoring_terakhir' => \App\Models\MonitoringPemanfaatan::select('tanggal_monitoring')
                ->whereColumn('distribusi_bantuans.kelompok_id', 'kelompoks.id')
                ->join('distribusi_bantuans', 'monitoring_pemanfaatans.distribusi_bantuan_id', '=', 'distribusi_bantuans.id')
                ->latest('tanggal_monitoring')
                ->limit(1)
            ]);

        // Terapkan filter ke query kelompok
        $kelompokQuery = $this->applyCommonFilters($kelompokQuery, Kelompok::class, $filterKecamatan, $filterDesa, $filterBantuan, $filterPetugas, $filterJenis, $search);

        // Terapkan sorting kelompok
        $sortColumn = $request->input('sort', 'nama_kelompok');
        $sortDirection = $request->input('direction', 'asc');
        $allowedSortColumns = ['nama_kelompok', 'jenis_kelompok', 'total_penjualan_agg', 'avg_perkembangan_agg', 'monitoring_terakhir'];
        if (!in_array($sortColumn, $allowedSortColumns)) { $sortColumn = 'nama_kelompok'; }
        $sortDirection = strtolower($sortDirection) === 'desc' ? 'desc' : 'asc';
        $kelompokQuery->orderBy($sortColumn, $sortDirection);

        // Lakukan Pagination untuk kelompok
        $kelompoks = $kelompokQuery->paginate(15, ['*'], 'page')->withQueryString(); // Gunakan nama page default 'page'
        // ----------------------------------------------------

        // --- Query BARU untuk Tabel Kinerja Petugas (Pagination) ---
        $kinerjaPetugasQuery = User::query()
            ->role('Petugas Lapangan')
            ->select('users.*') // Pilih kolom user
            // Hitung Jumlah Kelompok Aktif (dengan filter)
            ->withCount(['kelompokYangDitangani as jumlah_kelompok_aktif' => function (Builder $query) use ($filterKecamatan, $filterDesa, $filterJenis, $search) {
                if ($filterKecamatan) { $query->whereHas('desa', fn($dq) => $dq->where('kecamatan_id', $filterKecamatan)); }
                if ($filterDesa) { $query->where('desa_id', $filterDesa); }
                if ($filterJenis) { $query->where('jenis_kelompok', $filterJenis); }
                if ($search) { $query->where('nama_kelompok', 'like', "%{$search}%"); }
            }])
            // --- Hitung Total Penjualan via Subquery ---
            ->addSelect(['total_penjualan_petugas' => PelaporanHasil::selectRaw('SUM(total_penjualan)')
                ->join('distribusi_bantuans', 'pelaporan_hasils.distribusi_bantuan_id', '=', 'distribusi_bantuans.id')
                ->join('kelompoks', 'distribusi_bantuans.kelompok_id', '=', 'kelompoks.id')
                ->whereColumn('kelompoks.user_id', 'users.id') // Korelasi ke user
                ->whereBetween('pelaporan_hasils.tanggal_pelaporan', [$startDate, $endDate])
                // Terapkan filter lain ke subquery jika perlu
                ->when($filterKecamatan, fn($q, $id) => $q->whereHas('distribusiBantuan.kelompok.desa', fn($dq) => $dq->where('kecamatan_id', $id)))
                ->when($filterDesa, fn($q, $id) => $q->whereHas('distribusiBantuan.kelompok', fn($dq) => $dq->where('desa_id', $id)))
                ->when($filterBantuan, fn($q, $id) => $q->whereHas('distribusiBantuan', fn($dq) => $dq->where('bantuan_id', $id)))
                ->when($filterJenis, fn($q, $jenis) => $q->whereHas('distribusiBantuan.kelompok', fn($kq) => $kq->where('jenis_kelompok', $jenis)))
                ->when($search, fn($q, $s) => $q->whereHas('distribusiBantuan.kelompok', fn($kq) => $kq->where('nama_kelompok', 'like', "%{$s}%")))
            ])
            // --- Hitung Avg Perkembangan via Subquery ---
             ->addSelect(['avg_perkembangan_petugas' => MonitoringPemanfaatan::selectRaw('AVG(persentase_perkembangan)')
                ->join('distribusi_bantuans', 'monitoring_pemanfaatans.distribusi_bantuan_id', '=', 'distribusi_bantuans.id')
                ->join('kelompoks', 'distribusi_bantuans.kelompok_id', '=', 'kelompoks.id')
                ->whereColumn('kelompoks.user_id', 'users.id') // Korelasi ke user
                ->whereBetween('monitoring_pemanfaatans.tanggal_monitoring', [$startDate, $endDate])
                 // Terapkan filter lain ke subquery jika perlu
                ->when($filterKecamatan, fn($q, $id) => $q->whereHas('distribusiBantuan.kelompok.desa', fn($dq) => $dq->where('kecamatan_id', $id)))
                ->when($filterDesa, fn($q, $id) => $q->whereHas('distribusiBantuan.kelompok', fn($dq) => $dq->where('desa_id', $id)))
                ->when($filterBantuan, fn($q, $id) => $q->whereHas('distribusiBantuan', fn($dq) => $dq->where('bantuan_id', $id)))
                ->when($filterJenis, fn($q, $jenis) => $q->whereHas('distribusiBantuan.kelompok', fn($kq) => $kq->where('jenis_kelompok', $jenis)))
                ->when($search, fn($q, $s) => $q->whereHas('distribusiBantuan.kelompok', fn($kq) => $kq->where('nama_kelompok', 'like', "%{$s}%")))
             ])
            // Hitung jumlah monitoring/pelaporan yg DILAKUKAN oleh petugas ini
            ->withCount(['monitoringPemanfaatans as count_monitoring_dilakukan' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal_monitoring', [$startDate, $endDate]);
                // Tambahkan filter lain di sini jika perlu (misal: hanya monitoring kelompok yg dia tangani?)
            }])
             ->withCount(['pelaporanHasils as count_pelaporan_dilakukan' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal_pelaporan', [$startDate, $endDate]);
                 // Tambahkan filter lain di sini jika perlu
            }]);

        // Terapkan sorting (sekarang bisa sort by kolom subquery)
        $sortPetugasColumn = $request->input('sort_petugas', 'name');
        $sortPetugasDirection = $request->input('direction_petugas', 'asc');
        $allowedSortPetugas = ['name', 'jumlah_kelompok_aktif', 'total_penjualan_petugas', 'avg_perkembangan_petugas', 'count_monitoring_dilakukan', 'count_pelaporan_dilakukan'];
         if (!in_array($sortPetugasColumn, $allowedSortPetugas)) { $sortPetugasColumn = 'name'; }
         $sortPetugasDirection = strtolower($sortPetugasDirection) === 'desc' ? 'desc' : 'asc';
         $kinerjaPetugasQuery->orderBy($sortPetugasColumn, $sortPetugasDirection);

        $kinerjaPetugas = $kinerjaPetugasQuery->paginate(10, ['*'], 'page_petugas')->withQueryString();
        // ---------------------------------------------------------

        // --- Ambil Data untuk Filter Dropdown ---
        $kecamatans = Kecamatan::orderBy('nama_kecamatan')->pluck('nama_kecamatan', 'id');
        $desas = collect();
        if ($filterKecamatan) { $desas = Desa::where('kecamatan_id', $filterKecamatan)->orderBy('nama_desa')->pluck('nama_desa', 'id'); }
        $bantuans = Bantuan::orderBy('nama_bantuan')->pluck('nama_bantuan', 'id');
        $petugasList = User::role('Petugas Lapangan')->orderBy('name')->pluck('name', 'id');
        $jenisKelompokOptions = ['Pertanian' => 'Pertanian', 'Peternakan' => 'Peternakan', 'Perikanan' => 'Perikanan'];

        // Kirim semua data ke view
        return view('public.analytics-admin', compact(
            'overviewData',
            'salesTrendData',
            'aidCompositionData',
            'groupPerformanceData',
            'monitoringTrendData',
            'kelompoks',
            'kinerjaPetugas',
            'kecamatans',
            'desas',
            'bantuans',
            'petugasList',
            'jenisKelompokOptions',
            // Kirim nilai filter saat ini
            'startDate', 'endDate', 'filterKecamatan', 'filterDesa', 'filterBantuan', 'filterPetugas', 'filterJenis', 'search',
            // Kirim variabel sort
            'sortColumn', 'sortDirection',
            'sortPetugasColumn', 'sortPetugasDirection'

        ));
    }

    // ======================================================
    // Method Helper untuk Mengambil Data (Private)
    // ======================================================

    /**
     * Helper untuk menerapkan filter umum ke query builder.
     */
    private function applyCommonFilters(Builder $query, string $modelClass, ?string $kecamatanId, ?string $desaId, ?string $bantuanId, ?string $petugasId, ?string $jenisKelompok = null, ?string $search = null): Builder
    {
        // Closure untuk filter pada tabel Kelompok
        $kelompokFilterClosure = function (Builder $q) use ($kecamatanId, $desaId, $petugasId, $jenisKelompok, $search) {
            if ($kecamatanId) { $q->whereHas('desa', fn($dq) => $dq->where('kecamatan_id', $kecamatanId)); }
            if ($desaId) { $q->where('desa_id', $desaId); }
            if ($petugasId) { // Filter Petugas PJ
                 if ($petugasId === 'none') { $q->whereNull('user_id'); } // Handle 'Belum Ditugaskan'
                 else { $q->where('user_id', $petugasId); }
            }
            if ($jenisKelompok) { $q->where('jenis_kelompok', $jenisKelompok); }
            if ($search) { $q->where('nama_kelompok', 'like', "%{$search}%"); }
        };

        // Terapkan filter berdasarkan model asal query
        if (method_exists($modelClass, 'distribusiBantuan')) { // Monitoring, Pelaporan
             $query->whereHas('distribusiBantuan.kelompok', $kelompokFilterClosure);
             if ($bantuanId) { $query->whereHas('distribusiBantuan', fn(Builder $q) => $q->where('bantuan_id', $bantuanId)); }
        } elseif (method_exists($modelClass, 'kelompok')) { // Distribusi
             $query->whereHas('kelompok', $kelompokFilterClosure);
             if ($bantuanId) { $query->where('bantuan_id', $bantuanId); }
        } elseif ($modelClass === Kelompok::class) { // Kelompok
             $query->where($kelompokFilterClosure); // Terapkan langsung
             if ($bantuanId) { $query->whereHas('distribusiBantuans', fn(Builder $q) => $q->where('bantuan_id', $bantuanId)); }
        }

        return $query;
    }

    /**
     * Menghitung data ringkasan untuk overview.
     */
    private function getOverviewData(?string $startDate, ?string $endDate, ?string $kecamatanId, ?string $desaId, ?string $bantuanId, ?string $petugasId, ?string $jenisKelompok, ?string $search): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        try {
            // Total Kelompok Terdampak (menerima distribusi sesuai filter & tanggal)
            $baseDistribusiQuery = $this->applyCommonFilters(DistribusiBantuan::query(), DistribusiBantuan::class, $kecamatanId, $desaId, $bantuanId, $petugasId, $jenisKelompok, $search)
                                    ->whereBetween('tanggal_distribusi', [$start, $end])
                                    ->where('status_pemberian', 'Diterima Kelompok');

            $totalKelompok = $baseDistribusiQuery->clone()->distinct()->pluck('kelompok_id')->count();
            $totalNilaiBantuan = $baseDistribusiQuery->clone()->sum('harga_awal_total') ?? 0;
            $countDistribusi = $baseDistribusiQuery->clone()->count(); // Hitung jumlah distribusi

            // Total Penjualan
            $totalPenjualan = $this->applyCommonFilters(PelaporanHasil::query(), PelaporanHasil::class, $kecamatanId, $desaId, $bantuanId, $petugasId, $jenisKelompok, $search)
                                ->whereBetween('tanggal_pelaporan', [$start, $end])
                                ->sum('total_penjualan') ?? 0;
            $countPelaporan = $this->applyCommonFilters(PelaporanHasil::query(), PelaporanHasil::class, $kecamatanId, $desaId, $bantuanId, $petugasId, $jenisKelompok, $search)
                                ->whereBetween('tanggal_pelaporan', [$start, $end])
                                ->count(); // Hitung jumlah pelaporan

            // Rata-rata Perkembangan
            $monitoringQuery = $this->applyCommonFilters(MonitoringPemanfaatan::query(), MonitoringPemanfaatan::class, $kecamatanId, $desaId, $bantuanId, $petugasId, $jenisKelompok, $search)
                                ->whereBetween('tanggal_monitoring', [$start, $end]);
            $avgPerkembangan = $monitoringQuery->clone()->avg('persentase_perkembangan');
            $countMonitoring = $monitoringQuery->clone()->count(); // Hitung jumlah monitoring

            return [
                'total_kelompok' => $totalKelompok,
                'total_nilai_bantuan' => $totalNilaiBantuan,
                'total_penjualan' => $totalPenjualan,
                'avg_perkembangan' => $avgPerkembangan !== null ? round($avgPerkembangan, 1) : null,
                'count_distribusi' => $countDistribusi,
                'count_monitoring' => $countMonitoring,
                'count_pelaporan' => $countPelaporan,
            ];

        } catch (\Exception $e) {
            Log::error("Error getting overview data: " . $e->getMessage());
            return array_fill_keys(['total_kelompok', 'total_nilai_bantuan', 'total_penjualan', 'avg_perkembangan', 'count_distribusi', 'count_monitoring', 'count_pelaporan'], 0);
        }
    }

    /**
     * Mengambil dan memformat data untuk chart tren penjualan.
     */
    private function getSalesTrendData(?string $startDate, ?string $endDate, ?string $kecamatanId, ?string $desaId, ?string $bantuanId, ?string $petugasId, ?string $jenisKelompok, ?string $search): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        try {
            $query = PelaporanHasil::query()
                ->select(DB::raw('YEAR(tanggal_pelaporan) as year'), DB::raw('MONTH(tanggal_pelaporan) as month'), DB::raw('SUM(total_penjualan) as aggregate'))
                ->whereBetween('tanggal_pelaporan', [$start, $end]);

            $query = $this->applyCommonFilters($query, PelaporanHasil::class, $kecamatanId, $desaId, $bantuanId, $petugasId, $jenisKelompok, $search);

            $salesData = $query->groupBy('year', 'month')->orderBy('year')->orderBy('month')->get();

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

            return ['labels' => $labels, 'datasets' => [['label' => 'Total Penjualan (Rp)', 'data' => $data, 'borderColor' => '#16A34A', 'backgroundColor' => 'rgba(22, 163, 74, 0.1)', 'fill' => true, 'tension' => 0.2]]];

        } catch (\Exception $e) {
            Log::error("Error getting sales trend data: " . $e->getMessage());
            return ['labels' => [], 'datasets' => []];
        }
    }

    /**
     * Mengambil dan memformat data untuk chart komposisi bantuan.
     */
    private function getAidCompositionData(?string $startDate, ?string $endDate, ?string $kecamatanId, ?string $desaId, ?string $bantuanId, ?string $petugasId, ?string $jenisKelompok, ?string $search): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

         try {
             $query = DistribusiBantuan::query()
                 ->join('bantuans', 'distribusi_bantuans.bantuan_id', '=', 'bantuans.id')
                 ->select('bantuans.jenis_bantuan', DB::raw('SUM(distribusi_bantuans.harga_awal_total) as total_value'))
                 ->where('distribusi_bantuans.status_pemberian', 'Diterima Kelompok')
                 ->whereBetween('distribusi_bantuans.tanggal_distribusi', [$start, $end]);

             $query = $this->applyCommonFilters($query, DistribusiBantuan::class, $kecamatanId, $desaId, $bantuanId, $petugasId, $jenisKelompok, $search);

             $data = $query->groupBy('bantuans.jenis_bantuan')->orderBy('bantuans.jenis_bantuan')->pluck('total_value', 'jenis_bantuan');
             $colors = ['Pertanian' => '#10B981', 'Peternakan' => '#F59E0B', 'Perikanan' => '#3B82F6', 'Lainnya' => '#6B7280'];
             $backgroundColors = $data->keys()->map(fn ($jenis) => $colors[$jenis] ?? $colors['Lainnya'])->values()->toArray();

             return ['labels' => $data->keys()->toArray(), 'datasets' => [['label' => 'Total Nilai Bantuan (Rp)', 'data' => $data->values()->toArray(), 'backgroundColor' => $backgroundColors, 'borderColor' => $backgroundColors, 'borderWidth' => 1]]];

         } catch (\Exception $e) {
              Log::error("Error getting aid composition data: " . $e->getMessage());
              return ['labels' => [], 'datasets' => []];
         }
    }

    /**
     * Mengambil dan memformat data untuk chart performa kelompok.
     */
    private function getGroupPerformanceData(?string $startDate, ?string $endDate, ?string $kecamatanId, ?string $desaId, ?string $bantuanId, ?string $petugasId, ?string $jenisKelompok, ?string $search): array
    {
         $start = Carbon::parse($startDate)->startOfDay();
         $end = Carbon::parse($endDate)->endOfDay();

         try {
             $query = Kelompok::query()
                 ->select('kelompoks.id', 'kelompoks.nama_kelompok')
                 ->withSum(['allPelaporanHasils as total_penjualan_agg' => function ($q) use ($start, $end, $bantuanId) {
                     $q->whereBetween('tanggal_pelaporan', [$start, $end]);
                     if ($bantuanId) { $q->whereHas('distribusiBantuan', fn(Builder $dq) => $dq->where('bantuan_id', $bantuanId)); }
                 }], 'total_penjualan')
                 ->whereHas('allPelaporanHasils', function ($q) use ($start, $end, $bantuanId) {
                      $q->whereBetween('tanggal_pelaporan', [$start, $end]);
                      if ($bantuanId) { $q->whereHas('distribusiBantuan', fn(Builder $dq) => $dq->where('bantuan_id', $bantuanId)); }
                 });

             $query = $this->applyCommonFilters($query, Kelompok::class, $kecamatanId, $desaId, null, $petugasId, $jenisKelompok, $search); // Filter bantuan sudah di sum/has

             $topGroups = $query->orderByDesc('total_penjualan_agg')->limit(10)->get();

             return ['labels' => $topGroups->pluck('nama_kelompok')->toArray(), 'datasets' => [['label' => 'Total Penjualan Dilaporkan (Rp)', 'data' => $topGroups->pluck('total_penjualan_agg')->toArray(), 'backgroundColor' => '#8B5CF6', 'borderColor' => '#8B5CF6', 'borderWidth' => 1]]];

         } catch (\Exception $e) {
              Log::error("Error getting group performance data: " . $e->getMessage());
              return ['labels' => [], 'datasets' => []];
         }
    }

    /**
     * Mengambil dan memformat data untuk chart tren monitoring.
     */
    private function getMonitoringTrendData(?string $startDate, ?string $endDate, ?string $kecamatanId, ?string $desaId, ?string $bantuanId, ?string $petugasId, ?string $jenisKelompok, ?string $search): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        try {
            $query = MonitoringPemanfaatan::query()
                ->select(DB::raw('YEAR(tanggal_monitoring) as year'), DB::raw('MONTH(tanggal_monitoring) as month'), DB::raw('AVG(persentase_perkembangan) as average_progress'))
                ->whereBetween('tanggal_monitoring', [$start, $end]);

            $query = $this->applyCommonFilters($query, MonitoringPemanfaatan::class, $kecamatanId, $desaId, $bantuanId, $petugasId, $jenisKelompok, $search);

            $monitoringData = $query->groupBy('year', 'month')->orderBy('year')->orderBy('month')->get();

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

            return ['labels' => $labels, 'datasets' => [['label' => 'Rata-rata Perkembangan (%)', 'data' => $data, 'borderColor' => '#F59E0B', 'backgroundColor' => 'rgba(245, 158, 11, 0.1)', 'fill' => true, 'tension' => 0.2]]];

        } catch (\Exception $e) {
             Log::error("Error getting monitoring trend data: " . $e->getMessage());
             return ['labels' => [], 'datasets' => []];
        }
    }
}
