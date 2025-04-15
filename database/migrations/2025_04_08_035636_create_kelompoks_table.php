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
        Schema::create('kelompoks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desa_id')->constrained('desas')->cascadeOnDelete();
            $table->string('nama_kelompok');
            // ketua_id akan ditambahkan setelah tabel anggota dibuat (lihat di bawah)
            $table->enum('jenis_kelompok', ['Pertanian', 'Peternakan', 'Perikanan']);
            $table->text('alamat_sekretariat')->nullable();
            $table->date('tanggal_dibentuk')->nullable();
            $table->timestamps();

            // $table->index('desa_id'); // Index sudah dibuat oleh foreignId()
            $table->index('jenis_kelompok');
            $table->unique(['desa_id', 'nama_kelompok']); // Nama kelompok unik per desa
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelompoks');
    }
};
