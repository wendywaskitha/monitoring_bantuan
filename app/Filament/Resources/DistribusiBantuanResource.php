<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Infolists;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use App\Models\Bantuan; // Import
use App\Models\DistribusiBantuan;
use App\Models\Kelompok; // Import
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Grid;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get; // Untuk get value form lain
use Filament\Forms\Set; // Untuk set value form lain
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\DistribusiBantuanResource\Pages;
use Filament\Infolists\Components\Section as InfolistSection;
use App\Filament\Resources\DistribusiBantuanResource\RelationManagers; // Import namespace RelationManagers

class DistribusiBantuanResource extends Resource
{
    protected static ?string $model = DistribusiBantuan::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Manajemen Bantuan';
    protected static ?int $navigationSort = 2; // Urutan antara Kelompok dan Monitoring
    protected static ?string $pluralModelLabel = 'Distribusi Bantuan';
    protected static ?string $modelLabel = 'Distribusi';
    protected static ?string $recordTitleAttribute = 'id'; // Akan dikustomisasi di table


    protected static function updateHargaAwalTotal(Get $get, Set $set): void
    {
        $bantuanId = $get('bantuan_id');
        $jumlah = $get('jumlah');

        if ($bantuanId && is_numeric($jumlah) && $jumlah > 0) {
            $bantuan = Bantuan::find($bantuanId);
            if ($bantuan && is_numeric($bantuan->perkiraan_harga_awal)) {
                $total = (float)$jumlah * (float)$bantuan->perkiraan_harga_awal;
                // Set state 'harga_awal_total', formatStateUsing akan menanganinya
                $set('harga_awal_total', $total);
            } else {
                $set('harga_awal_total', null); // Set null jika bantuan/harga tidak valid
            }
        } else {
            $set('harga_awal_total', null); // Set null jika input tidak valid
        }
    }

