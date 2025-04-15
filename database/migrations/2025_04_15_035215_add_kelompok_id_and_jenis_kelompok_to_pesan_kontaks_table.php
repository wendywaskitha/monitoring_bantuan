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
        Schema::table('pesan_kontaks', function (Blueprint $table) {
            $table->foreignId('kelompok_id')->nullable()->constrained('kelompoks')->nullOnDelete();
            $table->string('jenis_kelompok')->nullable(); // Untuk menyimpan jenis kelompok terkait
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pesan_kontaks', function (Blueprint $table) {
            //
        });
    }
};
