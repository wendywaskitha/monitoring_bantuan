<?php

namespace App\Filament\Resources\KelompokResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\KelompokResource;
use Filament\Actions\Action as HeaderAction;

class ViewKelompok extends ViewRecord
{
    protected static string $resource = KelompokResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            // --- Tambahkan Action Cetak PDF di Header ---
            HeaderAction::make('cetakPdfHeader')
                ->label('Cetak Laporan PDF')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn (): string => route('report.kelompok.pdf', $this->record), shouldOpenInNewTab: true)
                ->visible(fn (): bool => Auth::user()?->hasRole('Admin') ?? false),
        // -----------------------------------------
        ];
    }
}
