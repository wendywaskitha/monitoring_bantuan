<?php

namespace App\Filament\Resources\EvaluasiKelompokResource\Pages;

use App\Filament\Resources\EvaluasiKelompokResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEvaluasiKelompok extends ViewRecord
{
    protected static string $resource = EvaluasiKelompokResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
