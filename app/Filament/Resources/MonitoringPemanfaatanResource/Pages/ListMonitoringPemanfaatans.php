<?php

namespace App\Filament\Resources\MonitoringPemanfaatanResource\Pages;

use App\Filament\Resources\MonitoringPemanfaatanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMonitoringPemanfaatans extends ListRecords
{
    protected static string $resource = MonitoringPemanfaatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
