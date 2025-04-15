<?php
namespace App\Models;
use App\Models\User;
use App\Models\Kelompok;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PesanKontak extends Model
{
    use HasFactory;
    protected $table = 'pesan_kontaks';
    protected $fillable = [
        'nama_lengkap', 'email', 'nomor_telepon', 'nama_instansi',
        'subjek', 'pesan', 'sudah_dibaca', 'kelompok_id', // <-- Tambahkan ini
        'jenis_kelompok', // <-- Tambahkan ini
    ];
    protected $casts = [
        'sudah_dibaca' => 'boolean',
    ];

    public function kelompok()
    {
        return $this->belongsTo(Kelompok::class);
    }

    public function verifikator()
    {
        return $this->belongsTo(User::class, 'verifikator_id');
    }
}
