<?php

namespace App\Models;

use App\Models\Kelompok;
use App\Models\Kecamatan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Desa extends Model
{
    use HasFactory;

    protected $fillable = ['kecamatan_id', 'nama_desa'];

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }

    public function kelompoks(): HasMany
    {
        return $this->hasMany(Kelompok::class);
    }
}
