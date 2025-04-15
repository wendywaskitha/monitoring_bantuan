<?php

namespace App\Models;

use App\Models\User;
use App\Models\DistribusiBantuan;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PelaporanHasil extends Model
{
    use HasFactory;

        protected $fillable = [
            'distribusi_bantuan_id', 'user_id', 'tanggal_pelaporan',
            // Kolom hasil spesifik
            'hasil_panen_kg', 'hasil_panen_ton', 'kualitas_hasil_panen', 'jumlah_dikonsumsi', 'satuan_konsumsi',
            'jumlah_ternak_terjual', 'jenis_produk_ternak_terjual', 'berat_total_terjual_kg', 'volume_produk_liter',
            'jumlah_tangkapan_kg', 'jenis_ikan_dominan', 'jumlah_diolah_kg',
            // Kolom finansial baru
            'jumlah_dijual', 'satuan_dijual', 'harga_jual_per_satuan_dijual', 'pendapatan_lain',
            'total_penjualan', // Tetap ada, tapi mungkin tidak diisi langsung dari form jika dihitung otomatis
            // Kolom lain
            'foto_hasil', 'catatan_hasil',
        ];

        protected $casts = [
            'tanggal_pelaporan' => 'date',
            // Casts hasil spesifik
            'hasil_panen_kg' => 'decimal:2', 'hasil_panen_ton' => 'decimal:2', 'kualitas_hasil_panen' => 'string',
            'jumlah_dikonsumsi' => 'decimal:2',
            'jumlah_ternak_terjual' => 'integer', 'berat_total_terjual_kg' => 'decimal:2', 'volume_produk_liter' => 'decimal:2',
            'jumlah_tangkapan_kg' => 'decimal:2', 'jumlah_diolah_kg' => 'decimal:2',
            // Casts finansial baru
            'jumlah_dijual' => 'decimal:2',
            'harga_jual_per_satuan_dijual' => 'decimal:2',
            'pendapatan_lain' => 'decimal:2',
            'total_penjualan' => 'decimal:2',
        ];

        public function distribusiBantuan(): BelongsTo
        {
            return $this->belongsTo(DistribusiBantuan::class);
        }

        public function user(): BelongsTo // Petugas yg melapor
        {
            return $this->belongsTo(User::class);
        }

        protected static function booted(): void
        {
            static::saving(function (PelaporanHasil $pelaporan) {
                // --- Logging Detail ---
                Log::info('--- Saving PelaporanHasil Event ---');
                Log::info('Record ID (if exists): ' . $pelaporan->id);
                Log::info('Jumlah Dijual (Raw): ' . var_export($pelaporan->jumlah_dijual, true));
                Log::info('Harga Jual/Satuan (Raw): ' . var_export($pelaporan->harga_jual_per_satuan_dijual, true));
                Log::info('Pendapatan Lain (Raw): ' . var_export($pelaporan->pendapatan_lain, true));
                Log::info('Total Penjualan (Before Calc): ' . var_export($pelaporan->total_penjualan, true));
                // ----------------------

                $jumlah = $pelaporan->jumlah_dijual;
                $harga = $pelaporan->harga_jual_per_satuan_dijual;
                $lain = $pelaporan->pendapatan_lain ?? 0; // Default 0 jika null

                // Cek apakah nilai untuk perhitungan otomatis valid
                $canCalculate = is_numeric($jumlah) && is_numeric($harga) && $jumlah >= 0 && $harga >= 0;
                Log::info('Can Calculate Automatically? ' . ($canCalculate ? 'Yes' : 'No'));

                if ($canCalculate) {
                    $subTotal = (float)$jumlah * (float)$harga;
                    $pendapatanLainFloat = is_numeric($lain) ? (float)$lain : 0;
                    $calculatedTotal = $subTotal + $pendapatanLainFloat;

                    // Set total penjualan dengan hasil perhitungan
                    $pelaporan->total_penjualan = $calculatedTotal;
                    Log::info("Calculated Total Set: {$pelaporan->total_penjualan}");

                } elseif ($pelaporan->isDirty('total_penjualan') && is_numeric($pelaporan->total_penjualan)) {
                    // Jika tidak bisa dihitung otomatis TAPI total_penjualan diubah manual (dan valid), biarkan.
                    Log::info("Using Manually Input Total Penjualan: {$pelaporan->total_penjualan}");
                    // Tidak perlu assignment ulang, nilainya sudah ada di $pelaporan

                } else {
                     // Jika tidak bisa dihitung & tidak diinput manual (atau input tidak valid), set 0
                     $pelaporan->total_penjualan = 0;
                     Log::info("Setting Total Penjualan to 0 (cannot calculate, no valid manual input)");
                }

                // Pastikan tidak negatif
                if ($pelaporan->total_penjualan < 0) {
                    $pelaporan->total_penjualan = 0;
                }
                Log::info('Total Penjualan (Final): ' . var_export($pelaporan->total_penjualan, true));
                Log::info('--- End Saving PelaporanHasil ---');
            });
        }
}
