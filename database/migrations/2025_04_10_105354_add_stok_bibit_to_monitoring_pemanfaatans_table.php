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
        Schema::table('monitoring_pemanfaatans', function (Blueprint $table) {
            // Tambahkan setelah kolom pertanian lain, misal setelah satuan_hasil_panen
            if (!Schema::hasColumn('monitoring_pemanfaatans', 'jumlah_stok_bibit_disimpan')) {
                $table->decimal('jumlah_stok_bibit_disimpan', 12, 2)->nullable()->after('satuan_hasil_panen')->comment('Jumlah hasil panen yg disimpan jadi bibit (Kg, dll)');
            }
             if (!Schema::hasColumn('monitoring_pemanfaatans', 'satuan_stok_bibit')) {
                $table->string('satuan_stok_bibit', 50)->nullable()->after('jumlah_stok_bibit_disimpan')->comment('Satuan stok bibit (Kg, Liter, dll)');
             }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_pemanfaatans', function (Blueprint $table) {
            $table->dropColumn(['jumlah_stok_bibit_disimpan', 'satuan_stok_bibit']);
        });
    }
};
