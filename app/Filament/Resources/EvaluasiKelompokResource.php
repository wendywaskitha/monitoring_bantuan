<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Kelompok;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Number;
use App\Models\EvaluasiKelompok;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;

use Filament\Tables\Actions\ActionGroup;

use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth; // Import Auth
use App\Filament\Resources\EvaluasiKelompokResource\Pages;
use Filament\Infolists\Components\Section as InfolistSection;
use App\Filament\Resources\EvaluasiKelompokResource\RelationManagers;

class EvaluasiKelompokResource extends Resource
{
    protected static ?string $model = EvaluasiKelompok::class;

    // Konfigurasi Navigasi
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar'; // Ikon berbeda
    protected static ?string $navigationLabel = 'Data Evaluasi'; // Label lebih jelas
    protected static ?string $pluralModelLabel = 'Data Evaluasi Kelompok';
    protected static ?string $modelLabel = 'Data Evaluasi';
    protected static ?string $navigationGroup = 'Laporan & Analitik'; // Grup Navigasi
    protected static ?int $navigationSort = 2; // Urutan dalam grup

    public static function form(Form $form): Form
    {
        $user = Auth::user(); // Dapatkan user saat ini

        return $form
            ->schema([
                Section::make('Informasi Evaluasi')
                    ->columns(2)
                    ->schema([
                        Select::make('kelompok_id')
                            ->label('Kelompok yang Dievaluasi')
                            ->relationship(
                                name: 'kelompok',
                                titleAttribute: 'nama_kelompok',
                                // Filter opsi kelompok untuk Petugas Lapangan
                                modifyQueryUsing: function (Builder $query) use ($user) {
                                    $query->with('desa');
                                    if ($user && !$user->hasRole('Admin')) {
                                        $query->where('user_id', $user->id);
                                    }
                                }
                            )
                            ->getOptionLabelFromRecordUsing(fn (Kelompok $record) => "{$record->nama_kelompok} (Desa: {$record->desa?->nama_desa})")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull()
                            ->helperText(fn(): string => $user?->hasRole('Admin') ? 'Pilih dari semua kelompok.' : 'Hanya menampilkan kelompok yang Anda tangani.'),

                        DatePicker::make('tanggal_evaluasi')
                            ->label('Tanggal Evaluasi')
                            ->required()
                            ->native(false)
                            ->default(now()),

                        TextInput::make('periode_omset')
                            ->label('Periode Omset')
                            ->helperText('Contoh: Januari 2024, Kuartal 1 2024, Tahun 2023')
                            ->nullable()
                            ->maxLength(100),

                        // Petugas yang Melakukan Evaluasi (bisa otomatis atau dipilih jika Admin)
                        Select::make('user_id')
                            ->label('Petugas Evaluasi')
                            ->relationship('user', 'name', fn (Builder $query) => $query->role(['Admin', 'Petugas Lapangan'])) // Opsi Admin & Petugas
                            ->searchable()
                            ->preload()
                            ->required()
                            // Default ke user login jika dia petugas/admin, jika tidak null
                            ->default(fn() => ($user?->hasAnyRole(['Admin', 'Petugas Lapangan'])) ? $user->id : null)
                            // Admin bisa memilih, Petugas tidak bisa ganti (otomatis dirinya)
                            ->disabled(fn(): bool => !$user?->hasRole('Admin')),

                    ]),

                Section::make('Data Kuantitatif')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nilai_aset')
                            ->label('Estimasi Nilai Aset')
                            ->prefix('Rp')
                            ->numeric()->nullable()->minValue(0)
                            ->formatStateUsing(fn ($state): ?string => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : null)
                            ->dehydrateStateUsing(fn ($state): ?string => is_numeric($state) ? $state : null)
                            ->helperText('Estimasi total nilai aset saat ini.'),
                        TextInput::make('omset_periode')
                            ->label('Omset Selama Periode')
                            ->prefix('Rp')
                            ->numeric()->nullable()->minValue(0)
                            ->formatStateUsing(fn ($state): ?string => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : null)
                            ->dehydrateStateUsing(fn ($state): ?string => is_numeric($state) ? $state : null)
                            ->helperText('Total penjualan kotor selama periode.'),
                    ]),

                Section::make('Catatan Kualitatif')
                    ->schema([
                        Textarea::make('catatan_evaluasi')
                            ->label('Catatan/Evaluasi Petugas')
                            ->rows(5) // Lebih banyak baris
                            ->columnSpanFull()
                            ->helperText('Deskripsikan perkembangan, kendala, rekomendasi, dan justifikasi kelayakan bantuan selanjutnya.'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user(); // Dapatkan user

        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->searchable(),
                TextColumn::make('kelompok.nama_kelompok')
                    ->label('Kelompok')
                    ->searchable()
                    ->sortable()
                    ->description(fn(EvaluasiKelompok $record) => "Desa: " . $record->kelompok?->desa?->nama_desa),
                TextColumn::make('tanggal_evaluasi')->date('d M Y')->sortable(),
                TextColumn::make('periode_omset')->placeholder('-')->searchable(),
                TextColumn::make('nilai_aset')->label('Nilai Aset')->money('IDR', 0)->sortable(),
                TextColumn::make('omset_periode')->label('Omset Periode')->money('IDR', 0)->sortable(),
                TextColumn::make('user.name') // Petugas yg input evaluasi
                    ->label('Dievaluasi Oleh')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('kelompok.petugas.name') // Petugas PJ Kelompok
                    ->label('Petugas PJ Kelompok')
                    ->placeholder('-')
                    ->visible(fn (): bool => $user?->hasRole('Admin') ?? false) // Hanya Admin
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Waktu Input')->dateTime('d M Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filter Kelompok (otomatis terbatas untuk petugas)
                SelectFilter::make('kelompok')
                    ->relationship('kelompok', 'nama_kelompok', function (Builder $query) use ($user) {
                         if ($user && !$user->hasRole('Admin')) {
                            $query->where('user_id', $user->id);
                        }
                    })
                    ->searchable()->preload(),
                // Filter Petugas Evaluasi (visible untuk Admin)
                SelectFilter::make('user_id')
                    ->label('Filter Dievaluasi Oleh')
                    ->relationship('user', 'name', fn (Builder $query) => $query->role(['Admin', 'Petugas Lapangan']))
                    ->searchable()->preload()
                    ->visible(fn (): bool => $user?->hasRole('Admin') ?? false),
                 // Filter Petugas PJ Kelompok (visible untuk Admin)
                 SelectFilter::make('petugas_pj')
                    ->label('Filter Petugas PJ Kelompok')
                    ->query(fn (Builder $query, array $data): Builder =>
                        $query->when($data['value'], fn ($q, $v) =>
                           $q->whereHas('kelompok', fn($kq) => $kq->where('user_id', $v))
                        )
                    )
                    ->relationship('kelompok.petugas', 'name', fn (Builder $query) => $query->role('Petugas Lapangan'))
                    ->searchable()->preload()
                    ->visible(fn (): bool => $user?->hasRole('Admin') ?? false),
                // Filter Tanggal Evaluasi (Contoh sederhana)
                Filter::make('tanggal_evaluasi')
                    ->form([
                        DatePicker::make('evaluasi_dari')->label('Evaluasi Dari'),
                        DatePicker::make('evaluasi_sampai')->label('Evaluasi Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['evaluasi_dari'], fn (Builder $query, $date): Builder => $query->whereDate('tanggal_evaluasi', '>=', $date))
                            ->when($data['evaluasi_sampai'], fn (Builder $query, $date): Builder => $query->whereDate('tanggal_evaluasi', '<=', $date));
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    // ViewAction::make(),
                    // --- Action Kustom untuk View ---
                    Action::make('lihat') // Nama unik untuk action
                        ->label('Lihat')   // Label tombol/link
                        ->icon('heroicon-o-eye') // Ikon mata
                        ->color('gray') // Warna standar untuk view
                        // Definisikan URL tujuan menggunakan closure
                        ->url(function (EvaluasiKelompok $record): string {
                            // Panggil getUrl() dari Resource ini,
                            // berikan nama rute ('view') dan array parameter
                            return static::getUrl('view', ['record' => $record->id]);
                        })
                        // Opsional: Buka di tab baru
                        // ->openUrlInNewTab()
                        // Kontrol visibilitas berdasarkan permission (jika perlu)
                        ->visible(fn (EvaluasiKelompok $record) => Auth::user()->can('view_evaluasi::kelompok', $record)), // Ganti nama permission jika perlu
                    // --------------------------------
                    Action::make('ubah') // Nama unik untuk action edit
                        ->label('Edit')
                        ->icon('heroicon-o-pencil-square')
                        ->color('primary') // Warna standar untuk edit
                        // Definisikan URL tujuan menggunakan closure
                        ->url(function (EvaluasiKelompok $record): string {
                            // Panggil getUrl() dari Resource ini,
                            // berikan nama rute ('edit') dan array parameter
                            return static::getUrl('edit', ['record' => $record->id]);
                        })
                        // Kontrol visibilitas berdasarkan permission
                        ->visible(fn (EvaluasiKelompok $record) => Auth::user()->can('update_evaluasi::kelompok', $record)),
                    DeleteAction::make()->visible(fn (EvaluasiKelompok $record) => Auth::user()->can('delete_evaluasi::kelompok', $record)),


                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->visible(fn () => Auth::user()->can('delete_any_evaluasi::kelompok')),
                ]),
            ])
            ->striped()
            ->defaultSort('tanggal_evaluasi', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Informasi Kelompok & Evaluasi')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('kelompok.nama_kelompok')->label('Kelompok'),
                        TextEntry::make('kelompok.desa.nama_desa')->label('Desa'),
                        TextEntry::make('kelompok.desa.kecamatan.nama_kecamatan')->label('Kecamatan'),
                        TextEntry::make('tanggal_evaluasi')->date('d F Y'),
                        TextEntry::make('periode_omset')->placeholder('-'),
                        TextEntry::make('user.name')->label('Dievaluasi Oleh'),
                    ]),
                InfolistSection::make('Hasil Kuantitatif')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('nilai_aset')->label('Estimasi Nilai Aset')->money('IDR', 0),
                        TextEntry::make('omset_periode')->label('Omset Periode')->money('IDR', 0),
                    ]),
                InfolistSection::make('Catatan Kualitatif')
                    ->schema([
                        TextEntry::make('catatan_evaluasi')
                            ->label(false) // Hilangkan label
                            ->placeholder('Tidak ada catatan.')
                            ->extraAttributes(['class' => 'whitespace-normal break-words prose prose-sm dark:prose-invert']), // Styling
                    ]),
            ]);
    }

     /**
     * Modifikasi query dasar untuk Resource ini berdasarkan peran.
     */
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery()->with([
            'kelompok.desa.kecamatan', // Eager load sampai kecamatan
            'kelompok.petugas',        // Eager load petugas PJ kelompok
            'user'                     // Eager load user yg input evaluasi
        ]);

        // Jika user login dan BUKAN Admin, filter hanya evaluasi
        // untuk kelompok yang ditangani user tersebut.
        if ($user && !$user->hasRole('Admin')) {
            $query->whereHas('kelompok', function (Builder $q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            // Tidak ada relation manager dari sini biasanya
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvaluasiKelompoks::route('/'),
            'create' => Pages\CreateEvaluasiKelompok::route('/create'),
            'view' => Pages\ViewEvaluasiKelompok::route('/{record}'),
            'edit' => Pages\EditEvaluasiKelompok::route('/{record}/edit'),
        ];
    }

    
}
