<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pelaporan_hasils', function (Blueprint $table) {
            // Hapus kolom lama jika ada dan ingin diganti
            // if (Schema::hasColumn('pelaporan_hasils', 'jumlah_hasil')) {
            //     $table->dropColumn('jumlah_hasil');
            // }
            // if (Schema::hasColumn('pelaporan_hasils', 'satuan_hasil')) {
            //     $table->dropColumn('satuan_hasil');
            // }
            if (Schema::hasColumn('pelaporan_hasils', 'harga_jual_per_satuan')) {
                // Ubah nama atau drop jika tidak dipakai
                // $table->renameColumn('harga_jual_per_satuan', 'harga_jual_per_satuan_lama');
                $table->dropColumn('harga_jual_per_satuan');
            }

           // Tambah kolom baru setelah tanggal_pelaporan (atau posisi lain)
           $table->decimal('jumlah_dijual', 12, 2)->nullable()->after('tanggal_pelaporan');
           $table->string('satuan_dijual', 50)->nullable()->after('jumlah_dijual');
           $table->decimal('harga_jual_per_satuan_dijual', 15, 2)->nullable()->after('satuan_dijual');
           $table->decimal('pendapatan_lain', 15, 2)->nullable()->default(0)->after('harga_jual_per_satuan_dijual');

           // Pastikan total_penjualan ada dan not null default 0
           if (Schema::hasColumn('pelaporan_hasils', 'total_penjualan')) {
                $table->decimal('total_penjualan', 15, 2)->nullable(false)->default(0)->change();
           } else {
                $table->decimal('total_penjualan', 15, 2)->nullable(false)->default(0)->after('pendapatan_lain');
           }

           // Tambahkan kolom spesifik hasil lainnya (seperti rencana sebelumnya)
           // Pertanian
           $table->decimal('hasil_panen_kg', 12, 2)->nullable()->after('total_penjualan');
           $table->decimal('hasil_panen_ton', 12, 2)->nullable()->after('hasil_panen_kg');
           $table->enum('kualitas_hasil_panen', ['Premium', 'Baik', 'Sedang', 'Kurang'])->nullable()->after('hasil_panen_ton');
           $table->decimal('jumlah_dikonsumsi', 12, 2)->nullable()->after('kualitas_hasil_panen');
           $table->string('satuan_konsumsi')->nullable()->after('jumlah_dikonsumsi'); // Kg, Liter, dll.

           // Peternakan
           $table->integer('jumlah_ternak_terjual')->unsigned()->nullable()->after('satuan_konsumsi');
           $table->string('jenis_produk_ternak_terjual')->nullable()->after('jumlah_ternak_terjual'); // Bisa teks bebas atau enum
           $table->decimal('berat_total_terjual_kg', 12, 2)->nullable()->after('jenis_produk_ternak_terjual');
           $table->decimal('volume_produk_liter', 12, 2)->nullable()->after('berat_total_terjual_kg');

           // Perikanan
           $table->decimal('jumlah_tangkapan_kg', 12, 2)->nullable()->after('volume_produk_liter');
           $table->string('jenis_ikan_dominan')->nullable()->after('jumlah_tangkapan_kg');
           $table->decimal('jumlah_diolah_kg', 12, 2)->nullable()->after('jenis_ikan_dominan'); // Jumlah yg diolah (Kg)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pelaporan_hasils', function (Blueprint $table) {
            // Drop kolom baru
            $columnsToDrop = [
                'jumlah_dijual', 'satuan_dijual', 'harga_jual_per_satuan_dijual', 'pendapatan_lain',
                'hasil_panen_kg', 'hasil_panen_ton', 'kualitas_hasil_panen', 'jumlah_dikonsumsi', 'satuan_konsumsi',
                'jumlah_ternak_terjual', 'jenis_produk_ternak_terjual', 'berat_total_terjual_kg', 'volume_produk_liter',
                'jumlah_tangkapan_kg', 'jenis_ikan_dominan', 'jumlah_diolah_kg'
             ];
             $table->dropColumn($columnsToDrop);

             // Kembalikan kolom lama jika perlu
             // $table->decimal('harga_jual_per_satuan', 15, 2)->nullable()->after(...);
             // $table->decimal('total_penjualan', 15, 2)->default(0)->change(); // Kembalikan ke default 0 jika nullable diubah
        });
    }
};
