<?php

namespace App\Filament\Resources\PesanKontakResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\PesanKontakResource;
use App\Filament\Resources\PesanKontakResource\Widgets\PesanKontakStatsOverview;

class ListPesanKontaks extends ListRecords
{
    protected static string $resource = PesanKontakResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PesanKontakStatsOverview::class, // <-- Daftarkan widget stats
        ];
    }

    
}
