<?php

namespace App\Filament\Resources\DistribusiBantuanResource\Pages;

use App\Filament\Resources\DistribusiBantuanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDistribusiBantuan extends ViewRecord
{
    protected static string $resource = DistribusiBantuanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
