<?php

namespace App\Filament\Resources\KelompokResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\BadgeColumn;
use App\Models\Bantuan; // Import Bantuan
use Filament\Forms\Components\DatePicker;
use Filament\Support\RawJs; // Untuk mask
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get; // Untuk get value form lain
use Filament\Forms\Components\Hidden; // Untuk user_id
use Filament\Resources\RelationManagers\RelationManager;
use App\Models\DistribusiBantuan; // Import DistribusiBantuan

class DistribusiBantuansRelationManager extends RelationManager
{
    protected static string $relationship = 'distribusiBantuans';

    // Judul record akan kita set di table()
    protected static ?string $modelLabel = 'Distribusi Bantuan';
    protected static ?string $pluralModelLabel = 'Distribusi Bantuan Diterima';

    public function form(Form $form): Form
    {
        $statusPemberianOptions = [
            'Direncanakan' => 'Direncanakan',
            'Proses Pengiriman' => 'Proses Pengiriman',
            'Diterima Kelompok' => 'Diterima Kelompok',
            'Dibatalkan' => 'Dibatalkan',
        ];

        return $form
            ->schema([
                // kelompok_id otomatis terisi dari relasi
                Select::make('bantuan_id')
                    ->label('Jenis Bantuan')
                    ->relationship('bantuan', 'nama_bantuan', modifyQueryUsing: fn (Builder $query, RelationManager $livewire) =>
                        // Filter bantuan berdasarkan jenis kelompok induk
                        $query->where('jenis_bantuan', $livewire->ownerRecord->jenis_kelompok)
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive() // Agar satuan & harga bisa update
                    ->afterStateUpdated(function (callable $set, ?string $state) {
                        // Ambil satuan default dari master bantuan saat dipilih
                        $bantuan = Bantuan::find($state);
                        if ($bantuan) {
                            $set('satuan', $bantuan->satuan);
                        }
                    })
                    ->columnSpan(2),
                TextInput::make('jumlah')
                    ->numeric()
                    ->required()
                    ->minValue(0.01), // Minimal 0.01?
                TextInput::make('satuan')
                    ->required()
                    ->helperText('Satuan saat diberikan (otomatis dari master).'),
                DatePicker::make('tanggal_distribusi')
                    ->required()
                    ->native(false)
                    ->default(now()), // Default hari ini
                Select::make('status_pemberian')
                    ->options($statusPemberianOptions)
                    ->required()
                    ->default('Diterima Kelompok'), // Default sudah diterima?
                // Harga awal total dihitung otomatis oleh model event
                // TextInput::make('harga_awal_total')
                //     ->money('IDR') // Format IDR
                //     ->readOnly() // Hanya lihat
                //     ->helperText('Dihitung otomatis dari jumlah & harga master.'),
                Textarea::make('catatan_distribusi')
                    ->rows(3)
                    ->columnSpanFull(),
                Hidden::make('user_id')->default(Auth::id()), // ID petugas yg input
            ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn (DistribusiBantuan $record): string => "Bantuan {$record->bantuan->nama_bantuan}") // Judul record dinamis
            ->columns([
                TextColumn::make('bantuan.nama_bantuan')
                    ->label('Nama Bantuan')
                    ->searchable()
                    ->sortable()
                    ->description(fn(DistribusiBantuan $r) => "{$r->jumlah} {$r->satuan}"),
                // TextColumn::make('jumlah')
                //     ->alignRight(),
                // TextColumn::make('satuan'),
                TextColumn::make('tanggal_distribusi')
                    ->date('d M Y')
                    ->sortable(),
                BadgeColumn::make('status_pemberian')
                    ->colors([
                        'gray' => 'Direncanakan',
                        'info' => 'Proses Pengiriman',
                        'success' => 'Diterima Kelompok',
                        'danger' => 'Dibatalkan',
                    ])
                    ->searchable(),
                TextColumn::make('harga_awal_total')
                    ->label('Est. Nilai')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.name') // Petugas yg mencatat
                    ->label('Dicatat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Dicatat Tgl')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status_pemberian')
                    ->options([
                        'Direncanakan' => 'Direncanakan',
                        'Proses Pengiriman' => 'Proses Pengiriman',
                        'Diterima Kelompok' => 'Diterima Kelompok',
                        'Dibatalkan' => 'Dibatalkan',
                    ]),
                // Filter berdasarkan jenis bantuan?
                SelectFilter::make('bantuan_id')
                    ->label('Filter Jenis Bantuan')
                    ->relationship('bantuan', 'nama_bantuan', modifyQueryUsing: fn (Builder $query, RelationManager $livewire) =>
                        $query->where('jenis_bantuan', $livewire->ownerRecord->jenis_kelompok)
                    )
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(), // Tombol tambah distribusi
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                 ->visible(fn () => Auth::user()->can('delete_distribusi::bantuan')), // Sesuaikan permission
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                     ->visible(fn () => Auth::user()->can('delete_distribusi::bantuan')), // Sesuaikan permission
                ]),
            ])
            ->striped()
            ->defaultSort('tanggal_distribusi', 'desc');
    }
}
