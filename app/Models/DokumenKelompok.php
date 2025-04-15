<?php

namespace App\Models; // <-- Pastikan namespace ini benar

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DokumenKelompok extends Model // <-- Pastikan nama class benar
{
    use HasFactory;

    protected $table = 'dokumen_kelompoks'; // Nama tabel

    // Kolom yang boleh diisi massal
    protected $fillable = [
        'kelompok_id',
        'user_id',
        'nama_dokumen',
        'kategori',
        'deskripsi',
        'path_file',
        'tipe_file',
        'ukuran_file',
    ];

    // Tipe data casting
    protected $casts = [
        'ukuran_file' => 'integer',
        'created_at' => 'datetime', // Otomatis oleh Laravel biasanya
        'updated_at' => 'datetime',
    ];

    // Accessor URL (jika sudah ditambahkan)
    public function getUrlAttribute(): ?string
    {
        if ($this->path_file && Storage::disk('public')->exists($this->path_file)) {
            return Storage::disk('public')->url($this->path_file);
        }
        return null;
    }

    // Relasi ke Kelompok
    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class);
    }

    // Relasi ke User (Pengupload)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Model Event Hapus File (jika sudah ditambahkan)
    protected static function booted(): void
    {
        static::deleted(function (DokumenKelompok $dokumen) {
            if ($dokumen->path_file) {
                Storage::disk('public')->delete($dokumen->path_file);
            }
        });
    }
}
