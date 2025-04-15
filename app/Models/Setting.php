<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Setting extends Model
{
    use HasFactory;
    protected $fillable = ['key', 'value'];
    // Tidak perlu timestamps jika tidak ingin melacak kapan setting diubah
    // public $timestamps = false;
    // Set primary key jika bukan 'id' (tapi di sini 'id')
    // protected $primaryKey = 'key';
    // public $incrementing = false;
    // protected $keyType = 'string';
}
