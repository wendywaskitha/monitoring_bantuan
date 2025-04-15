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
        Schema::create('pendaftaran_kelompoks', function (Blueprint $table) {
            $table->id();
            // Info Kelompok Diajukan
            $table->string('nama_kelompok_diajukan');
            $table->enum('jenis_kelompok', ['Pertanian', 'Peternakan', 'Perikanan']);
            $table->foreignId('kecamatan_id')->constrained('kecamatans')->cascadeOnDelete(); // Relasi ke kecamatan
            $table->foreignId('desa_id')->constrained('desas')->cascadeOnDelete(); // Relasi ke desa
            $table->text('alamat_singkat')->nullable();
            $table->integer('perkiraan_jumlah_anggota')->unsigned()->nullable();
            $table->text('deskripsi_kegiatan')->nullable();

            // Info Kontak Penanggung Jawab (Calon Ketua)
            $table->string('nama_kontak');
            $table->string('telepon_kontak');
            $table->string('email_kontak')->nullable();

            // Dokumen Pendukung (Opsional)
            $table->string('dokumen_pendukung_path')->nullable(); // Path file di storage
            $table->string('dokumen_pendukung_nama_asli')->nullable(); // Nama file asli

            // Status & Verifikasi
            $table->enum('status', ['Baru', 'Diproses', 'Ditolak', 'Diterima'])->default('Baru')->index();
            $table->text('catatan_verifikasi')->nullable();
            $table->foreignId('verifikator_id')->nullable()->constrained('users')->nullOnDelete(); // User yg verifikasi
            $table->timestamp('diverifikasi_at')->nullable(); // Waktu verifikasi

            $table->timestamps(); // Waktu pendaftaran (created_at)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pendaftaran_kelompoks');
    }
};
