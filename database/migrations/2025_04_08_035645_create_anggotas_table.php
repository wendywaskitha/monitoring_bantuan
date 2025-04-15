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
        Schema::create('anggotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelompok_id')->constrained('kelompoks')->cascadeOnDelete();
            $table->string('nama_anggota');
            $table->string('nik')->unique()->nullable(); // NIK unik jika diisi
            $table->text('alamat')->nullable();
            $table->string('nomor_telepon')->nullable();
            $table->boolean('is_ketua')->default(false); // Menandakan ketua kelompok
            $table->timestamps();

            // // $table->index('kelompok_id'); // Index sudah dibuat oleh foreignId()
            // $table->index('nik');
            // // Pastikan hanya ada satu ketua per kelompok (bisa diperkuat dengan trigger DB atau validasi aplikasi)
            // $table->unique(['kelompok_id', 'is_ketua'], 'kelompok_ketua_unique')->where('is_ketua', true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anggotas');
    }
};
