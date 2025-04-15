<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KecamatanResource\Pages;
use App\Filament\Resources\KecamatanResource\RelationManagers; // Akan kita buat Relation Manager Desa
use App\Models\Kecamatan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ActionGroup; // Untuk grouping action
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

class KecamatanResource extends Resource
{
    protected static ?string $model = Kecamatan::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin'; // Ikon peta
    protected static ?string $navigationGroup = 'Data Master'; // Grup navigasi
    protected static ?int $navigationSort = 1; // Urutan dalam grup
    protected static ?string $recordTitleAttribute = 'nama_kecamatan'; // Judul record saat view/edit

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nama_kecamatan')
                    ->label('Nama Kecamatan')
                    ->required()
                    ->unique(ignoreRecord: true) // Nama kecamatan harus unik
                    ->maxLength(255)
                    ->columnSpanFull(), // Ambil lebar penuh
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()->searchable()->label('ID'),
                TextColumn::make('nama_kecamatan')
                    ->label('Nama Kecamatan')
                    ->searchable()
                    ->sortable(),
                // Menampilkan jumlah desa (menggunakan count relasi)
                TextColumn::make('desas_count')
                    ->counts('desas') // Hitung relasi 'desas'
                    ->label('Jumlah Desa')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Tidak ada filter khusus untuk kecamatan saat ini
            ])
            ->actions([
                ActionGroup::make([ // Kelompokkan aksi
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])->tooltip("Opsi Lain"), // Tooltip untuk group
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            // Daftarkan Relation Manager untuk Desa di sini nanti
            RelationManagers\DesasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKecamatans::route('/'),
            'create' => Pages\CreateKecamatan::route('/create'),
            // Aktifkan view jika ingin halaman detail terpisah
            'view' => Pages\ViewKecamatan::route('/{record}'),
            'edit' => Pages\EditKecamatan::route('/{record}/edit'),
        ];
    }
}
