<?php

namespace App\Filament\Resources\MonitoringPemanfaatanResource\Pages;

use App\Filament\Resources\MonitoringPemanfaatanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMonitoringPemanfaatan extends EditRecord
{
    protected static string $resource = MonitoringPemanfaatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
