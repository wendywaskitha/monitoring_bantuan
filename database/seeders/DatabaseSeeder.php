<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\BantuanSeeder;
use Database\Seeders\MunaBaratSeeder;
use Database\Seeders\KelompokAnggotaSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            RolesAndPermissionsSeeder::class, // Pastikan ini jalan dulu
            MunaBaratSeeder::class,          // Kemudian data lokasi
            BantuanSeeder::class,            // Kemudian data jenis bantuan
            KelompokAnggotaSeeder::class,    // Terakhir kelompok & anggota yg butuh lokasi
        ]);
    }
}
