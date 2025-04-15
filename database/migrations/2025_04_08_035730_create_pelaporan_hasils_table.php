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
        Schema::create('pelaporan_hasils', function (Blueprint $table) {
            $table->id();
            // Merujuk ke item bantuan spesifik yang didistribusikan
            $table->foreignId('distribusi_bantuan_id')->constrained('distribusi_bantuans')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Petugas yg melapor
            $table->date('tanggal_pelaporan');
            $table->decimal('jumlah_hasil', 10, 2)->nullable(); // Jumlah hasil yg didapat (panen, ternak lahir, dll)
            $table->string('satuan_hasil')->nullable(); // Satuan dari hasil (kg, ekor, dll)
            $table->decimal('harga_jual_per_satuan', 15, 2)->nullable();
            $table->decimal('total_penjualan', 15, 2)->default(0); // Akan dihitung otomatis
            $table->string('foto_hasil')->nullable(); // Path file foto hasil
            $table->text('catatan_hasil')->nullable();
            $table->timestamps();

            // $table->index('distribusi_bantuan_id');
            // $table->index('user_id');
            $table->index('tanggal_pelaporan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelaporan_hasils');
    }
};
