<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Kecamatan;
use App\Models\Desa;
use Illuminate\Support\Facades\DB; // Untuk transaction

class MunaBaratSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Memulai seeder Kecamatan dan Desa Muna Barat...');

        // Data Kecamatan dan Desa/Kelurahan dari Wikipedia (per April 2025, cek ulang jika perlu)
        $data = [
            'Barangka' => ['Barangka', 'Bungkolo', 'Lafinde', 'Lapolea', 'Sawerigadi', 'Waulai', 'Wuna'],
            'Kusambi' => ['Bakeramba', 'Guali', 'Kasakamu', 'Kusambi', 'Lemoambo', 'Sidamangura', 'Tanjung Pinang', 'Lapokainse', 'Konawe'], // Kelurahan Kusambi & Konawe
            'Lawa' => ['Lawa', 'Lagadi', 'Lalemo', 'Latugho', 'Latompe', 'Madampi', 'Mata Indaha', 'Watumela', 'Wamelai', 'Lapolemo'], // Kelurahan Lapolemo & Wamelai
            'Maginti' => ['Abadi Jaya', 'Bangko', 'Gala', 'Kambara', 'Kangkunawe', 'Kembar Maminasa', 'Mekar Jaya', 'Pajala', 'Pasipadangan', 'Kasimpa Jaya'], // Kelurahan Pajala & Tiworo
            'Napano Kusambi' => ['Kombi-Kombi', 'Lahaji', 'Masara', 'Napano Kusambi', 'Tangkalalo', 'Umba'],
            'Sawerigadi' => ['Kampobalano', 'Lailangga', 'Lombu Jaya', 'Maperaha', 'Marobea', 'Nihi', 'Ondoke', 'Pada Indah', 'Sawerigadi', 'Wakoila', 'Watu Putih', 'Lawada Jaya'],
            'Tiworo Kepulauan' => ['Katela', 'Lasama', 'Laworo', 'Sido Makmur', 'Tiworo', 'Wandarasi', 'Wulanga Jaya', 'Kambikuno'], // Kelurahan Tiworo
            'Tiworo Selatan' => ['Barakkah', 'Kasimpa Jaya', 'Katangana', 'Parura Jaya', 'Sangia Tiworo', 'Wulante'],
            'Tiworo Tengah' => ['Labokolo', 'Lakabu', 'Langku Langku', 'Mekar Sari', 'Momuntu', 'Wanseriwu', 'Wapae', 'Waturempe'],
            'Tiworo Utara' => ['Mandike', 'Santigi', 'Santiri', 'Tasipi', 'Tiga', 'Tondasi', 'Bero', 'Mandiri'],
            'Wadaga' => ['Kampani', 'Katobu', 'Lailab', 'Lindo', 'Linsowu', 'Wadaga', 'Wakontu']
        ];

        DB::transaction(function () use ($data) {
            foreach ($data as $namaKecamatan => $daftarDesa) {
                // Buat atau cari Kecamatan
                $kecamatan = Kecamatan::firstOrCreate(
                    ['nama_kecamatan' => $namaKecamatan],
                );
                $this->command->line("  -> Kecamatan: {$kecamatan->nama_kecamatan}");

                foreach ($daftarDesa as $namaDesa) {
                    // Buat atau cari Desa dalam kecamatan tersebut
                    Desa::firstOrCreate(
                        [
                            'kecamatan_id' => $kecamatan->id,
                            'nama_desa' => $namaDesa
                        ],
                    );
                    $this->command->line("     - Desa/Kel: {$namaDesa}");
                }
            }
        });

        $this->command->info('Seeder Kecamatan dan Desa Muna Barat selesai.');
    }
}
