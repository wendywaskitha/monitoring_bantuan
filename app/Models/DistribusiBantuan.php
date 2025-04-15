<?php

namespace App\Models;

use App\Models\User;
use App\Models\Bantuan;
use App\Models\Kelompok;
use App\Models\PelaporanHasil;
use App\Models\MonitoringPemanfaatan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DistribusiBantuan extends Model
{
    use HasFactory;

    protected $fillable = [
        'kelompok_id',
        'bantuan_id',
        'user_id',
        'jumlah',
        'satuan',
        'harga_awal_total', // Tambahkan ini
        'tanggal_distribusi',
        'status_pemberian',
        'catatan_distribusi',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'harga_awal_total' => 'decimal:2',
        'tanggal_distribusi' => 'date',
        'status_pemberian' => 'string',
    ];

    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class);
    }

    public function bantuan(): BelongsTo
    {
        return $this->belongsTo(Bantuan::class);
    }

    public function user(): BelongsTo // Petugas yg mencatat
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke monitoring pemanfaatan untuk distribusi ini
    public function monitoringPemanfaatans(): HasMany
    {
        return $this->hasMany(MonitoringPemanfaatan::class);
    }

    // Relasi ke pelaporan hasil untuk distribusi ini
    public function pelaporanHasils(): HasMany
    {
        return $this->hasMany(PelaporanHasil::class);
    }

    // Model event untuk menghitung harga_awal_total secara otomatis
    protected static function booted(): void // Tambahkan return type hint void
    {
        static::creating(function (DistribusiBantuan $distribusi) {
            if ($distribusi->bantuan_id && $distribusi->jumlah > 0) {
                $bantuan = Bantuan::find($distribusi->bantuan_id);
                if ($bantuan) {
                    $distribusi->harga_awal_total = $distribusi->jumlah * $bantuan->perkiraan_harga_awal;
                    // Jika satuan tidak diisi saat create, ambil dari master bantuan
                    if (empty($distribusi->satuan)) {
                         $distribusi->satuan = $bantuan->satuan;
                    }
                } else {
                    // Handle jika bantuan tidak ditemukan (meskipun seharusnya tidak terjadi karena foreign key)
                    $distribusi->harga_awal_total = 0;
                }
            } else {
                $distribusi->harga_awal_total = 0;
            }
        });

        static::updating(function (DistribusiBantuan $distribusi) {
            // Hitung ulang hanya jika jumlah atau bantuan_id berubah
            if ($distribusi->isDirty('jumlah') || $distribusi->isDirty('bantuan_id')) {
                if ($distribusi->bantuan_id && $distribusi->jumlah > 0) {
                    $bantuan = Bantuan::find($distribusi->bantuan_id);
                    if ($bantuan) {
                        $distribusi->harga_awal_total = $distribusi->jumlah * $bantuan->perkiraan_harga_awal;
                         // Jika satuan berubah bersamaan dengan bantuan_id, update juga
                         if ($distribusi->isDirty('bantuan_id') && empty($distribusi->getOriginal('satuan'))) {
                             $distribusi->satuan = $bantuan->satuan;
                         }
                    } else {
                        $distribusi->harga_awal_total = 0;
                    }
                } else {
                    $distribusi->harga_awal_total = 0;
                }
            }
        });
    }

}
