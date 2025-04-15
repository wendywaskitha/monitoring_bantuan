<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Desa;
use App\Models\Kelompok;
use App\Models\Anggota;
use Illuminate\Support\Facades\DB; // Untuk transaction
use Illuminate\Support\Str; // Untuk Str::random

// Jika menggunakan Faker
use Faker\Factory as Faker;

class KelompokAnggotaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Memulai seeder Kelompok dan Anggota...');
        $faker = Faker::create('id_ID'); // Gunakan locale Indonesia untuk Faker

        // Ambil beberapa desa secara acak untuk disebar kelompoknya
        $desas = Desa::inRandomOrder()->limit(15)->get(); // Ambil 15 desa acak

        if ($desas->isEmpty()) {
            $this->command->error('Tidak ada data Desa ditemukan. Jalankan MunaBaratSeeder terlebih dahulu.');
            return;
        }

        $jenisKelompok = ['Pertanian', 'Peternakan', 'Perikanan'];
        $namaKelompokPrefix = [
            'Pertanian' => ['Tani Makmur', 'Sri Rejeki', 'Harapan Jaya', 'Maju Bersama', 'Sumber Tani'],
            'Peternakan' => ['Ternak Sejahtera', 'Sumber Hewani', 'Karya Mandiri Ternak', 'Mekar Jaya Ternak', 'Harapan Ternak'],
            'Perikanan' => ['Mina Bahari', 'Nelayan Sejahtera', 'Sumber Ikan', 'Maju Jaya Perikanan', 'Harapan Bahari']
        ];

        DB::transaction(function () use ($desas, $jenisKelompok, $namaKelompokPrefix, $faker) {
            foreach ($desas as $desa) {
                // Buat 1-3 kelompok per desa terpilih
                $jumlahKelompokPerDesa = rand(1, 3);

                for ($i = 0; $i < $jumlahKelompokPerDesa; $i++) {
                    $jenis = $jenisKelompok[array_rand($jenisKelompok)];
                    $namaPrefix = $namaKelompokPrefix[$jenis][array_rand($namaKelompokPrefix[$jenis])];
                    $namaKelompok = "Kelompok {$namaPrefix} {$desa->nama_desa}";

                    // Buat Kelompok
                    $kelompok = Kelompok::create([
                        'desa_id' => $desa->id,
                        'nama_kelompok' => $namaKelompok,
                        'jenis_kelompok' => $jenis,
                        'alamat_sekretariat' => "Sekretariat {$namaKelompok}, {$desa->nama_desa}",
                        'tanggal_dibentuk' => $faker->dateTimeBetween('-3 years', '-1 month')->format('Y-m-d'),
                        'ketua_id' => null, // Akan diisi setelah anggota dibuat
                    ]);
                    $this->command->line("  -> Kelompok: {$kelompok->nama_kelompok} (Desa: {$desa->nama_desa}, Jenis: {$jenis})");

                    $ketuaAnggota = null;
                    $jumlahAnggota = rand(7, 20); // Jumlah anggota per kelompok

                    for ($j = 0; $j < $jumlahAnggota; $j++) {
                        $isKetua = ($j === 0); // Jadikan anggota pertama sebagai ketua

                        // Generate NIK unik (contoh sederhana, mungkin perlu logika lebih baik)
                        $nik = $faker->unique()->numerify('7403#############'); // 16 digit diawali kode Muna Barat (contoh)

                        $anggota = Anggota::create([
                            'kelompok_id' => $kelompok->id,
                            'nama_anggota' => $faker->name(),
                            'nik' => $nik,
                            'alamat' => $faker->address(),
                            'nomor_telepon' => $faker->numerify('08##########'), // 10-12 digit nomor HP
                            'is_ketua' => $isKetua, // Set flag is_ketua
                        ]);

                        if ($isKetua) {
                            $ketuaAnggota = $anggota; // Simpan objek ketua
                        }
                         $this->command->line("     - Anggota: {$anggota->nama_anggota}" . ($isKetua ? ' (KETUA)' : ''));
                    }

                    // Update ketua_id di kelompok setelah semua anggota dibuat
                    if ($ketuaAnggota) {
                        $kelompok->update(['ketua_id' => $ketuaAnggota->id]);
                         $this->command->line("     * Ketua ditetapkan: {$ketuaAnggota->nama_anggota}");
                    } else {
                         $this->command->warn("     ! Tidak ada ketua yang ditetapkan untuk kelompok {$kelompok->nama_kelompok}");
                    }
                }
            }
        });

        $this->command->info('Seeder Kelompok dan Anggota selesai.');
    }
}
