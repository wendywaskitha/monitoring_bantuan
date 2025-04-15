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
        Schema::create('bantuans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_bantuan');
            $table->enum('jenis_bantuan', ['Pertanian', 'Peternakan', 'Perikanan']);
            $table->string('satuan'); // e.g., kg, liter, ekor, unit, paket
            $table->text('deskripsi')->nullable();
            $table->decimal('perkiraan_harga_awal', 15, 2)->default(0); // Harga per satuan
            $table->timestamps();

            $table->index('jenis_bantuan');
            $table->unique(['nama_bantuan', 'jenis_bantuan']); // Nama bantuan unik per jenis
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bantuans');
    }
};
