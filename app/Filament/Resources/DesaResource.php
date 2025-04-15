<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DesaResource\Pages;
use App\Filament\Resources\DesaResource\RelationManagers;
use App\Models\Desa;
use App\Models\Kecamatan; // Import Kecamatan
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Validation\Rule; // Import Rule facade
use Illuminate\Contracts\Validation\Rule as ValidationRule; // Alias untuk custom rule

class DesaResource extends Resource
{
    protected static ?string $model = Desa::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationGroup = 'Data Master';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'nama_desa';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('kecamatan_id')
                    ->label('Kecamatan')
                    ->relationship('kecamatan', 'nama_kecamatan')
                    ->searchable()
                    ->preload()
                    ->live() // Penting untuk validasi desa yang bergantung kecamatan
                    ->required()
                    ->createOptionForm([
                        TextInput::make('nama_kecamatan')
                            ->label('Nama Kecamatan Baru')
                            ->required()
                            ->maxLength(255)
                            ->unique(table: Kecamatan::class, column: 'nama_kecamatan')
                            ->validationAttribute('Nama Kecamatan Baru'),
                    ])
                    ->helperText('Pilih kecamatan atau buat baru jika belum ada.'),

                TextInput::make('nama_desa')
                    ->label('Nama Desa')
                    ->required()
                    ->maxLength(255)
                    // Gunakan ->rules() atau ->rule() dengan closure yang return array
                    ->rules([ // Lebih eksplisit menggunakan ->rules() untuk array
                        static function (Forms\Get $get, ?Desa $record): array { // <-- Ubah return type hint ke array
                            $kecamatanId = $get('kecamatan_id');

                            if (!$kecamatanId) {
                                // Jangan terapkan rule unique jika kecamatan belum dipilih
                                return []; // Kembalikan array kosong
                            }

                            // Buat rule unique menggunakan Rule facade
                            $rule = Rule::unique('desas', 'nama_desa')
                                        ->where('kecamatan_id', $kecamatanId);

                            // Abaikan ID record saat ini jika sedang dalam mode edit
                            if ($record) {
                                $rule->ignore($record->id);
                            }

                            // Kembalikan rule unique yang sudah dikonfigurasi DI DALAM ARRAY
                            return [$rule];
                        }
                    ])
                    ->validationAttribute('Nama Desa'),

            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()->label('ID'),
                TextColumn::make('nama_desa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kecamatan.nama_kecamatan')
                    ->label('Kecamatan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kecamatan')
                    ->label('Filter Kecamatan')
                    ->relationship('kecamatan', 'nama_kecamatan')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                 ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])->tooltip("Opsi Lain"),
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
            // Relation managers
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDesas::route('/'),
            'create' => Pages\CreateDesa::route('/create'),
            'view' => Pages\ViewDesa::route('/{record}'),
            'edit' => Pages\EditDesa::route('/{record}/edit'),
        ];
    }
}
