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
            // Kolom Umum (sudah ada: id, distribusi_bantuan_id, user_id, tanggal_monitoring, deskripsi_pemanfaatan, persentase_perkembangan, foto_dokumentasi, catatan_monitoring, timestamps)

            // Kolom Spesifik Peternakan (nullable)
            $table->integer('jumlah_ternak_saat_ini')->unsigned()->nullable()->after('catatan_monitoring');
            $table->integer('jumlah_kelahiran')->unsigned()->nullable()->after('jumlah_ternak_saat_ini');
            $table->integer('jumlah_kematian')->unsigned()->nullable()->after('jumlah_kelahiran');
            $table->enum('kondisi_kesehatan_ternak', ['Sehat', 'Sakit Ringan', 'Sakit Berat'])->nullable()->after('jumlah_kematian');
            $table->enum('kondisi_kandang', ['Baik', 'Cukup', 'Kurang'])->nullable()->after('kondisi_kesehatan_ternak');
            $table->text('pemanfaatan_pakan_ternak')->nullable()->after('kondisi_kandang');

            // Kolom Spesifik Pertanian - Tanaman (nullable)
            $table->decimal('luas_tanam_aktual', 8, 2)->nullable()->after('pemanfaatan_pakan_ternak')->comment('Dalam Hektar');
            $table->enum('kondisi_tanaman', ['Subur', 'Cukup Subur', 'Kurang Subur', 'Terserang Hama/Penyakit'])->nullable()->after('luas_tanam_aktual');
            $table->string('fase_pertumbuhan')->nullable()->after('kondisi_tanaman')->comment('Misal: Vegetatif Awal, Pembungaan, Pengisian Biji, Panen');
            $table->decimal('estimasi_hasil_panen', 10, 2)->nullable()->after('fase_pertumbuhan')->comment('Dalam Kg atau Ton');
            $table->string('satuan_hasil_panen')->nullable()->after('estimasi_hasil_panen')->comment('Kg, Ton, dll');

             // Kolom Spesifik Pertanian/Perikanan - Alat/Sarana (nullable)
            $table->enum('kondisi_alat', ['Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang'])->nullable()->after('satuan_hasil_panen');
            $table->text('pemanfaatan_alat')->nullable()->after('kondisi_alat'); // Deskripsi penggunaan pupuk, jaring, terpal, dll.
            $table->decimal('frekuensi_penggunaan_alat', 8, 2)->nullable()->after('pemanfaatan_alat')->comment('Misal: 3 (kali/minggu), 10 (Ha/musim)');
            $table->string('satuan_frekuensi_alat')->nullable()->after('frekuensi_penggunaan_alat')->comment('kali/minggu, Ha/musim, dll');

            // Kolom Spesifik Perikanan - Budidaya (nullable)
            $table->integer('jumlah_ikan_hidup')->unsigned()->nullable()->after('satuan_frekuensi_alat');
            $table->string('ukuran_ikan_rata2')->nullable()->after('jumlah_ikan_hidup')->comment('Misal: 10-12 cm, 500 gram/ekor');
            $table->enum('kondisi_air_kolam', ['Baik', 'Keruh', 'Berbau', 'Hijau Pekat'])->nullable()->after('ukuran_ikan_rata2');
            $table->text('pemberian_pakan_ikan')->nullable()->after('kondisi_air_kolam');

            // Kolom Spesifik Perikanan - Tangkap (nullable)
            $table->decimal('estimasi_hasil_tangkapan', 10, 2)->nullable()->after('pemberian_pakan_ikan')->comment('Dalam Kg');
            $table->string('satuan_hasil_tangkapan')->nullable()->default('Kg')->after('estimasi_hasil_tangkapan');

            // Tambahkan index jika diperlukan untuk query kolom baru
            $table->index('kondisi_kesehatan_ternak');
            $table->index('kondisi_tanaman');
            $table->index('kondisi_alat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_pemanfaatans', function (Blueprint $table) {
            // Drop kolom-kolom baru (dalam urutan terbalik atau definisikan array)
            $columnsToDrop = [
                'jumlah_ternak_saat_ini', 'jumlah_kelahiran', 'jumlah_kematian',
                'kondisi_kesehatan_ternak', 'kondisi_kandang', 'pemanfaatan_pakan_ternak',
                'luas_tanam_aktual', 'kondisi_tanaman', 'fase_pertumbuhan',
                'estimasi_hasil_panen', 'satuan_hasil_panen', 'kondisi_alat',
                'pemanfaatan_alat', 'frekuensi_penggunaan_alat', 'satuan_frekuensi_alat',
                'jumlah_ikan_hidup', 'ukuran_ikan_rata2', 'kondisi_air_kolam',
                'pemberian_pakan_ikan', 'estimasi_hasil_tangkapan', 'satuan_hasil_tangkapan'
            ];
            // Drop index dulu jika ada
            // $table->dropIndex(['kondisi_kesehatan_ternak']); // Nama index bisa otomatis
            // $table->dropIndex(['kondisi_tanaman']);
            // $table->dropIndex(['kondisi_alat']);

            $table->dropColumn($columnsToDrop);
        });
    }
};
