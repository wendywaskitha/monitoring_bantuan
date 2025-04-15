<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Bantuan;

class BantuanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Memulai seeder Bantuan...');

        $bantuans = [
            // Pertanian
            ['nama_bantuan' => 'Bibit Jagung Hibrida Bisi-18', 'jenis_bantuan' => 'Pertanian', 'satuan' => 'kg', 'perkiraan_harga_awal' => 75000, 'deskripsi' => 'Bibit jagung unggul tahan penyakit'],
            ['nama_bantuan' => 'Bibit Padi Ciherang', 'jenis_bantuan' => 'Pertanian', 'satuan' => 'kg', 'perkiraan_harga_awal' => 12000, 'deskripsi' => 'Bibit padi populer tahan wereng'],
            ['nama_bantuan' => 'Pupuk Urea Non-Subsidi', 'jenis_bantuan' => 'Pertanian', 'satuan' => 'kg', 'perkiraan_harga_awal' => 8000, 'deskripsi' => 'Pupuk nitrogen untuk pertumbuhan tanaman'],
            ['nama_bantuan' => 'Pupuk NPK Mutiara 16-16-16', 'jenis_bantuan' => 'Pertanian', 'satuan' => 'kg', 'perkiraan_harga_awal' => 15000, 'deskripsi' => 'Pupuk majemuk seimbang'],
            ['nama_bantuan' => 'Hand Sprayer 16L', 'jenis_bantuan' => 'Pertanian', 'satuan' => 'unit', 'perkiraan_harga_awal' => 250000, 'deskripsi' => 'Alat semprot pestisida/pupuk cair'],
            ['nama_bantuan' => 'Mesin Pompa Air Alkon 3 inch', 'jenis_bantuan' => 'Pertanian', 'satuan' => 'unit', 'perkiraan_harga_awal' => 1500000, 'deskripsi' => 'Pompa irigasi sawah'],

            // Peternakan
            ['nama_bantuan' => 'Bibit Sapi Bali Betina', 'jenis_bantuan' => 'Peternakan', 'satuan' => 'ekor', 'perkiraan_harga_awal' => 8000000, 'deskripsi' => 'Bibit sapi lokal adaptif'],
            ['nama_bantuan' => 'Bibit Ayam Kampung Unggul (DOC)', 'jenis_bantuan' => 'Peternakan', 'satuan' => 'ekor', 'perkiraan_harga_awal' => 8500, 'deskripsi' => 'Anak ayam kampung umur 1 hari'],
            ['nama_bantuan' => 'Bibit Kambing Etawa Jantan', 'jenis_bantuan' => 'Peternakan', 'satuan' => 'ekor', 'perkiraan_harga_awal' => 3500000, 'deskripsi' => 'Bibit kambing perah/daging'],
            ['nama_bantuan' => 'Pakan Konsentrat Sapi', 'jenis_bantuan' => 'Peternakan', 'satuan' => 'kg', 'perkiraan_harga_awal' => 5000, 'deskripsi' => 'Pakan tambahan penggemukan sapi'],
            ['nama_bantuan' => 'Pakan Ayam Broiler Starter', 'jenis_bantuan' => 'Peternakan', 'satuan' => 'kg', 'perkiraan_harga_awal' => 9000, 'deskripsi' => 'Pakan awal ayam pedaging'],
            ['nama_bantuan' => 'Obat Ternak Vitamin B Kompleks', 'jenis_bantuan' => 'Peternakan', 'satuan' => 'botol', 'perkiraan_harga_awal' => 50000, 'deskripsi' => 'Suplemen kesehatan ternak'],

            // Perikanan
            ['nama_bantuan' => 'Bibit Ikan Nila Gesit (5-7 cm)', 'jenis_bantuan' => 'Perikanan', 'satuan' => 'ekor', 'perkiraan_harga_awal' => 500, 'deskripsi' => 'Benih ikan nila tahan penyakit'],
            ['nama_bantuan' => 'Bibit Ikan Lele Sangkuriang (7-9 cm)', 'jenis_bantuan' => 'Perikanan', 'satuan' => 'ekor', 'perkiraan_harga_awal' => 600, 'deskripsi' => 'Benih ikan lele pertumbuhan cepat'],
            ['nama_bantuan' => 'Jaring Ikan Nylon D/6', 'jenis_bantuan' => 'Perikanan', 'satuan' => 'roll', 'perkiraan_harga_awal' => 1200000, 'deskripsi' => 'Jaring tangkap ikan ukuran standar'],
            ['nama_bantuan' => 'Pakan Ikan Apung HI-PROVITE 781-1', 'jenis_bantuan' => 'Perikanan', 'satuan' => 'kg', 'perkiraan_harga_awal' => 13000, 'deskripsi' => 'Pakan ikan protein tinggi'],
            ['nama_bantuan' => 'Terpal Kolam A12 4x6m', 'jenis_bantuan' => 'Perikanan', 'satuan' => 'lembar', 'perkiraan_harga_awal' => 450000, 'deskripsi' => 'Terpal untuk pembuatan kolam ikan'],
            ['nama_bantuan' => 'Aerator Kolam 1 Lubang', 'jenis_bantuan' => 'Perikanan', 'satuan' => 'unit', 'perkiraan_harga_awal' => 75000, 'deskripsi' => 'Alat penambah oksigen kolam'],
        ];

        foreach ($bantuans as $bantuanData) {
            Bantuan::firstOrCreate(
                [
                    'nama_bantuan' => $bantuanData['nama_bantuan'],
                    'jenis_bantuan' => $bantuanData['jenis_bantuan']
                ], // Kunci untuk cek duplikat
                $bantuanData // Data lengkap jika baru dibuat
            );
            $this->command->line("  -> Bantuan: {$bantuanData['nama_bantuan']} ({$bantuanData['jenis_bantuan']})");
        }

        $this->command->info('Seeder Bantuan selesai.');
    }
}
