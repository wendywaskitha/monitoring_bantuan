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
        Schema::table('pendaftaran_kelompoks', function (Blueprint $table) {
            // Tambahkan kolom setelah verifikator_id atau sebelum timestamps
            $table->foreignId('kelompok_baru_id')
                  ->nullable() // Nullable karena baru diisi saat kelompok dibuat
                  ->after('diverifikasi_at') // Sesuaikan posisi jika perlu
                  ->constrained('kelompoks') // Foreign key ke tabel kelompoks
                  ->nullOnDelete(); // Jika kelompok dihapus, ID di sini jadi NULL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pendaftaran_kelompoks', function (Blueprint $table) {
            // Drop foreign key dulu (nama constraint default: pendaftaran_kelompoks_kelompok_baru_id_foreign)
            try {
                $table->dropForeign(['kelompok_baru_id']);
            } catch (\Exception $e) {
                // Mungkin nama berbeda atau tidak ada
            }
            // Drop kolomnya
            $table->dropColumn('kelompok_baru_id');
        });
    }
};