    public static function form(Form $form): Form
    {
        $statusPemberianOptions = [
            'Direncanakan' => 'Direncanakan',
            'Proses Pengiriman' => 'Proses Pengiriman',
            'Diterima Kelompok' => 'Diterima Kelompok',
            'Dibatalkan' => 'Dibatalkan',
        ];

        $user = Auth::user();

        return $form
            ->schema([
                Section::make('Informasi Distribusi')
                    ->columns(2)
                    ->schema([
                        Select::make('kelompok_id')
                            ->label('Kelompok Penerima')
                            ->relationship(
                                name: 'kelompok',
                                titleAttribute: 'nama_kelompok',
                                // Query dasar untuk memfilter berdasarkan user (jika bukan admin)
                                // dan eager load desa
                                modifyQueryUsing: function (Builder $query) use ($user) {
                                    $query->with('desa');
                                    if ($user && !$user->hasRole('Admin')) {
                                        $query->where('user_id', $user->id);
                                    }
                                }
                            )
                            // Definisikan cara menampilkan label opsi
                            ->getOptionLabelUsing(function ($value): ?string {
                                // Cari Kelompok berdasarkan ID ($value)
                                $kelompok = Kelompok::find($value);
                                // Jika ditemukan, buat labelnya
                                if ($kelompok) {
                                    return "{$kelompok->nama_kelompok} (Desa: {$kelompok->desa?->nama_desa})";
                                }
                                // Kembalikan null atau string kosong jika tidak ditemukan
                                return null;
                            })
                            // Aktifkan input pencarian
                            ->searchable()
                            // Definisikan bagaimana hasil pencarian diambil (backend search)
                            ->getSearchResultsUsing(function(string $search, Get $get) use ($user): array {
                                // Mulai query dari model Kelompok
                                $query = Kelompok::query()->with('desa');

                                // Terapkan filter user jika bukan Admin
                                if ($user && !$user->hasRole('Admin')) {
                                    $query->where('user_id', $user->id);
                                }

                                // Terapkan filter pencarian
                                $query->where(function (Builder $q) use ($search) { // Gunakan Builder di sini
                                    $q->where('nama_kelompok', 'like', "%{$search}%")
                                    ->orWhereHas('desa', fn($dq) => $dq->where('nama_desa', 'like', "%{$search}%"));
                                    // Tambahkan pencarian kecamatan jika perlu
                                    // ->orWhereHas('desa.kecamatan', fn($kq) => $kq->where('nama_kecamatan', 'like', "%{$search}%"));
                                });

                                // Ambil hasil dan format untuk Select
                                return $query
                                    ->limit(50) // Batasi hasil
                                    ->get()
                                    ->mapWithKeys(function (Kelompok $kelompok) {
                                        $label = $kelompok->nama_kelompok . ' (Desa: ' . ($kelompok->desa?->nama_desa ?? 'N/A') . ')';
                                        return [$kelompok->id => $label];
                                    })->toArray();
                            })
                            // ->preload() // Hapus preload jika menggunakan getSearchResultsUsing
                            ->live()
                            ->required()
                            ->columnSpanFull()
                            ->helperText(fn(): string => $user?->hasRole('Admin') ? 'Pilih dari semua kelompok.' : 'Hanya menampilkan kelompok yang Anda tangani.'),



                        Select::make('bantuan_id')
                            ->label('Jenis Bantuan')
                            ->relationship(
                                name: 'bantuan',
                                titleAttribute: 'nama_bantuan',
                                // Filter bantuan berdasarkan jenis kelompok yang dipilih
                                modifyQueryUsing: function (Builder $query, Get $get) {
                                    $kelompokId = $get('kelompok_id');
                                    if ($kelompokId) {
                                        $kelompok = Kelompok::find($kelompokId);
                                        if ($kelompok) {
                                            return $query->where('jenis_bantuan', $kelompok->jenis_kelompok);
                                        }
                                    }
                                    // Jika kelompok belum dipilih, tampilkan semua (atau kosongkan?)
                                    // return $query->whereRaw('1 = 0'); // Kosongkan jika kelompok belum dipilih
                                    return $query; // Tampilkan semua jika kelompok belum dipilih
                                }
                            )
                            ->searchable()
                            ->preload()
                            ->live() // Agar satuan bisa update
                            ->required()
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) { // <-- Modifikasi afterStateUpdated
                                // Ambil satuan default
                                $bantuan = Bantuan::find($state);
                                $set('satuan', $bantuan?->satuan);
                                // Panggil juga kalkulasi total
                                self::updateHargaAwalTotal($get, $set);
                            })
                            ->helperText('Jenis bantuan akan terfilter otomatis berdasarkan jenis kelompok penerima.'),

                        TextInput::make('jumlah')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->live(debounce: '500ms') // <-- Tambahkan live
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::updateHargaAwalTotal($get, $set)), // <-- Panggil updater

                        TextInput::make('satuan')
                            ->required()
                            ->helperText('Satuan saat diberikan (otomatis dari master).'),

                        DatePicker::make('tanggal_distribusi')
                            ->required()
                            ->native(false)
                            ->default(now()),

                        ToggleButtons::make('status_pemberian')
                             ->label('Status Pemberian')
                            ->options($statusPemberianOptions)
                            ->required()
                            ->inline()
                            ->grouped()
                            ->default('Diterima Kelompok')
                            ->colors([
                                'Direncanakan' => 'gray',
                                'Proses Pengiriman' => 'info',
                                'Diterima Kelompok' => 'success',
                                'Dibatalkan' => 'danger',
                            ]),

                        Textarea::make('catatan_distribusi')
                            ->label('Catatan Distribusi')
                            ->rows(3)
                            ->columnSpanFull(),

                        Hidden::make('user_id')->default(fn() => Auth::id()), // Petugas yg input


                        // Menampilkan harga awal total (read-only)
                        TextInput::make('harga_awal_total')
                            ->label('Estimasi Nilai Bantuan')
                            ->prefix('Rp') // Tetap gunakan prefix
                            // ->money('IDR') // PASTIKAN BARIS INI DIHAPUS ATAU DIKOMENTARI
                            ->disabled() // Biarkan disabled (atau ->readOnly())
                            ->formatStateUsing(fn (?string $state): string =>
                                // Format nilai dari database (numeric) ke string IDR
                                // Gunakan 0 desimal untuk format seperti 1.500.000
                                is_numeric($state) ? number_format((float)$state, 0, ',', '.') : '0'
                                // Atau gunakan 2 desimal untuk format seperti 1.500.000,00
                                // is_numeric($state) ? number_format((float)$state, 2, ',', '.') : '0,00'
                            )
                            ->helperText('Dihitung otomatis saat disimpan.')
                            ->columnSpanFull(), // Pastikan ini ada jika perlu span penuh
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        $statusPemberianOptions = [
            'Direncanakan' => 'Direncanakan',
            'Proses Pengiriman' => 'Proses Pengiriman',
            'Diterima Kelompok' => 'Diterima Kelompok',
            'Dibatalkan' => 'Dibatalkan',
        ];

        $user = Auth::user();

        return $table
            ->columns([
                TextColumn::make('id')->sortable()->label('ID'),
                TextColumn::make('kelompok.nama_kelompok')
                    ->label('Kelompok')
                    ->searchable()
                    ->sortable()
                    ->description(fn(DistribusiBantuan $r) => "Desa: " . $r->kelompok->desa->nama_desa),
                TextColumn::make('bantuan.nama_bantuan')
                    ->label('Bantuan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jumlah')
                    ->alignRight(),
                TextColumn::make('satuan'),
                TextColumn::make('tanggal_distribusi')
                    ->date('d M Y')
                    ->sortable(),
                BadgeColumn::make('status_pemberian')
                    ->label('Status')
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
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('kelompok.petugas.name') // Petugas PJ Kelompok
                    ->label('Petugas PJ Kelompok')
                    ->placeholder('-')
                    ->visible(fn (): bool => $user?->hasRole('Admin') ?? false) // Hanya Admin
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Dicatat Tgl')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Ekspor Excel')
                    // HAPUS ->withFilename(...) dari sini
                    // ->withFilename('Daftar Kelompok - ' . date('Ymd'))
                    // Konfigurasi lain (opsional)
                    // ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                    // ->onlyColumns([...])
                    // ->exceptColumns([...])
                    ->visible(fn (): bool => Auth::user()?->hasRole('Admin') ?? false),
            ])
            ->filters([
                // --- Filter Kelompok ---
                SelectFilter::make('kelompok')
                    ->label('Filter Kelompok')
                    // Query di relationship akan otomatis terfilter oleh getEloquentQuery untuk Petugas
                    ->relationship('kelompok', 'nama_kelompok', function (Builder $query) use ($user) {
                         // Terapkan filter petugas juga di sini agar opsi dropdown konsisten
                         if ($user && !$user->hasRole('Admin')) {
                            $query->where('user_id', $user->id);
                        }
                    })
                    ->searchable()
                    ->preload(), // Preload opsi yang sudah difilter

                 // --- Filter Bantuan --- (Visible untuk semua peran)
                 SelectFilter::make('bantuan')
                    ->label('Filter Bantuan')
                    ->relationship('bantuan', 'nama_bantuan')
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => $user?->hasRole('Admin') ?? false), // <-- Visible hanya untuk Admin,

                // --- Filter Status Pemberian --- (Visible untuk semua peran)
                SelectFilter::make('status_pemberian')
                    ->label('Filter Status')
                    ->options($statusPemberianOptions),

                // --- Filter Petugas yang Input (Dicatat Oleh) --- (Visible hanya untuk Admin?)
                // Jika Anda ingin Petugas hanya melihat data yg dia input, logika getEloquentQuery perlu diubah.
                // Jika Petugas boleh lihat semua data kelompoknya (siapapun yg input), filter ini bisa visible untuk Admin saja.
                SelectFilter::make('user_id')
                    ->label('Filter Dicatat Oleh')
                    ->relationship('user', 'name') // Relasi ke user yg input record distribusi
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => $user?->hasRole('Admin') ?? false), // <-- Visible hanya untuk Admin

                 // --- Filter Petugas PJ Kelompok --- (Visible hanya untuk Admin)
                 SelectFilter::make('petugas_pj')
                    ->label('Filter Petugas PJ Kelompok')
                    // Query builder untuk filter relasi bertingkat
                    ->query(fn (Builder $query, array $data): Builder =>
                        $query->when($data['value'], fn ($q, $v) =>
                           $q->whereHas('kelompok', fn($kq) => $kq->where('user_id', $v))
                        )
                    )
                    // Relationship untuk mendapatkan opsi petugas
                    ->relationship('kelompok.petugas', 'name', fn (Builder $query) => $query->role('Petugas Lapangan'))
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => $user?->hasRole('Admin') ?? false), // <-- Visible hanya untuk Admin
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(), // Akan menampilkan detail & relation manager
                    EditAction::make(),
                    DeleteAction::make()
                        ->visible(fn () => auth()->user()->can('delete_distribusi::bantuan')), // Sesuaikan permission
                ])->tooltip("Opsi Lain"),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('delete_distribusi::bantuan')), // Sesuaikan permission
                        // --- Tambahkan Export Bulk Action ---
                    ExportBulkAction::make()
                        ->visible(fn (): bool => Auth::user()?->hasRole('Admin') ?? false),
                // ------------------------------------
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->striped()
            ->defaultSort('tanggal_distribusi', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Informasi Penerima')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('kelompok.nama_kelompok')->label('Nama Kelompok'),
                        TextEntry::make('kelompok.desa.nama_desa')->label('Desa/Kelurahan'),
                        TextEntry::make('kelompok.desa.kecamatan.nama_kecamatan')->label('Kecamatan'),
                    ]),
                InfolistSection::make('Detail Bantuan')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('bantuan.nama_bantuan')->label('Jenis Bantuan'),
                        TextEntry::make('jumlah'),
                        TextEntry::make('satuan'),
                        TextEntry::make('tanggal_distribusi')->date('d F Y'),
                        TextEntry::make('status_pemberian')->badge()->color(fn (string $state): string => match ($state) {
                            'Direncanakan' => 'gray',
                            'Proses Pengiriman' => 'info',
                            'Diterima Kelompok' => 'success',
                            'Dibatalkan' => 'danger',
                            default => 'gray',
                        }),
                        TextEntry::make('harga_awal_total')->label('Estimasi Nilai')->money('IDR'),
                        Grid::make(1)->schema([
                             TextEntry::make('catatan_distribusi')->placeholder('Tidak ada catatan.'),
                        ])->columnSpanFull(),
                        TextEntry::make('user.name')->label('Dicatat Oleh'),
                        TextEntry::make('created_at')->label('Waktu Pencatatan')->dateTime('d F Y H:i:s'),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Daftarkan Relation Manager yang sudah dibuat
            // RelationManagers\MonitoringPemanfaatansRelationManager::class,
            RelationManagers\PelaporanHasilsRelationManager::class, // Daftarkan ini
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDistribusiBantuans::route('/'),
            'create' => Pages\CreateDistribusiBantuan::route('/create'),
            'view' => Pages\ViewDistribusiBantuan::route('/{record}'), // Halaman view aktif
            'edit' => Pages\EditDistribusiBantuan::route('/{record}/edit'),
        ];
    }

     /**
      * Modifikasi query dasar untuk Resource ini.
      */
    public static function getEloquentQuery(): Builder // <-- Pastikan return type Builder
    {
        $user = Auth::user();
        // Mulai dengan query dasar dan eager load
        $query = parent::getEloquentQuery()->with([
            'kelompok.desa.kecamatan', // Eager load sampai kecamatan
            'kelompok.petugas',
            'bantuan',
            'user'
        ]);

        // Jika user login dan BUKAN Admin, filter berdasarkan user_id di kelompok terkait
        if ($user && !$user->hasRole('Admin')) {
            $query->whereHas('kelompok', function (Builder $q) use ($user) { // <-- Gunakan Builder di closure
                $q->where('user_id', $user->id);
            });
        }

        return $query;
    }
}
