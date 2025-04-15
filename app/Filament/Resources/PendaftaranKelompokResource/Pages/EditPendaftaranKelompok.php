<?php

namespace App\Filament\Resources\PendaftaranKelompokResource\Pages;

use App\Filament\Resources\PendaftaranKelompokResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPendaftaranKelompok extends EditRecord
{
    protected static string $resource = PendaftaranKelompokResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
