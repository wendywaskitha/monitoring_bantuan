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
            // Tambahkan setelah kolom alamat atau di akhir
            // Gunakan tipe data DECIMAL untuk presisi yang baik
            $table->decimal('latitude', 10, 8)->nullable()->after('alamat_sekretariat'); // Presisi 8 desimal cukup
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude'); // Longitude perlu 11 digit total
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelompoks', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
