<?php

namespace App\Models;

use App\Models\Desa;
use App\Models\User;
use App\Models\Kelompok;
use App\Models\Kecamatan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PendaftaranKelompok extends Model
{
    use HasFactory;

    protected $table = 'pendaftaran_kelompoks';

    protected $fillable = [
        'nama_kelompok_diajukan',
        'jenis_kelompok',
        'kecamatan_id',
        'desa_id',
        'alamat_singkat',
        'perkiraan_jumlah_anggota',
        'deskripsi_kegiatan',
        'nama_kontak',
        'telepon_kontak',
        'email_kontak',
        'dokumen_pendukung_path',
        'dokumen_pendukung_nama_asli',
        'status',
        'catatan_verifikasi',
        'verifikator_id',
        'diverifikasi_at',
        'kelompok_baru_id',
    ];

    protected $casts = [
        'perkiraan_jumlah_anggota' => 'integer',
        'status' => 'string', // Enum
        'diverifikasi_at' => 'datetime',
        'kelompok_baru_id' => 'integer',
    ];

    // Accessor untuk URL dokumen pendukung
    public function getDokumenPendukungUrlAttribute(): ?string
    {
        if ($this->dokumen_pendukung_path && Storage::disk('public')->exists($this->dokumen_pendukung_path)) {
            return Storage::disk('public')->url($this->dokumen_pendukung_path);
        }
        return null;
    }

    // Relasi ke Kecamatan
    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }

    // Relasi ke Desa
    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }

    // Relasi ke User (Verifikator)
    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifikator_id');
    }

    /**
     * Relasi ke Kelompok yang dibuat dari pendaftaran ini.
     */
    public function kelompokBaru(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class, 'kelompok_baru_id');
    }

    // Model Event untuk hapus file jika record dihapus
    protected static function booted(): void
    {
        static::deleted(function (PendaftaranKelompok $pendaftaran) {
            if ($pendaftaran->dokumen_pendukung_path) {
                Storage::disk('public')->delete($pendaftaran->dokumen_pendukung_path);
            }
        });
    }
}
