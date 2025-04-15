<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User; // Import User model
use Illuminate\Support\Facades\Hash; // Import Hash for password hashing

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- Definisikan Semua Permissions ---

        // Permissions untuk User (Resource: User)
        Permission::firstOrCreate(['name' => 'view_any_user', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_user', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create_user', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'update_user', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete_user', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'restore_user', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'force_delete_user', 'guard_name' => 'web']);

        // Permissions untuk Role & Permission (Resource: Role dari Filament Shield)
        Permission::firstOrCreate(['name' => 'view_any_role', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_role', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create_role', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'update_role', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete_role', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'assign_permission', 'guard_name' => 'web']);

        // Permissions untuk Master Data (Kecamatan, Desa, Bantuan)
        Permission::firstOrCreate(['name' => 'view_any_kecamatan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_kecamatan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create_kecamatan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'update_kecamatan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete_kecamatan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_any_desa', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_desa', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create_desa', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'update_desa', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete_desa', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_any_bantuan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_bantuan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create_bantuan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'update_bantuan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete_bantuan', 'guard_name' => 'web']);

        // Permissions untuk Manajemen Inti (Kelompok, Distribusi, Monitoring, Pelaporan, Evaluasi)
        Permission::firstOrCreate(['name' => 'view_any_kelompok', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_kelompok', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create_kelompok', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'update_kelompok', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete_kelompok', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_any_distribusi::bantuan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_distribusi::bantuan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create_distribusi::bantuan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'update_distribusi::bantuan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete_distribusi::bantuan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_any_monitoring::pemanfaatan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_monitoring::pemanfaatan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create_monitoring::pemanfaatan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'update_monitoring::pemanfaatan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete_monitoring::pemanfaatan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_any_pelaporan::hasil', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_pelaporan::hasil', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create_pelaporan::hasil', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'update_pelaporan::hasil', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete_pelaporan::hasil', 'guard_name' => 'web']);

        // Permissions untuk CRUD Evaluasi Kelompok (jika dikelola via Resource/Relation Manager)
        // Nama permission ini mungkin tidak terpakai jika hanya pakai halaman Livewire,
        // tapi tidak masalah jika tetap ada. Sesuaikan jika Anda membuat Resource terpisah nanti.
        Permission::firstOrCreate(['name' => 'view_any_evaluasi::kelompok', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_evaluasi::kelompok', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create_evaluasi::kelompok', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'update_evaluasi::kelompok', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete_evaluasi::kelompok', 'guard_name' => 'web']);

        // Permission KHUSUS untuk Halaman Custom Evaluasi Kelompok
        Permission::firstOrCreate(['name' => 'view_page_kelola_evaluasi_kelompok', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'manage_app_settings', 'guard_name' => 'web']);


        // --- Buat Roles ---
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $petugasRole = Role::firstOrCreate(['name' => 'Petugas Lapangan', 'guard_name' => 'web']);


        // --- Assign Permissions ke Role Petugas Lapangan ---
        $petugasPermissions = [
            // Kelompok
            'view_any_kelompok', 'view_kelompok', 'create_kelompok', 'update_kelompok',
            // Distribusi Bantuan
            'view_any_distribusi::bantuan', 'view_distribusi::bantuan', 'create_distribusi::bantuan', 'update_distribusi::bantuan',
            // Monitoring Pemanfaatan
            'view_any_monitoring::pemanfaatan', 'view_monitoring::pemanfaatan', 'create_monitoring::pemanfaatan', 'update_monitoring::pemanfaatan',
            // Pelaporan Hasil
            'view_any_pelaporan::hasil', 'view_pelaporan::hasil', 'create_pelaporan::hasil', 'update_pelaporan::hasil',
            // Evaluasi (CRUD - jika diperlukan nanti)
            'view_any_evaluasi::kelompok', 'view_evaluasi::kelompok', 'create_evaluasi::kelompok', 'update_evaluasi::kelompok',
            // Halaman Custom Evaluasi
            'view_page_kelola_evaluasi_kelompok', // <-- Tambahkan permission halaman
        ];
        // Anda bisa uncomment permission delete jika petugas boleh hapus
        // 'delete_kelompok',
        // 'delete_distribusi::bantuan',
        // 'delete_monitoring::pemanfaatan',
        // 'delete_pelaporan::hasil',
        // 'delete_evaluasi::kelompok',

        $petugasRole->syncPermissions($petugasPermissions); // Sync permission petugas


        // --- Buat User Awal ---
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin Utama', 'password' => Hash::make('password')]
        );
        $adminUser->assignRole($adminRole);

        $petugasUser = User::firstOrCreate(
            ['email' => 'petugas@example.com'],
            ['name' => 'Petugas Lapangan 1', 'password' => Hash::make('password')]
        );
        $petugasUser->assignRole($petugasRole);

        $adminRole->givePermissionTo('manage_app_settings');


        // --- Assign SEMUA Permissions ke Role Admin ---
        // Dilakukan terakhir setelah semua permission dibuat
        $adminRole->syncPermissions(Permission::all());


        // Clear cache lagi untuk memastikan
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Roles and Permissions seeded successfully.');
        $this->command->info('Created Admin user: admin@example.com / password');
        $this->command->info('Created Petugas Lapangan user: petugas@example.com / password');
    }
}
