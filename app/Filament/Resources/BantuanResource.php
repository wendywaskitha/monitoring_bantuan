<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Bantuan;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Support\RawJs;

use Filament\Resources\Resource;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Average;
use App\Filament\Resources\BantuanResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\Filter; // Untuk filter harga
use App\Filament\Resources\BantuanResource\RelationManagers;
use Filament\Tables\Columns\BadgeColumn; // Untuk jenis bantuan
use Filament\Tables\Filters\SelectFilter; // Untuk filter jenis
use Filament\Tables\Columns\Summarizers\Sum; // Untuk summary harga
use Filament\Forms\Components\Fieldset; // Untuk mengelompokkan form
use Filament\Forms\Components\Radio; // Alternatif untuk jenis bantuan
use Filament\Forms\Components\ToggleButtons; // Alternatif lain yang menarik

class BantuanResource extends Resource
{
    protected static ?string $model = Bantuan::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift'; // Ikon kado/bantuan
    protected static ?string $navigationGroup = 'Data Master';
    protected static ?int $navigationSort = 3; // Urutan setelah Desa
    protected static ?string $recordTitleAttribute = 'nama_bantuan';

    public static function form(Form $form): Form
    {
        // Opsi untuk jenis bantuan (sesuai enum di migrasi)
        $jenisBantuanOptions = [
            'Pertanian' => 'Pertanian',
            'Peternakan' => 'Peternakan',
            'Perikanan' => 'Perikanan',
        ];

        return $form
            ->schema([
                Section::make('Detail Bantuan')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nama_bantuan')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1) // Lebar 1 kolom
                            // ->unique(ignoreRecord: true) // Seharusnya unik per jenis, perlu custom rule
                             ->rule(static function (Forms\Get $get, Forms\Components\Component $component, ?Bantuan $record): \Illuminate\Validation\Rules\Unique {
                                // Custom rule untuk unique per jenis_bantuan
                                $rule = \Illuminate\Validation\Rule::unique('bantuans', 'nama_bantuan')
                                    ->where('jenis_bantuan', $get('jenis_bantuan'));
                                if ($record) {
                                    $rule->ignore($record->id);
                                }
                                return $rule;
                            }),

                        // Pilih salah satu: Select, Radio, atau ToggleButtons
                        // Select::make('jenis_bantuan')
                        //     ->label('Jenis Bantuan')
                        //     ->options($jenisBantuanOptions)
                        //     ->required()
                        //     ->searchable()
                        //     ->columnSpan(1),

                        // Radio::make('jenis_bantuan')
                        //     ->label('Jenis Bantuan')
                        //     ->options($jenisBantuanOptions)
                        //     ->required()
                        //     ->inline() // Tampilkan horizontal
                        //     ->columnSpan(2), // Ambil 2 kolom jika pakai radio

                        ToggleButtons::make('jenis_bantuan') // UI lebih menarik
                            ->label('Jenis Bantuan')
                            ->options($jenisBantuanOptions)
                            ->required()
                            ->inline()
                            ->grouped() // Tombol menyatu
                            ->icons([ // Tambahkan ikon (opsional, perlu install blade-heroicons)
                                'Pertanian' => 'heroicon-m-check',
                                'Peternakan' => 'heroicon-o-bug-ant', // Ganti ikon sesuai
                                'Perikanan' => 'heroicon-m-adjustments-horizontal', // Ganti ikon sesuai
                            ])
                            ->colors([ // Warna berbeda per jenis
                                'Pertanian' => 'success',
                                'Peternakan' => 'warning',
                                'Perikanan' => 'info',
                            ])
                            ->columnSpanFull(), // Ambil lebar penuh

                        TextInput::make('satuan')
                            ->label('Satuan')
                            ->required()
                            ->helperText('Contoh: kg, liter, ekor, unit, paket')
                            ->maxLength(50)
                            ->columnSpan(1),

                        TextInput::make('perkiraan_harga_awal')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->prefix('Rp ')
                            ->numeric(),

                        Textarea::make('deskripsi')
                            ->label('Deskripsi Bantuan')
                            ->rows(3)
                            ->columnSpanFull(), // Ambil lebar penuh
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
         // Opsi untuk jenis bantuan (sesuai enum di migrasi)
        $jenisBantuanOptions = [
            'Pertanian' => 'Pertanian',
            'Peternakan' => 'Peternakan',
            'Perikanan' => 'Perikanan',
        ];

        return $table
            ->columns([
                TextColumn::make('id')->sortable()->label('ID'),
                TextColumn::make('nama_bantuan')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Bantuan $record): string => $record->deskripsi ?? ''), // Tampilkan deskripsi di bawah nama
                BadgeColumn::make('jenis_bantuan') // Gunakan BadgeColumn
                    ->label('Jenis')
                    ->colors([ // Sesuaikan warna dengan form
                        'success' => 'Pertanian',
                        'warning' => 'Peternakan',
                        'info' => 'Perikanan',
                        'primary' => fn ($state) => !in_array($state, ['Pertanian', 'Peternakan', 'Perikanan']), // Warna default jika ada nilai lain
                    ])
                    ->searchable()
                    ->sortable(),
                TextColumn::make('satuan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('perkiraan_harga_awal')
                    ->label('Harga Awal/Satuan')
                    ->money('IDR') // Format sebagai mata uang Rupiah
                    ->sortable()
                    // Tambahkan summary di footer tabel
                    ->summarize([
                        Average::make()->label('Rata-rata Harga')->money('IDR'),
                        // Sum::make()->label('Total Estimasi Harga')->money('IDR') // Kurang relevan untuk harga satuan
                    ]),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('jenis_bantuan')
                    ->label('Filter Jenis')
                    ->options($jenisBantuanOptions)
                    ->multiple() // Bisa filter lebih dari satu jenis
                    ->preload(),
                // Filter berdasarkan range harga (contoh)
                Filter::make('harga_awal_range')
                    ->form([
                        TextInput::make('harga_min')->numeric()->prefix('Rp'),
                        TextInput::make('harga_max')->numeric()->prefix('Rp'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['harga_min'],
                                fn (Builder $query, $harga): Builder => $query->where('perkiraan_harga_awal', '>=', $harga),
                            )
                            ->when(
                                $data['harga_max'],
                                fn (Builder $query, $harga): Builder => $query->where('perkiraan_harga_awal', '<=', $harga),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string { // Tampilkan indikator filter aktif
                        if (! $data['harga_min'] && ! $data['harga_max']) {
                            return null;
                        }
                        $min = $data['harga_min'] ? 'Rp ' . number_format($data['harga_min'], 0, ',', '.') : '';
                        $max = $data['harga_max'] ? 'Rp ' . number_format($data['harga_max'], 0, ',', '.') : '';

                        if ($min && $max) return "Harga: {$min} - {$max}";
                        if ($min) return "Harga >= {$min}";
                        if ($max) return "Harga <= {$max}";
                        return null;
                    })
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBantuans::route('/'),
            'create' => Pages\CreateBantuan::route('/create'),
            'view' => Pages\ViewBantuan::route('/{record}'),
            'edit' => Pages\EditBantuan::route('/{record}/edit'),
        ];
    }
}
