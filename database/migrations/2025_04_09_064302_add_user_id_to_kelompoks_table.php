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
        Schema::table('kelompoks', function (Blueprint $table) {
            // Tambahkan kolom user_id setelah desa_id (atau posisi lain)
            // Nullable karena mungkin ada kelompok yg belum ditugaskan
            // onDelete('set null') agar jika petugas dihapus, kelompok tidak ikut terhapus
            $table->foreignId('user_id')
                  ->nullable()
                  ->after('desa_id')
                  ->constrained('users') // Merujuk ke tabel users
                  ->nullOnDelete();

            // Tambahkan index untuk performa query berdasarkan user_id
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelompoks', function (Blueprint $table) {
            // Hati-hati drop foreign key di MySQL
            // Nama constraint default: kelompoks_user_id_foreign
            try {
                $table->dropForeign(['user_id']);
           } catch (\Exception $e) {
               // Mungkin nama berbeda atau sudah tidak ada
           }
           $table->dropIndex(['user_id']); // Hapus index jika ditambahkan
           $table->dropColumn('user_id');
        });
    }
};
