<?php

namespace App\Filament\Resources\PendaftaranKelompokResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\PendaftaranKelompokResource;
use App\Filament\Resources\PendaftaranKelompokResource\Widgets\PendaftaranKelompokStatsOverview;

class ListPendaftaranKelompoks extends ListRecords
{
    protected static string $resource = PendaftaranKelompokResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    /**
     * Daftarkan widget yang akan tampil di header halaman list ini.
     */
    protected function getHeaderWidgets(): array
    {
        return [
            PendaftaranKelompokStatsOverview::class, // <-- Daftarkan widget stats
        ];
    }

    // --- Opsional: Kirim filter ke widget ---
    // protected function getHeaderWidgetsColumns(): int | array
    // {
    //     return 1; // Atau 2 jika ingin widget lebih lebar
    // }
    // protected function getHeaderWidgetsData(): array
    // {
    //     return [
    //         'filterData' => $this->tableFilters, // Kirim state filter tabel
    //     ];
    // }
    // ---------------------------------------
}
