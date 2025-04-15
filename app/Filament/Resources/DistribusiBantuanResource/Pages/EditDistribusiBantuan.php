<?php

namespace App\Filament\Resources\DistribusiBantuanResource\Pages;

use App\Filament\Resources\DistribusiBantuanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDistribusiBantuan extends EditRecord
{
    protected static string $resource = DistribusiBantuanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
