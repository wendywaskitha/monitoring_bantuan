<?php

namespace App\Http\Controllers\Frontend;

use App\Models\Anggota;
use App\Models\Kelompok;
use App\Models\Kecamatan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\DistribusiBantuan;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage; // Import Storage facade

class KelompokController extends Controller
{
    /**
     * Menampilkan halaman Peta Potensi dan Kelompok Unggulan.
     */
    public function petaPotensi(Request $request)
    {
        $filterKecamatanId = $request->input('kecamatan');
        $filterJenis = $request->input('jenis');

        // --- Query untuk Kelompok Unggulan (Top 6 Penjualan) ---
        $kelompokUnggulanQuery = Kelompok::query()
            ->select('kelompoks.*')
            ->with(['desa.kecamatan'])
            // Pastikan accessor 'total_hasil_penjualan' ada di model Kelompok
            // atau gunakan subquery seperti di bawah
            ->withSum(['allPelaporanHasils as total_penjualan_agg' => function ($q) {
                // Filter periode penjualan jika perlu
                // $q->whereBetween('tanggal_pelaporan', [now()->subYear(), now()]);
            }], 'total_penjualan')
            ->whereHas('allPelaporanHasils') // Hanya yg punya data penjualan
            ->whereNotNull('latitude')->whereNotNull('longitude'); // Hanya yg ada lokasi

        // Terapkan filter
        if ($filterKecamatanId) {
            $kelompokUnggulanQuery->whereHas('desa', fn(Builder $q) => $q->where('kecamatan_id', $filterKecamatanId));
        }
        if ($filterJenis) {
            $kelompokUnggulanQuery->where('jenis_kelompok', $filterJenis);
        }

        $kelompokUnggulan = $kelompokUnggulanQuery->orderByDesc('total_penjualan_agg') // Urutkan berdasarkan SUM
                                                 ->limit(6)->get();
        // -------------------------------------------------------------

        // --- Query untuk Data Peta ---
        $kelompokPetaQuery = Kelompok::query()
            ->with(['desa', 'petugas'])
            ->whereNotNull('latitude')->whereNotNull('longitude');

        // Terapkan filter
        if ($filterKecamatanId) { $kelompokPetaQuery->whereHas('desa', fn(Builder $q) => $q->where('kecamatan_id', $filterKecamatanId)); }
        if ($filterJenis) { $kelompokPetaQuery->where('jenis_kelompok', $filterJenis); }

        // Ambil data dan format
        $kelompokPeta = $kelompokPetaQuery->limit(500)
                ->get()
                ->map(function(Kelompok $kelompok) {
                    // --- TAMBAHKAN DEFAULT KE COLORMAP ---
                    $colorMap = [
                        'Pertanian' => '#198754', // Bootstrap Success
                        'Peternakan' => '#ffc107', // Bootstrap Warning
                        'Perikanan' => '#0d6efd', // Bootstrap Primary/Info
                        'default' => '#6c757d'  // <-- TAMBAHKAN DEFAULT (Bootstrap Secondary)
                    ];
                    // ------------------------------------
                    $warna = $colorMap[$kelompok->jenis_kelompok] ?? $colorMap['default']; // Sekarang aman

                    return [
                        'id' => $kelompok->id,
                        'latitude' => (float) $kelompok->latitude,
                        'longitude' => (float) $kelompok->longitude,
                        'nama' => e($kelompok->nama_kelompok),
                        'jenis' => e($kelompok->jenis_kelompok),
                        'warna' => $warna,
                        'desa' => e($kelompok->desa?->nama_desa ?? 'N/A'),
                        'petugas' => e($kelompok->petugas?->name ?? 'N/A'),
                        'detail_url' => route('kelompok.detail', $kelompok->id),
                    ];
                });
        // -------------------------------------------------------------

        $kecamatans = Kecamatan::orderBy('nama_kecamatan')->pluck('nama_kecamatan', 'id');

        // --- HITUNG DATA UNTUK OVERVIEW STATS ---
        $totalKelompok = Kelompok::count(); // Hitung semua kelompok
        $totalNilaiBantuan = DistribusiBantuan::where('status_pemberian', 'Diterima Kelompok')->sum('harga_awal_total');
        $totalKecamatan = Kecamatan::count(); // Jumlah kecamatan di database
        $totalAnggota = Anggota::count(); // Total semua anggota
        // ---------------------------------------


        return view('public.peta-potensi', compact(
            'kelompokUnggulan',
            'kelompokPeta',
            'kecamatans',
            'totalKelompok', // <-- Kirim data overview
            'totalNilaiBantuan',
            'totalKecamatan',
            'totalAnggota'
        ));
    }

