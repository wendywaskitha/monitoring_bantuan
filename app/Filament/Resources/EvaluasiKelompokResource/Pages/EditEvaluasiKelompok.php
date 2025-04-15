<?php

namespace App\Filament\Resources\EvaluasiKelompokResource\Pages;

use App\Filament\Resources\EvaluasiKelompokResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEvaluasiKelompok extends EditRecord
{
    protected static string $resource = EvaluasiKelompokResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
