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
        Schema::create('distribusi_bantuans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelompok_id')->constrained('kelompoks')->cascadeOnDelete();
            $table->foreignId('bantuan_id')->constrained('bantuans')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('jumlah', 10, 2);
            $table->string('satuan');
            // $table->decimal('harga_awal_total', 15, 2)->storedAs('...'); // JANGAN GUNAKAN INI
            $table->date('tanggal_distribusi');
            $table->enum('status_pemberian', ['Direncanakan', 'Proses Pengiriman', 'Diterima Kelompok', 'Dibatalkan'])->default('Direncanakan');
            $table->text('catatan_distribusi')->nullable();
            $table->timestamps();

            $table->index('tanggal_distribusi');
            $table->index('status_pemberian');
        });

        // Pastikan kolom ditambahkan sebagai kolom biasa jika belum ada di create()
        Schema::table('distribusi_bantuans', function (Blueprint $table) {
            if (!Schema::hasColumn('distribusi_bantuans', 'harga_awal_total')) { // Cek jika belum ada
                $table->decimal('harga_awal_total', 15, 2)->default(0)->after('satuan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribusi_bantuans');
    }
};
