<?php

namespace App\Filament\Resources\DistribusiBantuanResource\Pages;

use App\Filament\Resources\DistribusiBantuanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDistribusiBantuans extends ListRecords
{
    protected static string $resource = DistribusiBantuanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
