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
            // Tambahkan setelah kolom nama_kelompok, nullable karena mungkin diisi setelah anggota dibuat
            // onDelete('set null') agar jika ketua (anggota) dihapus, kelompok tidak ikut terhapus
            $table->foreignId('ketua_id')->nullable()->after('nama_kelompok')->constrained('anggotas')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelompoks', function (Blueprint $table) {
            // Hati-hati saat drop foreign key di MySQL/MariaDB, perlu nama constraint
            // Nama default: kelompoks_ketua_id_foreign
            $table->dropForeign(['ketua_id']);
            $table->dropColumn('ketua_id');
        });
    }
};
