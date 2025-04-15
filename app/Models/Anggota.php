<?php

namespace App\Models;

use App\Models\Kelompok;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Anggota extends Model
{
    use HasFactory;

    protected $fillable = [
        'kelompok_id',
        'nama_anggota',
        'nik',
        'alamat',
        'nomor_telepon',
        'is_ketua',
    ];

    protected $casts = [
        'is_ketua' => 'boolean',
    ];

    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class);
    }

    // Relasi jika anggota ini adalah ketua dari suatu kelompok (menggunakan foreign key ketua_id di tabel kelompoks)
    public function ketuaDariKelompok(): HasOne
    {
         return $this->hasOne(Kelompok::class, 'ketua_id');
    }

    // Observer untuk memastikan hanya satu ketua per kelompok jika menggunakan is_ketua
    protected static function booted()
    {
        static::saving(function ($anggota) {
            if ($anggota->is_ketua && $anggota->isDirty('is_ketua')) {
                // Set semua anggota lain di kelompok yang sama menjadi bukan ketua
                Anggota::where('kelompok_id', $anggota->kelompok_id)
                       ->where('id', '!=', $anggota->id)
                       ->update(['is_ketua' => false]);
                // Update juga ketua_id di tabel kelompok
                $anggota->kelompok()->update(['ketua_id' => $anggota->id]);
            } elseif (!$anggota->is_ketua && $anggota->isDirty('is_ketua')) {
                // Jika anggota ini tidak lagi menjadi ketua, null kan ketua_id di kelompok
                // Cek dulu apakah dia memang ketua sebelumnya
                $kelompok = $anggota->kelompok;
                if ($kelompok && $kelompok->ketua_id === $anggota->id) {
                    $kelompok->update(['ketua_id' => null]);
                }
            }
        });

        // Handle saat ketua dihapus
        static::deleting(function ($anggota) {
            if ($anggota->is_ketua) {
                $anggota->kelompok()->update(['ketua_id' => null]);
            }
        });
    }
}
