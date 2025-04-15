<?php

namespace App\Models;

use App\Models\User;
use App\Models\DistribusiBantuan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MonitoringPemanfaatan extends Model
{
    use HasFactory;

    protected $fillable = [
        'distribusi_bantuan_id',
        'user_id',
        'tanggal_monitoring',
        'deskripsi_pemanfaatan',
        'persentase_perkembangan',
        'foto_dokumentasi',
        'catatan_monitoring',

        // Kolom Detail Peternakan
        'jumlah_ternak_saat_ini',
        'jumlah_kelahiran',
        'jumlah_kematian',
        'kondisi_kesehatan_ternak',
        'kondisi_kandang',
        'pemanfaatan_pakan_ternak',

        // Kolom Detail Pertanian - Tanaman
        'luas_tanam_aktual',
        'kondisi_tanaman',
        'fase_pertumbuhan',
        'estimasi_hasil_panen',
        'satuan_hasil_panen',

        // Kolom Detail Alat/Sarana (Pertanian/Perikanan)
        'kondisi_alat',
        'pemanfaatan_alat',
        'frekuensi_penggunaan_alat',
        'satuan_frekuensi_alat',

        // Kolom Detail Perikanan - Budidaya
        'jumlah_ikan_hidup',
        'ukuran_ikan_rata2',
        'kondisi_air_kolam',
        'pemberian_pakan_ikan',

        // Kolom Detail Perikanan - Tangkap
        'estimasi_hasil_tangkapan',
        'satuan_hasil_tangkapan',

        // Field baru
        'jumlah_stok_bibit_disimpan',
        'satuan_stok_bibit',

        // Field baru untuk perubahan stok
        'jumlah_kelahiran_sejak_terakhir',
        'jumlah_kematian_sejak_terakhir',
        'jumlah_dikonsumsi_sejak_terakhir',
        'jumlah_rusak_hilang_sejak_terakhir',
    ];

    protected $casts = [
        'tanggal_monitoring' => 'date',
        'persentase_perkembangan' => 'integer',
        // Casts untuk kolom baru
        'jumlah_ternak_saat_ini' => 'integer',
        'jumlah_kelahiran' => 'integer',
        'jumlah_kematian' => 'integer',
        'kondisi_kesehatan_ternak' => 'string', // Enum
        'kondisi_kandang' => 'string', // Enum
        'luas_tanam_aktual' => 'decimal:2',
        'kondisi_tanaman' => 'string', // Enum
        'estimasi_hasil_panen' => 'decimal:2',
        'kondisi_alat' => 'string', // Enum
        'frekuensi_penggunaan_alat' => 'decimal:2',
        'jumlah_ikan_hidup' => 'integer',
        'kondisi_air_kolam' => 'string', // Enum
        'estimasi_hasil_tangkapan' => 'decimal:2',
        // Casts baru
        'jumlah_stok_bibit_disimpan' => 'decimal:2',
        'satuan_stok_bibit' => 'string',
        // Casts baru
        'jumlah_kelahiran_sejak_terakhir' => 'integer',
        'jumlah_kematian_sejak_terakhir' => 'integer',
        'jumlah_dikonsumsi_sejak_terakhir' => 'integer',
        'jumlah_rusak_hilang_sejak_terakhir' => 'integer',
    ];

    public function distribusiBantuan(): BelongsTo
    {
        // Eager load relasi bantuan untuk cek jenisnya nanti
    return $this->belongsTo(DistribusiBantuan::class)->with('bantuan');
    }

    public function user(): BelongsTo // Petugas yg memonitor
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

        // Helper untuk mendapatkan jenis bantuan terkait (opsional tapi berguna)
    public function getJenisBantuanAttribute(): ?string
    {
        return $this->distribusiBantuan?->bantuan?->jenis_bantuan;
    }
}
