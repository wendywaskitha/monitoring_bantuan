<?php

namespace App\Filament\Resources\PendaftaranKelompokResource\Pages;

use App\Filament\Resources\PendaftaranKelompokResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPendaftaranKelompok extends ViewRecord
{
    protected static string $resource = PendaftaranKelompokResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
