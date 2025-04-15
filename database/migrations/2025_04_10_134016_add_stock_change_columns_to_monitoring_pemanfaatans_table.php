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
        Schema::table('monitoring_pemanfaatans', function (Blueprint $table) {
            // Tambahkan kolom setelah kolom spesifik yang sudah ada
            // Default 0 agar SUM() tidak menghasilkan NULL jika tidak ada data
            $table->integer('jumlah_kelahiran_sejak_terakhir')->unsigned()->nullable()->default(0)->after('pemanfaatan_pakan_ternak')->comment('Jumlah kelahiran ternak/ikan sejak monitoring lalu');
            $table->integer('jumlah_kematian_sejak_terakhir')->unsigned()->nullable()->default(0)->after('jumlah_kelahiran_sejak_terakhir')->comment('Jumlah kematian ternak/ikan sejak monitoring lalu');
            $table->integer('jumlah_dikonsumsi_sejak_terakhir')->unsigned()->nullable()->default(0)->after('jumlah_kematian_sejak_terakhir')->comment('Jumlah ternak/ikan/hasil panen dikonsumsi sejak monitoring lalu');
            $table->integer('jumlah_rusak_hilang_sejak_terakhir')->unsigned()->nullable()->default(0)->after('jumlah_dikonsumsi_sejak_terakhir')->comment('Jumlah alat/sarana rusak/hilang sejak monitoring lalu');
            // Catatan: Jumlah terjual diambil dari tabel pelaporan_hasils
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_pemanfaatans', function (Blueprint $table) {
            $table->dropColumn([
                'jumlah_kelahiran_sejak_terakhir',
                'jumlah_kematian_sejak_terakhir',
                'jumlah_dikonsumsi_sejak_terakhir',
                'jumlah_rusak_hilang_sejak_terakhir',
            ]);
        });
    }
};
