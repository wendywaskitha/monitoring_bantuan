<?php

namespace App\Models;

use App\Models\DistribusiBantuan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bantuan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_bantuan',
        'jenis_bantuan',
        'satuan',
        'deskripsi',
        'perkiraan_harga_awal',
    ];

    protected $casts = [
        'perkiraan_harga_awal' => 'decimal:2',
        'jenis_bantuan' => 'string',
    ];

    // Relasi ke Distribusi Bantuan (jenis bantuan ini didistribusikan ke mana saja)
    public function distribusiBantuans(): HasMany
    {
        return $this->hasMany(DistribusiBantuan::class);
    }
}
