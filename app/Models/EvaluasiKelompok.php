<?php

namespace App\Models;

use App\Models\User;
use App\Models\Kelompok;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EvaluasiKelompok extends Model
{
    use HasFactory;

    protected $table = 'evaluasi_kelompoks'; // Nama tabel eksplisit
    protected $primaryKey = 'id'; // Default, bisa ditulis eksplisit
    public $incrementing = true; // Default

    protected $fillable = [
        'kelompok_id',
        'user_id',
        'tanggal_evaluasi',
        'periode_omset',
        'nilai_aset',
        'omset_periode',
        'catatan_evaluasi',
    ];

    protected $casts = [
        'tanggal_evaluasi' => 'date',
        'nilai_aset' => 'decimal:2',
        'omset_periode' => 'decimal:2',
    ];

    /**
     * Relasi ke Kelompok.
     */
    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class);
    }

    /**
     * Relasi ke User (Petugas).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
