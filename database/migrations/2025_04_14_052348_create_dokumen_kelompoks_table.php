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
        Schema::create('dokumen_kelompoks', function (Blueprint $table) {
            $table->id();
            // Foreign key ke tabel kelompoks
            $table->foreignId('kelompok_id')
            ->constrained('kelompoks') // Nama tabel referensi
            ->cascadeOnDelete(); // Jika kelompok dihapus, dokumen terkait ikut terhapus

            // Foreign key ke tabel users (untuk user yg upload)
            $table->foreignId('user_id')
                    ->nullable() // Boleh null jika tidak diketahui siapa yg upload
                    ->constrained('users') // Nama tabel referensi
                    ->nullOnDelete(); // Jika user dihapus, user_id jadi NULL

            $table->string('nama_dokumen'); // Nama deskriptif file (misal: "Proposal Bantuan 2024")
            $table->string('kategori')->nullable()->index(); // Kategori: Proposal, SK, Foto Kegiatan, LPJ, dll.
            $table->text('deskripsi')->nullable(); // Deskripsi tambahan tentang dokumen
            $table->string('path_file'); // Path penyimpanan file relatif terhadap disk
            $table->string('tipe_file')->nullable(); // MIME Type file (misal: application/pdf, image/jpeg)
            $table->unsignedBigInteger('ukuran_file')->nullable(); // Ukuran file dalam bytes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumen_kelompoks');
    }
};
