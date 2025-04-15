<?php

namespace App\Filament\Pages; // Namespace Pages standar

use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;

class AnalyticsPage extends Page
{
    // Tentukan view yang akan merender komponen Livewire
    protected static string $view = 'filament.pages.analytics-page';

    // Konfigurasi Navigasi
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie'; // Ikon Analitik
    protected static ?string $navigationLabel = 'Analitik Bantuan';
    protected static ?string $title = 'Analitik Program Bantuan';

    // Grup navigasi (buat grup baru atau gabung)
    protected static ?string $navigationGroup = 'Laporan & Analitik'; // Grup baru
    protected static ?int $navigationSort = 1;

    // Slug URL
    protected static ?string $slug = 'analitik-bantuan';

    /**
     * Otorisasi akses - Hanya Admin? Atau Pimpinan?
     */
    public static function canView(): bool
    {
        // Ganti dengan permission yang sesuai, misal 'view_page_analytics'
        // return Gate::allows('view_page_analytics');
        return auth()->user()?->hasRole('Admin') ?? false; // Sementara hanya Admin
    }
}
