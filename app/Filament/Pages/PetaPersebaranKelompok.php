<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;

class PetaPersebaranKelompok extends Page
{
    // Tunjuk ke view yang akan merender komponen Livewire
    protected static string $view = 'filament.pages.peta-persebaran-kelompok';

    // Konfigurasi Navigasi
    protected static ?string $navigationIcon = 'heroicon-o-map'; // Ikon Peta
    protected static ?string $navigationLabel = 'Peta Kelompok';
    protected static ?string $title = 'Peta Persebaran Kelompok Bantuan';

    // Grup navigasi (misal: Laporan & Analitik atau grup baru)
    protected static ?string $navigationGroup = 'Laporan & Analitik';
    protected static ?int $navigationSort = 3; // Urutan setelah Analitik

    // Slug URL
    protected static ?string $slug = 'peta-kelompok';

    /**
     * Otorisasi akses - Admin & Petugas Lapangan?
     */
    public static function canView(): bool
    {
        // Ganti dengan permission spesifik jika perlu
        // return Gate::allows('view_page_peta_kelompok');
        return auth()->user()?->hasAnyRole(['Admin', 'Petugas Lapangan']) ?? false;
    }
}