    /**
     * Menampilkan halaman detail satu kelompok.
     */
    public function show(Kelompok $kelompok)
    {
        // Eager load relasi yang dibutuhkan
        $kelompok->load([
            'desa.kecamatan',
            'ketua',
            'petugas',
            'anggotas' => fn($q) => $q->orderByRaw("FIELD(id, {$kelompok->ketua_id}) DESC")->orderBy('nama_anggota'),
            'distribusiBantuans' => function ($q) {
                $q->with('bantuan')->where('status_pemberian', 'Diterima Kelompok')->latest('tanggal_distribusi');
            },
            // Load SEMUA monitoring & pelaporan untuk ambil foto & agregat (jika tidak pakai accessor)
            'allMonitoringPemanfaatans' => fn($q) => $q->latest('tanggal_monitoring'),
            'allPelaporanHasils' => fn($q) => $q->latest('tanggal_pelaporan'),
        ]);

        // --- Hitung/Ambil Data Agregat & Spesifik ---
        // Gunakan accessor jika tersedia di model Kelompok
        $totalNilaiBantuan = $kelompok->total_nilai_bantuan_diterima ?? $kelompok->distribusiBantuans()->where('status_pemberian', 'Diterima Kelompok')->sum('harga_awal_total');
        $totalPenjualan = $kelompok->total_hasil_penjualan ?? $kelompok->allPelaporanHasils()->sum('total_penjualan');
        $avgPerkembangan = $kelompok->rata_rata_perkembangan_terakhir ?? $kelompok->allMonitoringPemanfaatans()->avg('persentase_perkembangan');

        // Ambil data stok terakhir (contoh, gunakan accessor jika ada)
        $estimasiTernak = $kelompok->estimasi_jumlah_ternak_saat_ini ?? null;
        $estimasiIkan = $kelompok->estimasi_jumlah_ikan_saat_ini ?? null;
        $stokBibit = $kelompok->jumlah_stok_bibit_terakhir ?? null;
        $satuanStokBibit = $kelompok->satuan_stok_bibit_terakhir ?? null;

        $fotosMonitoring = $kelompok->allMonitoringPemanfaatans->whereNotNull('foto_dokumentasi')->pluck('foto_dokumentasi');
        $fotosPelaporan = $kelompok->allPelaporanHasils->whereNotNull('foto_hasil')->pluck('foto_hasil');
        $galeriFoto = $fotosMonitoring->concat($fotosPelaporan)
                                ->unique()
                                ->filter(fn($path) => $path && Storage::disk('public')->exists($path))
                                ->take(8); // Ambil maks 8 foto misalnya

        // Ambil kebutuhan pengembangan
        // Ganti 'kebutuhan_pengembangan' dengan nama kolom sebenarnya jika ada
        $kebutuhan = $kelompok->kebutuhan_pengembangan ?? 'Informasi kebutuhan pengembangan belum tersedia. Silakan hubungi kontak terkait.';
        // ---------------------------------------------

        // --- Persiapan Data Chart Tren Penjualan (12 Bulan Terakhir) ---
        $endDate = now();
        $startDate = now()->subMonths(11)->startOfMonth();

        $salesData = $kelompok->allPelaporanHasils() // Gunakan relasi kelompok ke pelaporan
            ->select(
                DB::raw('YEAR(tanggal_pelaporan) as year'),
                DB::raw('MONTH(tanggal_pelaporan) as month'),
                DB::raw('SUM(total_penjualan) as aggregate'),
                // Pilih kolom foreign key perantara yang dibutuhkan HasManyThrough
                'distribusi_bantuans.kelompok_id' // <-- Pilih kolom ini
            )
            ->whereBetween('tanggal_pelaporan', [$startDate, $endDate])
            // Tambahkan kolom foreign key perantara ke GROUP BY
            ->groupBy('year', 'month', 'distribusi_bantuans.kelompok_id') // <-- Tambahkan ke group by
            ->orderBy('year', 'asc')->orderBy('month', 'asc')
            ->get();

        // Proses $salesData menjadi format $labels dan $data
        // Karena kita group by kelompok_id juga, kita perlu sum lagi di PHP
        $salesLabels = [];
        $salesValues = [];
        if ($startDate->lte($endDate)) {
             $period = Carbon::parse($startDate)->monthsUntil($endDate->addDay());
            foreach ($period as $date) {
                $year = $date->year; $month = $date->month;
                $salesLabels[] = $date->translatedFormat('M Y');
                // Sum aggregate dari hasil query untuk bulan & tahun yang cocok
                $monthlyTotal = $salesData->where('year', $year)->where('month', $month)->sum('aggregate');
                $salesValues[] = $monthlyTotal ?? 0;
            }
        }

        $salesTrendData = [
            'labels' => $salesLabels,
            'datasets' => [[
                'label' => 'Penjualan Bulanan (Rp)',
                'data' => $salesValues,
                'borderColor' => '#16A34A', // Warna success
                'backgroundColor' => 'rgba(22, 163, 74, 0.1)', // Warna area success transparan
                'fill' => true,
                'tension' => 0.2,
            ]],
        ];
        // ---------------------------------------------------------

        return view('public.kelompok-detail', compact(
            'kelompok', // Objek Kelompok utama (sudah di-load relasinya)
            'totalNilaiBantuan',
            'totalPenjualan',
            'avgPerkembangan',
            'estimasiTernak',
            'estimasiIkan',
            'stokBibit',
            'satuanStokBibit',
            'galeriFoto',
            'kebutuhan',
            'salesTrendData'
        ));
    }

    /**
     * Menampilkan daftar semua kelompok dengan pagination.
     */
    public function list(Request $request)
    {
        // ... (Logika method list seperti sebelumnya) ...
        $filterKecamatanId = $request->input('kecamatan');
        $filterJenis = $request->input('jenis');
        $search = $request->input('search');

        $kelompokQuery = Kelompok::query()
                        ->with(['desa.kecamatan', 'petugas'])
                        // Tambahkan withSum untuk sorting penjualan di list (opsional)
                        ->withSum(['allPelaporanHasils as total_penjualan_agg'], 'total_penjualan')
                        ->orderBy('nama_kelompok');

        if ($filterKecamatanId) { $kelompokQuery->whereHas('desa', fn(Builder $q) => $q->where('kecamatan_id', $filterKecamatanId)); }
        if ($filterJenis) { $kelompokQuery->where('jenis_kelompok', $filterJenis); }
        if ($search) { $kelompokQuery->where('nama_kelompok', 'like', "%{$search}%"); }

        $kelompoks = $kelompokQuery->paginate(12)->withQueryString();
        $kecamatans = Kecamatan::orderBy('nama_kecamatan')->pluck('nama_kecamatan', 'id');

        return view('public.kelompok-list', compact('kelompoks', 'kecamatans'));
    }
}
