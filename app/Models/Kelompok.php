<?php

namespace App\Models;

use App\Models\Desa;
use App\Models\User;
use App\Models\Anggota;
use App\Models\DokumenKelompok;
use App\Models\EvaluasiKelompok;
use App\Models\DistribusiBantuan;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Kelompok extends Model
{
    use HasFactory;

    protected $fillable = [
        'desa_id',
        'user_id',
        'nama_kelompok',
        'ketua_id', // Pastikan ini ada
        'jenis_kelompok',
        'alamat_sekretariat',
        'tanggal_dibentuk',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'tanggal_dibentuk' => 'date',
        'jenis_kelompok' => 'string', // Enum cast handled automatically in newer Laravel, but explicit is fine
        'latitude' => 'float', // Atau 'decimal:8'
        'longitude' => 'float', // Atau 'decimal:8'
    ];



    /**
     * Relasi ke User (Petugas Penanggung Jawab).
     */
    public function petugas(): BelongsTo
    {
        // Gunakan nama relasi 'petugas' agar tidak bentrok jika ada relasi 'user' lain
        return $this->belongsTo(User::class, 'user_id');
    }

    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }

    // Relasi ke Anggota (semua anggota)
    public function anggotas(): HasMany
    {
        return $this->hasMany(Anggota::class);
    }

    // Relasi spesifik ke Anggota yang menjadi ketua (menggunakan foreign key ketua_id)
    public function ketua(): BelongsTo // BelongsTo karena foreign key ada di tabel ini
    {
        return $this->belongsTo(Anggota::class, 'ketua_id');
    }

    // Alternatif: Relasi ke Anggota yang is_ketua = true (jika tidak pakai ketua_id)
    // public function ketua(): HasOne
    // {
    //     return $this->hasOne(Anggota::class)->where('is_ketua', true);
    // }

    // Relasi ke Distribusi Bantuan yang diterima kelompok ini
    public function distribusiBantuans(): HasMany
    {
        return $this->hasMany(DistribusiBantuan::class);
    }

    /**
     * Relasi ke riwayat evaluasi kelompok.
     */
    public function evaluasiKelompoks(): HasMany
    {
        return $this->hasMany(EvaluasiKelompok::class)->orderBy('tanggal_evaluasi', 'desc'); // Urutkan terbaru dulu
    }

    /**
     * Relasi ke dokumen-dokumen yang terkait dengan kelompok ini.
     */
    public function dokumenKelompoks(): HasMany
    {
        // Relasi HasMany ke model DokumenKelompok
        // Laravel akan otomatis mengasumsikan foreign key adalah 'kelompok_id'
        return $this->hasMany(DokumenKelompok::class)->latest(); // Urutkan terbaru dulu
    }
    // ---------------------------

    /**
     * Relasi untuk mendapatkan semua record monitoring pemanfaatan
     * melalui tabel distribusi_bantuans.
     */
    public function allMonitoringPemanfaatans(): HasManyThrough
    {
        // Argument:
        // 1. Model tujuan (MonitoringPemanfaatan)
        // 2. Model perantara (DistribusiBantuan)
        // 3. Foreign key di model perantara (distribusi_bantuans.kelompok_id -> merujuk ke Kelompok)
        // 4. Foreign key di model tujuan (monitoring_pemanfaatans.distribusi_bantuan_id -> merujuk ke DistribusiBantuan)
        // 5. Local key di model ini (kelompoks.id)
        // 6. Local key di model perantara (distribusi_bantuans.id)
        return $this->hasManyThrough(
            MonitoringPemanfaatan::class,
            DistribusiBantuan::class,
            'kelompok_id', // Foreign key on distribusi_bantuans table...
            'distribusi_bantuan_id', // Foreign key on monitoring_pemanfaatans table...
            'id', // Local key on kelompoks table...
            'id' // Local key on distribusi_bantuans table...
        );
    }

    /**
     * Relasi untuk mendapatkan semua record pelaporan hasil
     * melalui tabel distribusi_bantuans.
     */
    public function allPelaporanHasils(): HasManyThrough
    {
         return $this->hasManyThrough(
            PelaporanHasil::class,
            DistribusiBantuan::class,
            'kelompok_id', // Foreign key on distribusi_bantuans table...
            'distribusi_bantuan_id', // Foreign key on pelaporan_hasils table...
            'id', // Local key on kelompoks table...
            'id' // Local key on distribusi_bantuans table...
        );
    }

    // --- Accessor Perhitungan Stok Dinamis ---

    /**
     * Menghitung estimasi jumlah ternak saat ini.
     * Membutuhkan kolom perubahan di monitoring_pemanfaatans
     * dan jumlah_ternak_terjual di pelaporan_hasils.
     */
    public function getEstimasiJumlahTernakSaatIniAttribute(): int
    {
        // Filter bantuan ternak (sesuaikan jika perlu)
        $bantuanTernakIds = $this->distribusiBantuans()
            ->where('status_pemberian', 'Diterima Kelompok')
            ->whereHas('bantuan', fn(Builder $q) => $q->where('jenis_bantuan', 'Peternakan'))
            ->pluck('bantuan_id'); // Ambil ID jenis bantuan ternak yg diterima

        $totalBantuan = $this->distribusiBantuans()
            ->whereIn('bantuan_id', $bantuanTernakIds) // Hanya hitung bantuan ternak
            ->where('status_pemberian', 'Diterima Kelompok')
            ->sum('jumlah') ?? 0;

        // Hitung mutasi dari monitoring yang terkait BANTUAN TERNAK saja
        $totalKelahiran = $this->allMonitoringPemanfaatans()
            ->whereHas('distribusiBantuan.bantuan', fn(Builder $q) => $q->where('jenis_bantuan', 'Peternakan'))
            ->sum('jumlah_kelahiran_sejak_terakhir') ?? 0;

        $totalKematian = $this->allMonitoringPemanfaatans()
             ->whereHas('distribusiBantuan.bantuan', fn(Builder $q) => $q->where('jenis_bantuan', 'Peternakan'))
            ->sum('jumlah_kematian_sejak_terakhir') ?? 0;

        $totalKonsumsiMonitoring = $this->allMonitoringPemanfaatans()
             ->whereHas('distribusiBantuan.bantuan', fn(Builder $q) => $q->where('jenis_bantuan', 'Peternakan'))
            ->sum('jumlah_dikonsumsi_sejak_terakhir') ?? 0;

        $totalTerjual = $this->allPelaporanHasils()
            ->whereHas('distribusiBantuan.bantuan', fn(Builder $q) => $q->where('jenis_bantuan', 'Peternakan'))
            // ->sum('jumlah_ternak_terjual') ?? 0; // Jika ada kolom spesifik
            ->where('satuan_dijual', 'like', '%ekor%') // Jika pakai kolom generik
            ->sum('jumlah_dijual') ?? 0;

        $estimasi = ($totalBantuan + $totalKelahiran) - ($totalKematian + $totalTerjual + $totalKonsumsiMonitoring);
        return max(0, (int)$estimasi);
    }

     /**
     * Menghitung estimasi jumlah ikan hidup saat ini (budidaya).
     */
    public function getEstimasiJumlahIkanSaatIniAttribute(): int
    {
        // Filter bantuan bibit ikan
        $bantuanIkanIds = $this->distribusiBantuans()
            ->where('status_pemberian', 'Diterima Kelompok')
            ->whereHas('bantuan', fn(Builder $q) => $q->where('jenis_bantuan', 'Perikanan')
                ->where(function($q2) {
                    $q2->where('nama_bantuan', 'like', '%bibit%')
                       ->orWhere('nama_bantuan', 'like', '%benih%')
                       ->orWhere('nama_bantuan', 'like', '%nener%'); // Tambahkan nener jika relevan
                })
            )->pluck('bantuan_id');

        // Jika tidak pernah dapat bantuan bibit ikan, return 0
        if ($bantuanIkanIds->isEmpty()) {
            return 0;
        }

        // Total bantuan bibit ikan diterima (dalam ekor)
        $totalBantuan = $this->distribusiBantuans()
            ->whereIn('bantuan_id', $bantuanIkanIds)
            ->where('status_pemberian', 'Diterima Kelompok')
            ->sum('jumlah') ?? 0;

        // Asumsi 'jumlah_kelahiran_sejak_terakhir' dipakai untuk penambahan/pertumbuhan ikan
        $totalPenambahan = $this->allMonitoringPemanfaatans()
             ->whereHas('distribusiBantuan', fn(Builder $q) => $q->whereIn('bantuan_id', $bantuanIkanIds))
            ->sum('jumlah_kelahiran_sejak_terakhir') ?? 0;

        // Total kematian ikan tercatat di monitoring
        $totalKematian = $this->allMonitoringPemanfaatans()
             ->whereHas('distribusiBantuan', fn(Builder $q) => $q->whereIn('bantuan_id', $bantuanIkanIds))
            ->sum('jumlah_kematian_sejak_terakhir') ?? 0;

        // --- PERBAIKAN: Hitung total ikan terjual (dalam Ekor) dari pelaporan hasil ---
        $totalTerjual = $this->allPelaporanHasils()
             ->whereHas('distribusiBantuan', fn(Builder $q) => $q->whereIn('bantuan_id', $bantuanIkanIds))
             // Filter pelaporan yang satuannya 'Ekor' (atau variasinya)
             ->where(function (Builder $query) {
                 $query->where('satuan_dijual', 'like', 'ekor')
                       ->orWhere('satuan_dijual', 'like', 'Ekor');
                 // Tambahkan variasi lain jika perlu
             })
             ->sum('jumlah_dijual') ?? 0; // Jumlahkan kolom 'jumlah_dijual'
        // --------------------------------------------------------------------------

        // Total ikan dikonsumsi tercatat di monitoring
        $totalKonsumsi = $this->allMonitoringPemanfaatans()
             ->whereHas('distribusiBantuan', fn(Builder $q) => $q->whereIn('bantuan_id', $bantuanIkanIds))
            ->sum('jumlah_dikonsumsi_sejak_terakhir') ?? 0;

        // Hitung estimasi akhir
        $estimasi = ($totalBantuan + $totalPenambahan) - ($totalKematian + $totalTerjual + $totalKonsumsi);

        // Pastikan tidak negatif
        return max(0, (int)$estimasi);
    }

    // --- Pastikan accessor lain yang relevan juga ada dan benar ---
    public function getTotalBantuanIkanDiterimaAttribute(): ?float {
        return $this->distribusiBantuans()
           ->where('status_pemberian', 'Diterima Kelompok')
           ->whereHas('bantuan', fn(Builder $q) => $q->where('jenis_bantuan', 'Perikanan')
               ->where(function($q2) { /* ... filter bibit ... */ })
           )
           ->sum('jumlah');
   }

    // Accessor total bantuan diterima (sudah ada)
    public function getTotalNilaiBantuanDiterimaAttribute(): float
    {
        // Pastikan hanya menghitung yang statusnya diterima
        return $this->distribusiBantuans()
                    ->where('status_pemberian', 'Diterima Kelompok')
                    ->sum('harga_awal_total');
    }

    // Accessor untuk total hasil penjualan (baru)
    // public function getTotalHasilPenjualanAttribute(): float
    // {
    //     // Gunakan relasi HasManyThrough untuk sum
    //     return $this->allPelaporanHasils()->sum('total_penjualan');
    // }

    public function getTotalHasilPenjualanAttribute(): float
    {
        // Perlu select kelompok_id dan group by kelompok_id juga
        $result = $this->allPelaporanHasils()
            ->selectRaw('SUM(total_penjualan) as total, distribusi_bantuans.kelompok_id') // Pilih juga kelompok_id
            ->groupBy('distribusi_bantuans.kelompok_id') // Group by kelompok_id
            ->first(); // Ambil hasil untuk kelompok ini

        return (float) ($result?->total ?? 0); // Ambil nilai total
    }

    // Accessor untuk rata-rata persentase perkembangan terakhir (baru)
    public function getRataRataPerkembanganTerakhirAttribute(): ?float
    {
        // Ambil persentase dari monitoring terbaru untuk setiap item distribusi
        // Ini agak kompleks, mungkin lebih mudah mengambil rata-rata semua monitoring?
        // Atau rata-rata dari X monitoring terakhir?

        // Pendekatan 1: Rata-rata semua monitoring untuk kelompok ini
        $avg = $this->allMonitoringPemanfaatans()->avg('persentase_perkembangan');

        // Pendekatan 2: Rata-rata monitoring terakhir per distribusi (lebih kompleks)
        // $latestMonitoring = $this->allMonitoringPemanfaatans()
        //     ->selectRaw('MAX(tanggal_monitoring) as last_date, distribusi_bantuan_id')
        //     ->groupBy('distribusi_bantuan_id');
        // $avg = MonitoringPemanfaatan::joinSub($latestMonitoring, 'latest', function ($join) {
        //         $join->on('monitoring_pemanfaatans.distribusi_bantuan_id', '=', 'latest.distribusi_bantuan_id');
        //         $join->on('monitoring_pemanfaatans.tanggal_monitoring', '=', 'latest.last_date');
        //     })
        //     ->avg('persentase_perkembangan');

        return $avg !== null ? round($avg, 1) : null;
    }

    // --- Accessor Baru untuk Stok Terakhir ---

    /**
     * Mendapatkan jumlah ternak terakhir dari monitoring.
     */
    public function getJumlahTernakTerakhirAttribute(): ?int
    {
        // Cari monitoring terbaru yg jenis bantuannya Peternakan & punya nilai jumlah_ternak_saat_ini
        $latestMonitoring = $this->allMonitoringPemanfaatans() // Gunakan relasi HasManyThrough
                                ->whereNotNull('jumlah_ternak_saat_ini')
                                ->latest('tanggal_monitoring')
                                ->first();
        return $latestMonitoring?->jumlah_ternak_saat_ini;
    }

    /**
     * Mendapatkan jumlah ikan hidup terakhir dari monitoring.
     */
    public function getJumlahIkanHidupTerakhirAttribute(): ?int
    {
        $latestMonitoring = $this->allMonitoringPemanfaatans()
                                ->whereNotNull('jumlah_ikan_hidup')
                                ->latest('tanggal_monitoring')
                                ->first();
        return $latestMonitoring?->jumlah_ikan_hidup;
    }

     /**
     * Mendapatkan jumlah stok bibit disimpan terakhir dari monitoring.
     */
    public function getJumlahStokBibitTerakhirAttribute(): ?float // Bisa float
    {
        $latestMonitoring = $this->allMonitoringPemanfaatans()
                                ->whereNotNull('jumlah_stok_bibit_disimpan')
                                ->latest('tanggal_monitoring')
                                ->first();
        return $latestMonitoring?->jumlah_stok_bibit_disimpan;
    }

     /**
     * Mendapatkan satuan stok bibit disimpan terakhir.
     */
    public function getSatuanStokBibitTerakhirAttribute(): ?string
    {
        $latestMonitoring = $this->allMonitoringPemanfaatans()
                                ->whereNotNull('jumlah_stok_bibit_disimpan') // Cari yg ada jumlahnya
                                ->latest('tanggal_monitoring')
                                ->first();
        return $latestMonitoring?->satuan_stok_bibit;
    }

    // -----------------------------------------

    // --- Accessor BARU untuk Total Bantuan Awal per Jenis ---

    /**
     * Menghitung total jumlah bantuan ternak yang diterima.
     */
    public function getTotalBantuanTernakDiterimaAttribute(): ?float // Bisa float jika ada desimal
    {
        return $this->distribusiBantuans()
            ->where('status_pemberian', 'Diterima Kelompok')
            // Filter berdasarkan jenis bantuan Peternakan
            ->whereHas('bantuan', fn(Builder $q) => $q->where('jenis_bantuan', 'Peternakan')
                // Opsional: Filter lebih spesifik jika perlu (misal hanya bibit ternak)
                // ->where(function($q2) {
                //     $q2->where('nama_bantuan', 'like', '%bibit%')
                //        ->orWhere('nama_bantuan', 'like', '%indukan%');
                // })
            )
            ->sum('jumlah'); // Jumlahkan kolom 'jumlah'
    }

    // /**
    //  * Menghitung total jumlah bantuan bibit ikan yang diterima.
    //  */
    // public function getTotalBantuanIkanDiterimaAttribute(): ?float
    // {
    //     return $this->distribusiBantuans()
    //         ->where('status_pemberian', 'Diterima Kelompok')
    //         ->whereHas('bantuan', fn(Builder $q) => $q->where('jenis_bantuan', 'Perikanan')
    //             // Filter hanya bibit/benih ikan
    //             ->where(function($q2) {
    //                 $q2->where('nama_bantuan', 'like', '%bibit%')
    //                    ->orWhere('nama_bantuan', 'like', '%benih%')
    //                    ->orWhere('nama_bantuan', 'like', '%nener%');
    //             })
    //         )
    //         ->sum('jumlah');
    // }

     /**
     * Menghitung total jumlah bantuan bibit/benih pertanian yang diterima.
     * Mengembalikan array [jumlah, satuan] karena satuan bisa berbeda.
     */
    public function getTotalBantuanBibitPertanianDiterimaAttribute(): ?array
    {
        // Ambil distribusi bantuan bibit/benih pertanian pertama untuk mendapatkan satuan umum
        $distribusiPertama = $this->distribusiBantuans()
            ->where('status_pemberian', 'Diterima Kelompok')
            ->whereHas('bantuan', fn(Builder $q) => $q->where('jenis_bantuan', 'Pertanian')
                ->where(function($q2) {
                    $q2->where('nama_bantuan', 'like', '%bibit%')
                       ->orWhere('nama_bantuan', 'like', '%benih%');
                })
            )
            ->first();

        if (!$distribusiPertama) {
            return null; // Tidak ada bantuan bibit pertanian
        }

        $satuanUmum = $distribusiPertama->satuan; // Ambil satuan dari record pertama

        // Hitung total jumlah dengan satuan yang sama (asumsi satuan konsisten per jenis bibit)
        $totalJumlah = $this->distribusiBantuans()
            ->where('status_pemberian', 'Diterima Kelompok')
            ->whereHas('bantuan', fn(Builder $q) => $q->where('jenis_bantuan', 'Pertanian')
                 ->where(function($q2) {
                    $q2->where('nama_bantuan', 'like', '%bibit%')
                       ->orWhere('nama_bantuan', 'like', '%benih%');
                })
            )
            ->where('satuan', $satuanUmum) // Hanya jumlahkan yang satuannya sama
            ->sum('jumlah');

        return [
            'jumlah' => $totalJumlah,
            'satuan' => $satuanUmum,
        ];
    }

    // ----------------------------------------------------
}
