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
        Schema::create('evaluasi_kelompoks', function (Blueprint $table) {
            // Relasi ke Kelompok
            $table->foreignId('kelompok_id')->constrained('kelompoks')->cascadeOnDelete();
            // Relasi ke Petugas yang melakukan evaluasi
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // Tanggal evaluasi/pencatatan data
            $table->date('tanggal_evaluasi');
            // Periode yang dicakup oleh omset (misal: "Januari 2024", "Kuartal 1 2024")
            $table->string('periode_omset')->nullable();
            // Estimasi total nilai aset kelompok pada tanggal evaluasi
            $table->decimal('nilai_aset', 15, 2)->nullable()->default(0);
            // Total omset/penjualan kotor selama periode_omset
            $table->decimal('omset_periode', 15, 2)->nullable()->default(0);
            // Catatan/evaluasi dari petugas
            $table->text('catatan_evaluasi')->nullable();
            $table->timestamps();

            $table->index('tanggal_evaluasi');
            // Index untuk mempermudah query per kelompok per tanggal
            $table->index(['kelompok_id', 'tanggal_evaluasi']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluasi_kelompoks');
    }
};
