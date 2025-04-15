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
        Schema::create('monitoring_pemanfaatans', function (Blueprint $table) {
            $table->id();
            // Merujuk ke item bantuan spesifik yang didistribusikan
            $table->foreignId('distribusi_bantuan_id')->constrained('distribusi_bantuans')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Petugas yg memonitor
            $table->date('tanggal_monitoring');
            $table->text('deskripsi_pemanfaatan');
            $table->unsignedTinyInteger('persentase_perkembangan')->default(0); // 0-100%
            $table->string('foto_dokumentasi')->nullable(); // Path file foto
            $table->text('catatan_monitoring')->nullable();
            $table->timestamps();

            // $table->index('distribusi_bantuan_id');
            // $table->index('user_id');
            $table->index('tanggal_monitoring');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_pemanfaatans');
    }
};
