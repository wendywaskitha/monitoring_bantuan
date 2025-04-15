<?php

namespace App\Filament\Resources\KecamatanResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;

class DesasRelationManager extends RelationManager
{
    protected static string $relationship = 'desas';

    protected static ?string $recordTitleAttribute = 'nama_desa'; // Sudah di set saat generate

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nama_desa')
                    ->required()
                    ->maxLength(255)
                    // Tidak perlu unique global, tapi bisa unique per kecamatan (otomatis karena relasi)
                    ->columnSpanFull(),
                // kecamatan_id tidak perlu di form ini karena otomatis terisi
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // ->recordTitleAttribute('nama_desa') // Alternatif set judul record
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('nama_desa')->searchable()->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(), // Tombol Tambah Desa
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped();
    }
}
