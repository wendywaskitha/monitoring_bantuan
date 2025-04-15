<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Set;
use App\Models\Kelompok;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Number;
use Filament\Resources\Resource;
use Dotswan\MapPicker\Fields\Map;
use App\Models\Desa; // Import Desa
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use App\Models\Anggota; // Import Anggota
use Dotswan\MapPicker\Infolists\MapEntry;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists; // Import Infolists
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Validation\Rule; // Import Rule
use App\Filament\Resources\KelompokResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use App\Filament\Forms\Components\MapLeafletPickerField;
use Filament\Infolists\Infolist; // Import Infolist class
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\KelompokResource\RelationManagers;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\Grid; // Untuk layout infolist
use App\Filament\Resources\KelompokResource\Pages as KelompokPages;
use App\Filament\Resources\KelompokResource\Pages\KelolaEvaluasiKelompok;

class KelompokResource extends Resource
{
    // use HasExportableTable;
    protected static ?string $model = Kelompok::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Manajemen Bantuan'; // Grup baru
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'nama_kelompok';
    protected static ?string $pluralModelLabel = 'Kelompok Penerima';
    protected static ?string $modelLabel = 'Kelompok';


    public static function form(Form $form): Form
    {
        $jenisKelompokOptions = [
            'Pertanian' => 'Pertanian',
            'Peternakan' => 'Peternakan',
            'Perikanan' => 'Perikanan',
        ];

        return $form
            ->schema([
                Section::make('Informasi Dasar Kelompok')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nama_kelompok')
                            ->label('Nama Kelompok')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true) // Agar validasi unique bisa cek desa_id
                            // Unique berdasarkan desa_id
                            ->rule(static function (Forms\Get $get, ?Kelompok $record): \Illuminate\Validation\Rules\Unique {
                                $desaId = $get('desa_id');
                                $rule = Rule::unique('kelompoks', 'nama_kelompok')
                                            ->where('desa_id', $desaId);
                                if ($record) {
                                    $rule->ignore($record->id);
                                }
                                return $rule;
                            })
                            ->validationAttribute('Nama Kelompok'),

                        ToggleButtons::make('jenis_kelompok')
                            ->label('Jenis Kelompok')
                            ->options($jenisKelompokOptions)
                            ->required()
                            ->inline()
                            ->grouped()
                            ->colors([
                                'Pertanian' => 'success',
                                'Peternakan' => 'warning',
                                'Perikanan' => 'info',
                            ])
                            ->columnSpanFull(), // Ambil lebar penuh di kolom ini

                        Select::make('desa_id')
                            ->label('Desa / Kelurahan')
                            ->relationship(
                                name: 'desa', // Nama relasi di model Kelompok
                                titleAttribute: 'nama_desa', // Kolom yg ditampilkan di opsi
                                // Kustomisasi query untuk load kecamatan juga (untuk search/display)
                                modifyQueryUsing: fn (Builder $query) => $query->with('kecamatan')
                            )
                            ->getOptionLabelFromRecordUsing(fn (Desa $record) => "{$record->nama_desa} (Kec. {$record->kecamatan->nama_kecamatan})") // Tampilkan nama desa & kecamatan
                            ->searchable(['nama_desa', 'kecamatan.nama_kecamatan']) // Cari berdasarkan desa & kecamatan
                            ->preload()
                            ->live() // Agar validasi nama kelompok bisa pakai desa_id terpilih
                            ->required()
                            ->helperText('Pilih desa lokasi kelompok.'),

                        DatePicker::make('tanggal_dibentuk')
                            ->label('Tanggal Pembentukan')
                            ->native(false), // Gunakan date picker JS

                        // ketua_id TIDAK diatur di sini, tapi melalui AnggotaRelationManager
                        // Select::make('ketua_id')
                        //     ->label('Ketua Kelompok')
                        //     ->options(function (callable $get, ?Kelompok $record) {
                        //         // Hanya bisa diisi saat edit dan anggota sudah ada
                        //         if ($record) {
                        //             return $record->anggotas()->pluck('nama_anggota', 'id');
                        //         }
                        //         return [];
                        //     })
                        //     ->searchable()
                        //     ->placeholder('Pilih dari Anggota (setelah disimpan)'),

                        Textarea::make('alamat_sekretariat')
                            ->label('Alamat Sekretariat')
                            ->rows(3)
                            ->columnSpanFull(),

                            // --- GUNAKAN DOTSWAN MAP PICKER ---
                        Map::make('location') // Nama field virtual (tidak disimpan langsung)
                            ->label('Pilih Lokasi di Peta (Leaflet)')
                            ->columnSpanFull()

                            // Controls
                            ->showFullscreenControl(true)
                            ->showZoomControl(true)
                            ->tilesUrl("https://tile.openstreetmap.de/{z}/{x}/{y}.png")
                            // ->tileLayerAttribution(config('filament-map-picker.attribution', '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'))
                            // ->defaultZoom(config('filament-map-picker.default_zoom', 10)) // Ambil dari config
                            ->defaultLocation(latitude: -4.84, longitude: 122.49)
                            // ->defaultLocation(config('filament-map-picker.default_location', [-4.84, 122.49])) // Ambil dari config
                            // Extra Customization
                            ->extraStyles([
                                'min-height: 50vh',
                                'border-radius: 5px'
                            ])
                            // State Management
                            ->afterStateUpdated(function (Set $set, ?array $state): void {
                                $set('latitude', $state['lat']);
                                $set('longitude', $state['lng']);
                                // $set('geojson', json_encode($state['geojson']));
                            })
                            ->afterStateHydrated(function (Set $set, ?Kelompok $record): void {
                                if ($record && $record->latitude && $record->longitude) {
                                    // Set state internal map picker
                                    $set('map_location_picker', [ // <-- Gunakan nama baru di sini juga
                                        'lat' => $record->latitude,
                                        'lng' => $record->longitude,
                                    ]);
                                }
                            })
                            ->helperText('Klik pada peta untuk menandai lokasi kelompok.'),
                            // ->dehydrated(),
                            // ->draggable() // Cek dokumentasi apakah marker draggable by default/opsi
                            // ->liveLocation() // Cek dokumentasi untuk fitur ini
                        // ------------------------------------

                        // --- Field Latitude & Longitude (bisa disembunyikan/disabled) ---
                        TextInput::make('latitude')
                            ->numeric()
                            ->readOnly() // Dibuat disabled karena diisi oleh MapPicker
                            ->nullable()
                            ->visible(fn() => auth()->user()->hasRole('Admin')), // Hanya Admin yg lihat?
                        TextInput::make('longitude')
                            ->numeric()
                            ->readOnly()
                            ->nullable()
                            ->visible(fn() => auth()->user()->hasRole('Admin')),

                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        $jenisKelompokOptions = [
            'Pertanian' => 'Pertanian',
            'Peternakan' => 'Peternakan',
            'Perikanan' => 'Perikanan',
        ];

        $user = Auth::user(); // Dapatkan user saat ini

        return $table
            ->columns([
                TextColumn::make('id')->sortable()->label('ID'),
                TextColumn::make('nama_kelompok')
                    ->searchable()
                    ->sortable()
                    // Modifikasi description untuk info ketua & bantuan diterima
                    ->description(function (Kelompok $record): string {
                        $ketua = $record->ketua?->nama_anggota ?? 'Belum Ditentukan';
                        // Ambil daftar nama bantuan unik yang diterima (limit untuk tampilan)
                        $bantuanDiterima = $record->distribusiBantuans()
                            ->where('status_pemberian', 'Diterima Kelompok')
                            ->with('bantuan') // Eager load bantuan
                            ->get()
                            ->pluck('bantuan.nama_bantuan') // Ambil nama bantuan
                            ->unique() // Hanya nama unik
                            ->slice(0, 3) // Ambil maks 3 nama
                            ->implode(', ');

                        if (strlen($bantuanDiterima) > 0 && $record->distribusiBantuans()->where('status_pemberian', 'Diterima Kelompok')->count() > 3) {
                            $bantuanDiterima .= ', ...'; // Tambah elipsis jika lebih dari 3
                        } elseif (strlen($bantuanDiterima) == 0) {
                            $bantuanDiterima = 'Belum ada';
                        }

                        return "Ketua: {$ketua} | Bantuan: {$bantuanDiterima}";
                    })
                    ->wrap(), // Aktifkan wrap untuk description
                BadgeColumn::make('jenis_kelompok')
                    ->label('Jenis')
                    ->colors([
                        'success' => 'Pertanian',
                        'warning' => 'Peternakan',
                        'info' => 'Perikanan',
                        'primary' => fn ($state) => !in_array($state, ['Pertanian', 'Peternakan', 'Perikanan']),
                    ])
                    ->searchable()
                    ->sortable(),
                // TextColumn::make('ketua.nama_anggota') // Sudah di description nama kelompok
                //     ->label('Ketua')
                //     ->placeholder('Belum Ditentukan')
                //     ->searchable()
                //     ->sortable(),
                TextColumn::make('desa.nama_desa')
                    ->label('Lokasi')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        // Custom search untuk desa dan kecamatan
                        return $query->whereHas('desa', function ($q) use ($search) {
                                    $q->where('nama_desa', 'like', "%{$search}%")
                                      ->orWhereHas('kecamatan', fn($q2) => $q2->where('nama_kecamatan', 'like', "%{$search}%"));
                                });
                    })
                    ->sortable()
                    ->description(fn (Kelompok $record): string => "Kec. {$record->desa->kecamatan->nama_kecamatan}"),
                TextColumn::make('petugas.name') // Kolom Petugas PJ
                    ->label('Petugas PJ')
                    ->placeholder('Belum Ditugaskan')
                    ->searchable() // Search di kolom ini mungkin perlu query kustom jika ingin akurat
                    ->sortable()
                    ->visible(fn (): bool => $user?->hasRole('Admin') ?? false) // Hanya Admin
                    ->toggleable(isToggledHiddenByDefault: true),
                    // --- KOLOM BARU: Total Nilai Bantuan ---
                TextColumn::make('total_nilai_bantuan_diterima') // Panggil accessor
                    ->label('Total Bantuan (Rp)')
                    ->money('IDR', 0) // Format sebagai Rupiah
                    // ->sortable() // Sortable berdasarkan nilai accessor
                    ->alignRight(), // Rata kanan
                // ----------------------------------------------------
                TextColumn::make('anggotas_count')
                    ->counts('anggotas')
                    ->label('Jml Anggota')
                    ->alignCenter() // Rata tengah
                    ->sortable(),
                TextColumn::make('tanggal_dibentuk')
                    ->label('Tgl Dibentuk')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // --- Filter Jenis Kelompok (Visible untuk semua) ---
                SelectFilter::make('jenis_kelompok')
                    ->label('Filter Jenis')
                    ->options($jenisKelompokOptions)
                    ->multiple(),

                // --- Filter Kecamatan (Visible untuk semua) ---
                SelectFilter::make('kecamatan')
                    ->label('Filter Kecamatan')
                    // Query relationship akan otomatis terfilter oleh getEloquentQuery untuk Petugas
                    ->relationship('desa.kecamatan', 'nama_kecamatan')
                    ->searchable()
                    ->preload(),

                // --- Filter Desa (Visible untuk semua) ---
                SelectFilter::make('desa_id')
                    ->label('Filter Desa')
                    // Query relationship akan otomatis terfilter oleh getEloquentQuery untuk Petugas
                    ->relationship('desa', 'nama_desa', function (Builder $query) use ($user) {
                        // Opsional: Jika ingin opsi desa juga difilter berdasarkan petugas
                        // if ($user && !$user->hasRole('Admin')) {
                        //    $query->whereHas('kelompoks', fn($kq) => $kq->where('user_id', $user->id));
                        // }
                        // Namun, biasanya membiarkan semua desa tampil lebih baik,
                        // karena tabel utama sudah difilter.
                    })
                    ->searchable()
                    ->preload(),

                // --- Filter Petugas PJ (Visible HANYA untuk Admin) ---
                SelectFilter::make('user_id') // Filter langsung pada kolom user_id
                    ->label('Filter Petugas PJ')
                    ->relationship('petugas', 'name', fn (Builder $query) => $query->role('Petugas Lapangan')) // Ambil opsi dari user dgn role petugas
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => $user?->hasRole('Admin') ?? false), // <-- Kunci visibilitas

                // Filter untuk menampilkan yang belum ditugaskan (Hanya Admin)
                Tables\Filters\Filter::make('belum_ditugaskan')
                    ->label('Hanya Belum Ditugaskan')
                    ->query(fn (Builder $query): Builder => $query->whereNull('user_id'))
                    ->visible(fn (): bool => $user?->hasRole('Admin') ?? false),
            ])
            // --- Tambahkan Header Action Ekspor v2.x ---
            ->headerActions([
                ExportAction::make() // <-- Gunakan ExportAction
                    ->label('Ekspor Excel')
                    // ->color('primary')
                    // Konfigurasi ekspor mungkin sedikit berbeda di v2.x
                    // Biasanya langsung konfigurasi di sini atau defaultnya fromTable
                    // ->withFilename('Daftar Kelompok - ' . date('Ymd'))
                    // ->withWriterType(\Maatwebsite\Excel\Excel::XLSX) // Contoh set tipe writer
                    // ->onlyColumns(['id', 'nama_kelompok', ...]) // Pilih kolom spesifik
                    // ->exceptColumns(['created_at', 'updated_at']) // Kecualikan kolom
                    ->visible(fn (): bool => Auth::user()?->hasRole('Admin') ?? false), // Hanya Admin
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(), // Akan mengarah ke halaman view dengan relation manager
                    EditAction::make(),
                    // --- Tambahkan Action Cetak PDF ---
                    Action::make('cetakPdf')
                        ->label('Cetak PDF')
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        // Buka URL route PDF di tab baru
                        ->url(fn (Kelompok $record): string => route('report.kelompok.pdf', $record), shouldOpenInNewTab: true)
                        // Kontrol akses jika perlu
                        ->visible(fn (): bool => Auth::user()?->hasRole('Admin') ?? false), // Misal hanya Admin
                    // ---------------------------------
                    DeleteAction::make()
                        ->visible(fn (?Kelompok $record) => auth()->user()->can('delete_kelompok', $record)), // Hanya yg boleh hapus
                ])->tooltip("Opsi Lain"),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                     ->visible(fn () => Auth::user()->can('delete_any_kelompok')),
                     // --- TAMBAHKAN EXPORT BULK ACTION ---
                    ExportBulkAction::make() // <-- Tambahkan ini
                    // Opsional: Batasi hanya untuk Admin
                    ->visible(fn (): bool => Auth::user()?->hasRole('Admin') ?? false),
                // ------------------------------------ // Hanya yg boleh bulk delete
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->striped()
            ->defaultSort('created_at', 'desc');
    }

    // --- getEloquentQuery() (Pastikan eager load relasi yang dibutuhkan) ---
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery()->with([
            'desa.kecamatan',
            'ketua',
            'petugas',
            // Eager load relasi bantuan untuk description & accessor total nilai
            'distribusiBantuans.bantuan'
        ]);

        // Tambahkan perhitungan total nilai bantuan untuk sorting/filtering jika perlu
        // $query->withSum(['distribusiBantuans as total_nilai_bantuan_agg' => function ($q) {
        //     $q->where('status_pemberian', 'Diterima Kelompok');
        // }], 'harga_awal_total');

        if ($user && !$user->hasRole('Admin')) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }



    // Definisikan Infolist untuk halaman View
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Section Informasi Dasar Kelompok
                InfolistSection::make('Informasi Kelompok')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('nama_kelompok'),
                        TextEntry::make('jenis_kelompok')->badge()->color(fn (string $state): string => match ($state) {
                            'Pertanian' => 'success',
                            'Peternakan' => 'warning',
                            'Perikanan' => 'info',
                            default => 'gray',
                        }),
                        TextEntry::make('ketua.nama_anggota')->label('Ketua Kelompok')->placeholder('Belum ditentukan'),
                        // Tampilkan Petugas PJ hanya untuk Admin
                        TextEntry::make('petugas.name')
                            ->label('Petugas PJ')
                            ->placeholder('Belum Ditugaskan')
                            ->visible(fn() => Auth::user()?->hasRole('Admin')), // Cek null safety
                        Grid::make(1)->schema([
                             TextEntry::make('alamat_sekretariat')
                                ->placeholder('Tidak ada data')
                                ->extraAttributes(['class' => 'whitespace-normal break-words']), // Wrap text
                        ])->columnSpan(fn() => Auth::user()?->hasRole('Admin') ? 1 : 2), // Sesuaikan span
                        TextEntry::make('tanggal_dibentuk')->date('d F Y'),
                    ]),

                // Section Lokasi
                InfolistSection::make('Lokasi')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('desa.nama_desa')->label('Desa / Kelurahan'),
                        TextEntry::make('desa.kecamatan.nama_kecamatan')->label('Kecamatan'),
                        // MapEntry::make('location')->coordinates('latitude', 'longitude')
                    ]),

                // --- Section Baru untuk Ringkasan Kinerja, Perkembangan & Stok ---
                InfolistSection::make('Ringkasan Kinerja, Perkembangan & Stok')
                    ->collapsible()
                    ->schema(function (Kelompok $record): array { // Gunakan closure untuk akses record
                        $entries = []; // Inisialisasi array untuk semua entri

                        // --- Data Kinerja Umum (Selalu Tampil) ---
                        $entries[] = TextEntry::make('total_nilai_bantuan_diterima')
                            ->label('Total Est. Bantuan Diterima')
                            ->money('IDR', 0)->color('primary')->weight('medium')
                            ->hintIcon('heroicon-o-gift')
                            ->columnSpan(1); // Span 1

                        // Asumsi accessor total_hasil_penjualan ada dan relevan
                        $entries[] = TextEntry::make('total_hasil_penjualan')
                            ->label('Total Penjualan Dilaporkan')
                            ->money('IDR', 0)->color('success')->weight('medium')
                            ->hintIcon('heroicon-o-currency-dollar')
                            ->placeholder('Rp 0')
                            ->columnSpan(1); // Span 1

                        // Asumsi accessor rata_rata_perkembangan_terakhir ada dan relevan
                        $entries[] = TextEntry::make('rata_rata_perkembangan_terakhir')
                            ->label('Rata-rata Perkembangan (%)')
                            ->numeric(decimalPlaces: 1)->suffix('%')
                            ->color('warning')->weight('medium')
                            ->placeholder('N/A')
                            ->hintIcon('heroicon-o-clipboard-document-check')
                            ->columnSpan(1); // Span 1

                        // --- Data Stok Spesifik Berdasarkan Jenis Kelompok ---
                        $stokEntries = []; // Array terpisah untuk entri stok

                        // Peternakan
                        if ($record->jenis_kelompok === 'Peternakan') {
                            $stokEntries[] = TextEntry::make('estimasi_jumlah_ternak_saat_ini') // Panggil accessor dinamis
                                ->label('Estimasi Ternak Saat Ini')
                                ->numeric()->suffix(' Ekor')->placeholder('N/A')
                                ->columnSpan(1); // Span 1
                            $stokEntries[] = TextEntry::make('total_bantuan_ternak_diterima') // Panggil accessor total bantuan
                                ->label('Total Bantuan Ternak')
                                ->numeric()->suffix(' Ekor')->placeholder('N/A')
                                ->columnSpan(1); // Span 1
                            // Tambahkan kolom kosong jika perlu agar pas 3 kolom
                            $stokEntries[] = TextEntry::make('placeholder_ternak')->label(false)->hidden()->columnSpan(1);
                        }
                        // Perikanan (Budidaya)
                        elseif ($record->jenis_kelompok === 'Perikanan') {
                            $stokEntries[] = TextEntry::make('estimasi_jumlah_ikan_saat_ini') // Panggil accessor dinamis
                                ->label('Estimasi Ikan Saat Ini')
                                ->numeric()->suffix(' Ekor')->placeholder('N/A')
                                ->columnSpan(1);
                             $stokEntries[] = TextEntry::make('total_bantuan_ikan_diterima') // Panggil accessor total bantuan
                                ->label('Total Bibit Ikan')
                                ->numeric()->suffix(' Ekor')->placeholder('N/A')
                                ->columnSpan(1);
                             $stokEntries[] = TextEntry::make('placeholder_ikan')->label(false)->hidden()->columnSpan(1);
                        }
                        // Pertanian (Bibit) - Tetap pakai snapshot terakhir
                        elseif ($record->jenis_kelompok === 'Pertanian') {
                             $stokBibitTerakhir = $record->jumlah_stok_bibit_terakhir;
                             $satuanStokBibit = $record->satuan_stok_bibit_terakhir;
                             $bantuanBibitAwal = $record->total_bantuan_bibit_pertanian_diterima;

                             $stokEntries[] = TextEntry::make('jumlah_stok_bibit_terakhir')
                                ->label('Stok Bibit Disimpan (Terakhir)')
                                ->state($stokBibitTerakhir)
                                ->numeric(decimalPlaces: 2)
                                ->suffix($satuanStokBibit ? ' ' . e($satuanStokBibit) : '')
                                ->placeholder('N/A')
                                ->columnSpan(1);
                             $stokEntries[] = TextEntry::make('total_bantuan_bibit_pertanian_diterima')
                                ->label('Total Bantuan Bibit')
                                ->state($bantuanBibitAwal ? $bantuanBibitAwal['jumlah'] : null)
                                ->numeric(decimalPlaces: 2)
                                ->suffix($bantuanBibitAwal ? ' ' . e($bantuanBibitAwal['satuan']) : '')
                                ->placeholder('N/A')
                                ->columnSpan(1);
                             $stokEntries[] = TextEntry::make('placeholder_pertanian')->label(false)->hidden()->columnSpan(1);
                        }

                        // Gabungkan entri stok ke entri utama jika ada
                        if (!empty($stokEntries)) {
                            // --- GANTI Placeholder DENGAN ViewEntry UNTUK SEPARATOR ---
                            $entries[] = Infolists\Components\ViewEntry::make('stok_separator')
                                ->view('infolists.components.hr-separator') // Buat view Blade ini
                                ->columnSpanFull(); // Separator ambil lebar penuh
                            // ---------------------------------------------------------

                            // Gabungkan array
                            $entries = array_merge($entries, $stokEntries);
                        }
                        // Tidak perlu placeholder 'no_stok_info' lagi karena data umum selalu ada

                        return $entries; // Kembalikan semua entri
                    })->columns(3), // Tetapkan jumlah kolom untuk section ini
                // ---------------------------------------------

                // Section Penilaian Terakhir (Jika pakai Alternatif 2)
                // InfolistSection::make('Penilaian Terakhir')
                //     ->collapsible()
                //     ->schema([ /* ... schema penilaian ... */ ])
                //     ->columns(2),

            ]); // Akhir Schema Infolist Utama
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AnggotasRelationManager::class,
            RelationManagers\DokumenKelompoksRelationManager::class,
            RelationManagers\DistribusiBantuansRelationManager::class,
            // RelationManagers\EvaluasiKelompoksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKelompoks::route('/'),
            'create' => Pages\CreateKelompok::route('/create'),
            'view' => Pages\ViewKelompok::route('/{record}'), // Halaman view aktif
            'edit' => Pages\EditKelompok::route('/{record}/edit'),
            // 'evaluasi' => \App\Filament\Resources\KelompokResource\Pages\KelolaEvaluasiKelompok::route('/evaluasi'),
        ];
    }
}
