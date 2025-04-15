<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Infolists;
use App\Models\Kelompok;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\PelaporanHasil;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use App\Models\DistribusiBantuan;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;
use App\Models\MonitoringPemanfaatan;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Get; // Import Get
use Filament\Forms\Components\Section;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Tabs;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Illuminate\Database\Eloquent\Collection;
use Filament\Infolists\Components\ImageEntry;
use Filament\Forms\Set; // Import Set (jika perlu)
use Filament\Infolists\Components\RepeatableEntry;
use Illuminate\Support\Str; // Untuk helper string
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Filament\Forms\Components\Fieldset; // Import Fieldset
use Filament\Infolists\Components\Section as InfolistSection;
use App\Filament\Resources\MonitoringPemanfaatanResource\Pages;
use Filament\Tables\Columns\BadgeColumn; // Untuk contoh di tabel
use App\Filament\Resources\MonitoringPemanfaatanResource\RelationManagers;

class MonitoringPemanfaatanResource extends Resource
{
    protected static ?string $model = MonitoringPemanfaatan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Manajemen Bantuan';
    protected static ?int $navigationSort = 3;
    protected static ?string $pluralModelLabel = 'Monitoring Pemanfaatan';
    protected static ?string $modelLabel = 'Monitoring';
    protected static ?string $recordTitleAttribute = 'id';

    // Opsi Enum (bisa ditaruh di Model atau di sini)
    protected static array $kondisiKesehatanTernakOptions = ['Sehat' => 'Sehat', 'Sakit Ringan' => 'Sakit Ringan', 'Sakit Berat' => 'Sakit Berat'];
    protected static array $kondisiKandangOptions = ['Baik' => 'Baik', 'Cukup' => 'Cukup', 'Kurang' => 'Kurang'];
    protected static array $kondisiTanamanOptions = ['Subur' => 'Subur', 'Cukup Subur' => 'Cukup Subur', 'Kurang Subur' => 'Kurang Subur', 'Terserang Hama/Penyakit' => 'Terserang Hama/Penyakit'];
    protected static array $kondisiAlatOptions = ['Baik' => 'Baik', 'Rusak Ringan' => 'Rusak Ringan', 'Rusak Berat' => 'Rusak Berat', 'Hilang' => 'Hilang'];
    protected static array $kondisiAirKolamOptions = ['Baik' => 'Baik', 'Keruh' => 'Keruh', 'Berbau' => 'Berbau', 'Hijau Pekat' => 'Hijau Pekat'];

    public static function form(Form $form): Form
    {
        $user = Auth::user();

        return $form
            ->schema([
                Section::make('Informasi Umum Monitoring')
                    ->columns(2)
                    ->schema([
                        Select::make('distribusi_bantuan_id')
                            ->label('Item Bantuan yang Dimonitor')
                            ->relationship(
                                name: 'distribusiBantuan',
                                titleAttribute: 'id',
                                // --- MODIFIKASI QUERY FORM UNTUK PEMBATASAN PETUGAS ---
                                modifyQueryUsing: function (Builder $query) use ($user) {
                                    // Eager load relasi yang dibutuhkan untuk label & filtering
                                    $query->with(['kelompok', 'bantuan']);
                                    // Filter berdasarkan petugas jika bukan Admin
                                    if ($user && !$user->hasRole('Admin')) {
                                        // Filter distribusi yang kelompoknya ditangani user ini
                                        $query->whereHas('kelompok', function ($q) use ($user) {
                                            $q->where('user_id', $user->id);
                                        });
                                    }
                                }
                                // ----------------------------------------------------
                            )
                            ->getOptionLabelFromRecordUsing(function (DistribusiBantuan $record) {
                                $tanggal = $record->tanggal_distribusi->format('d/m/Y');
                                $jenisBantuan = $record->bantuan?->jenis_bantuan ?? 'Tidak Diketahui';
                                return "{$record->kelompok->nama_kelompok} - {$record->bantuan->nama_bantuan} ({$jenisBantuan} | Dist: {$tanggal})";
                            })
                            ->searchable(['kelompok.nama_kelompok', 'bantuan.nama_bantuan', 'bantuan.jenis_bantuan'])
                            ->preload()
                            ->live() // <-- PENTING
                            ->required()
                            ->columnSpanFull()
                            // --- Tambahkan afterStateUpdated untuk load data sebelumnya ---
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                $set('monitoring_sebelumnya_info', null);
                                $set('penjualan_terakhir_info', null); // Tambah state untuk penjualan
                                if ($state) {
                                    // Cari monitoring sebelumnya
                                    $monitoringSebelumnya = MonitoringPemanfaatan::where('distribusi_bantuan_id', $state)
                                        ->whereDate('tanggal_monitoring', '<', now()->toDateString())
                                        ->orderBy('tanggal_monitoring', 'desc')
                                        ->first();
                                    $set('monitoring_sebelumnya_info', $monitoringSebelumnya);

                                    // Cari pelaporan hasil (penjualan) terakhir SETELAH monitoring sebelumnya
                                    $tanggalFilter = $monitoringSebelumnya?->tanggal_monitoring ?? now()->subYears(10); // Tanggal acuan
                                    $penjualanTerakhir = PelaporanHasil::where('distribusi_bantuan_id', $state)
                                        ->where('tanggal_pelaporan', '>', $tanggalFilter) // Setelah monitoring terakhir
                                        ->orderBy('tanggal_pelaporan', 'desc')
                                        ->first(); // Ambil penjualan terakhir saja
                                    $set('penjualan_terakhir_info', $penjualanTerakhir);
                                }
                            }),
                        // --------------------------------------------------------------

                        // --- Placeholder untuk Info Monitoring Sebelumnya ---
                        Placeholder::make('monitoring_sebelumnya_placeholder')
                            ->label('Data Sebelumnya (Referensi)') // Ubah label
                            ->columnSpanFull()
                            ->content(function (Get $get): ?HtmlString {
                                $monitoringSebelumnya = $get('monitoring_sebelumnya_info');
                                $penjualanTerakhir = $get('penjualan_terakhir_info'); // Ambil data penjualan

                                if (!$monitoringSebelumnya && !$penjualanTerakhir) {
                                    return new HtmlString('<span class="text-sm text-gray-500 dark:text-gray-400">Belum ada data monitoring/penjualan sebelumnya untuk item ini.</span>');
                                }

                                $html = '<div class="text-sm text-gray-600 dark:text-gray-400 border-l-4 border-primary-500 pl-4 py-2 space-y-1">';
                                if ($monitoringSebelumnya) {
                                    $html .= '<div class="font-medium text-gray-800 dark:text-gray-200">Monitoring Terakhir (' . $monitoringSebelumnya->tanggal_monitoring->format('d M Y') . '):</div>';
                                    // Tampilkan data monitoring sebelumnya
                                    if ($monitoringSebelumnya->jumlah_ternak_saat_ini !== null) {
                                        $html .= '<div>&nbsp;&nbsp;- Jml Ternak: ' . $monitoringSebelumnya->jumlah_ternak_saat_ini . ' Ekor</div>';
                                    }
                                    if ($monitoringSebelumnya->jumlah_ikan_hidup !== null) {
                                        $html .= '<div>&nbsp;&nbsp;- Estimasi Jml Ikan: ' . $monitoringSebelumnya->jumlah_ikan_hidup . ' Ekor</div>';
                                    }
                                    // ... (tambahkan data relevan lain dari monitoring sebelumnya) ...
                                } else {
                                    $html .= '<div class="italic">Belum ada data monitoring sebelumnya.</div>';
                                }

                                if ($penjualanTerakhir) {
                                    $html .= '<div class="mt-2 font-medium text-gray-800 dark:text-gray-200">Penjualan Terakhir Tercatat (' . $penjualanTerakhir->tanggal_pelaporan->format('d M Y') . '):</div>';
                                    // Tampilkan data penjualan terakhir
                                    if ($penjualanTerakhir->jumlah_ternak_terjual !== null) {
                                        $html .= '<div>&nbsp;&nbsp;- Ternak Terjual: ' . $penjualanTerakhir->jumlah_ternak_terjual . ' Ekor</div>';
                                    }
                                    if ($penjualanTerakhir->jumlah_dijual !== null) { // Gunakan field penjualan utama
                                        $html .= '<div>&nbsp;&nbsp;- Jumlah Dijual: ' . $penjualanTerakhir->jumlah_dijual . ' ' . ($penjualanTerakhir->satuan_dijual ?? '') . '</div>';
                                    }
                                    if ($penjualanTerakhir->total_penjualan !== null) {
                                        $html .= '<div>&nbsp;&nbsp;- Total Penjualan: Rp ' . number_format($penjualanTerakhir->total_penjualan, 0, ',', '.') . '</div>';
                                    }
                                } else {
                                    $html .= '<div class="mt-2 italic">Belum ada data penjualan tercatat setelah monitoring terakhir.</div>';
                                }

                                $html .= '</div>';
                                return new HtmlString($html);
                            })
                            ->visible(fn (Get $get) => $get('distribusi_bantuan_id') !== null),
                        // ---------------------------------------------------

                        DatePicker::make('tanggal_monitoring')
                            ->required()->native(false)->default(now()),

                        TextInput::make('persentase_perkembangan')
                            ->label('Persentase Perkembangan Umum (%)')
                            ->numeric()->required()->minValue(0)->maxValue(100)->suffix('%'),

                        Textarea::make('deskripsi_pemanfaatan')
                            ->label('Deskripsi Pemanfaatan/Perkembangan Umum')
                            ->required()->rows(3)->columnSpanFull(),
                    ]),

                // --- Fieldset Dinamis Berdasarkan Jenis Bantuan ---
                    Fieldset::make('Detail Monitoring Spesifik')
                    ->schema(function (Get $get): array {
                        $distribusiId = $get('distribusi_bantuan_id');
                        if (!$distribusiId) return []; // Kosong jika belum dipilih

                        // Ambil data bantuan sekali saja untuk efisiensi
                        $distribusi = DistribusiBantuan::with('bantuan')->find($distribusiId);
                        if (!$distribusi || !$distribusi->bantuan) return []; // Kosong jika data tidak valid

                        $jenisBantuan = $distribusi->bantuan->jenis_bantuan;
                        $namaBantuanLower = strtolower($distribusi->bantuan->nama_bantuan ?? '');
                        $satuanLower = strtolower($distribusi->bantuan->satuan ?? '');
                        // Ambil jumlah dan satuan bantuan awal untuk helper text
                        $jumlahBantuanAwal = $distribusi->jumlah;
                        $satuanBantuanAwal = $distribusi->satuan;
                        $infoBantuanAwal = ($jumlahBantuanAwal !== null && $satuanBantuanAwal) ? "Jumlah bantuan awal: {$jumlahBantuanAwal} {$satuanBantuanAwal}" : null;

                        $fields = []; // Inisialisasi array field

                        // --- Field Peternakan ---
                        if ($jenisBantuan === 'Peternakan') {
                            $fields = [
                                TextInput::make('jumlah_ternak_saat_ini')->numeric()->nullable()->minValue(0)
                                    ->label('Jumlah Ternak Saat Ini (Ekor)')
                                    ->helperText($infoBantuanAwal),
                                // Input Perubahan Stok Ternak
                                TextInput::make('jumlah_kelahiran_sejak_terakhir')->numeric()->nullable()->minValue(0)->default(0)
                                    ->label('Jumlah Kelahiran (Sejak Terakhir)'),
                                TextInput::make('jumlah_kematian_sejak_terakhir')->numeric()->nullable()->minValue(0)->default(0)
                                    ->label('Jumlah Kematian (Sejak Terakhir)'),
                                TextInput::make('jumlah_dikonsumsi_sejak_terakhir')->numeric()->nullable()->minValue(0)->default(0)
                                    ->label('Jumlah Dikonsumsi (Sejak Terakhir)'),
                                // Field Kondisi
                                Select::make('kondisi_kesehatan_ternak')->options(self::$kondisiKesehatanTernakOptions)->nullable()
                                    ->label('Kondisi Kesehatan Rata-rata'),
                                Select::make('kondisi_kandang')->options(self::$kondisiKandangOptions)->nullable()
                                    ->label('Kondisi Kandang'),
                                Textarea::make('pemanfaatan_pakan_ternak')->rows(2)->nullable()
                                    ->label('Catatan Pemanfaatan Pakan'),
                            ];
                        }

                        // --- Field Pertanian ---
                        elseif ($jenisBantuan === 'Pertanian') {
                            // Logika deteksi sub-kategori (PERLU DISEMPURNAKAN SESUAI DATA ANDA)
                            $isBibit = Str::contains($namaBantuanLower, ['bibit', 'benih', 'padi', 'jagung']);
                            $isPupuk = Str::contains($namaBantuanLower, ['pupuk', 'urea', 'npk', 'organik']);
                            $isAlat = in_array($satuanLower, ['unit', 'paket', 'buah', 'roll', 'liter', 'sprayer', 'pompa']); // Tambah contoh satuan alat

                            // Field yang mungkin relevan untuk banyak jenis pertanian
                            $fields = array_merge($fields, [
                                TextInput::make('luas_tanam_aktual')->numeric()->nullable()->minValue(0)->suffix('Ha')
                                    ->label('Luas Lahan Dimanfaatkan (Ha)')
                                    ->helperText($infoBantuanAwal), // Tampilkan info bantuan awal
                                Select::make('kondisi_tanaman')->options(self::$kondisiTanamanOptions)->nullable()
                                    ->label('Kondisi Tanaman Umum'),
                                TextInput::make('fase_pertumbuhan')->nullable()
                                    ->label('Fase Pertumbuhan Saat Ini'),
                            ]);

                            if ($isBibit) {
                                $fields = array_merge($fields, [
                                    TextInput::make('estimasi_hasil_panen')->numeric()->nullable()->minValue(0)
                                        ->label('Estimasi Hasil Panen'),
                                    TextInput::make('satuan_hasil_panen')->nullable()->placeholder('Kg, Ton, dll')
                                        ->label('Satuan Estimasi Panen'),
                                    // Stok Bibit Disimpan
                                    TextInput::make('jumlah_stok_bibit_disimpan')->numeric()->nullable()->minValue(0)
                                        ->label('Jumlah Stok Bibit Disimpan'),
                                    TextInput::make('satuan_stok_bibit')->nullable()->placeholder('Kg, Liter, dll')
                                        ->label('Satuan Stok Bibit'),
                                    // Konsumsi Hasil Panen
                                    TextInput::make('jumlah_dikonsumsi_sejak_terakhir')->numeric()->nullable()->minValue(0)->default(0)
                                        ->label('Jumlah Hasil Dikonsumsi (Sejak Terakhir)'),
                                ]);
                            }
                            if ($isPupuk) {
                                $fields = array_merge($fields, [
                                    Textarea::make('pemanfaatan_alat')->label('Cara & Waktu Pemupukan')->rows(2)->nullable(),
                                ]);
                            }
                            if ($isAlat && !$isPupuk && !$isBibit) { // Hanya jika murni alat
                                $fields = array_merge($fields, [
                                    Select::make('kondisi_alat')->options(self::$kondisiAlatOptions)->nullable()
                                        ->label('Kondisi Alat'),
                                    Textarea::make('pemanfaatan_alat')->label('Cara Pemanfaatan Alat')->rows(2)->nullable(),
                                    TextInput::make('frekuensi_penggunaan_alat')->numeric()->nullable()->minValue(0)
                                        ->label('Frekuensi Penggunaan'),
                                    TextInput::make('satuan_frekuensi_alat')->nullable()->placeholder('kali/minggu, Ha/musim, dll')
                                        ->label('Satuan Frekuensi'),
                                    // Perubahan Stok Alat
                                    TextInput::make('jumlah_rusak_hilang_sejak_terakhir')->numeric()->nullable()->minValue(0)->default(0)
                                        ->label('Jumlah Alat Rusak/Hilang (Sejak Terakhir)'),
                                ]);
                            }
                        }

                        // --- Field Perikanan ---
                        elseif ($jenisBantuan === 'Perikanan') {
                            $isBibitIkan = Str::contains($namaBantuanLower, ['bibit', 'benih', 'nener']);
                            $isPakanIkan = Str::contains($namaBantuanLower, 'pakan');
                            $isAlatIkan = in_array($satuanLower, ['unit', 'paket', 'roll', 'lembar', 'buah', 'jaring', 'perahu', 'aerator', 'terpal']); // Tambah contoh satuan

                            if ($isBibitIkan) {
                                $fields = array_merge($fields, [
                                    TextInput::make('jumlah_ikan_hidup')->numeric()->nullable()->minValue(0)
                                        ->label('Estimasi Jumlah Ikan Hidup')
                                        ->helperText($infoBantuanAwal),
                                    // Perubahan Stok Ikan
                                    TextInput::make('jumlah_kelahiran_sejak_terakhir')->numeric()->nullable()->minValue(0)->default(0)
                                        ->label('Jumlah Penambahan (Sejak Terakhir)'),
                                    TextInput::make('jumlah_kematian_sejak_terakhir')->numeric()->nullable()->minValue(0)->default(0)
                                        ->label('Jumlah Kematian/Hilang (Sejak Terakhir)'),
                                    TextInput::make('jumlah_dikonsumsi_sejak_terakhir')->numeric()->nullable()->minValue(0)->default(0)
                                        ->label('Jumlah Dikonsumsi (Sejak Terakhir)'),
                                    // Kondisi Budidaya
                                    TextInput::make('ukuran_ikan_rata2')->nullable()->placeholder('Contoh: 10-12 cm')
                                        ->label('Ukuran Ikan Rata-rata'),
                                    Select::make('kondisi_air_kolam')->options(self::$kondisiAirKolamOptions)->nullable()
                                        ->label('Kondisi Air Kolam'),
                                ]);
                            }
                            if ($isPakanIkan) {
                                $fields = array_merge($fields, [
                                    Textarea::make('pemberian_pakan_ikan')->rows(2)->nullable()
                                        ->label('Cara & Frekuensi Pakan')
                                        ->helperText($infoBantuanAwal),
                                ]);
                            }
                            if ($isAlatIkan && !$isBibitIkan && !$isPakanIkan) { // Hanya jika murni alat/sarana
                                $fields = array_merge($fields, [
                                    Select::make('kondisi_alat')->options(self::$kondisiAlatOptions)->nullable()
                                        ->label('Kondisi Alat/Sarana')
                                        ->hint($infoBantuanAwal),
                                    Textarea::make('pemanfaatan_alat')->label('Cara Pemanfaatan Alat/Sarana')->rows(2)->nullable(),
                                    TextInput::make('frekuensi_penggunaan_alat')->numeric()->nullable()->minValue(0)
                                        ->label('Frekuensi Penggunaan'),
                                    TextInput::make('satuan_frekuensi_alat')->nullable()->placeholder('kali/minggu, trip, dll')
                                        ->label('Satuan Frekuensi'),
                                    // Perubahan Stok Alat
                                    TextInput::make('jumlah_rusak_hilang_sejak_terakhir')->numeric()->nullable()->minValue(0)->default(0)
                                        ->label('Jumlah Alat Rusak/Hilang (Sejak Terakhir)'),
                                    // Hasil Tangkapan (jika alat tangkap)
                                    TextInput::make('estimasi_hasil_tangkapan')->numeric()->nullable()->minValue(0)
                                        ->label('Estimasi Hasil Tangkapan'),
                                    TextInput::make('satuan_hasil_tangkapan')->nullable()->default('Kg')
                                        ->label('Satuan Hasil Tangkapan'),
                                ]);
                            }
                        }

                        // Jika tidak ada field spesifik yang cocok, mungkin tambahkan field catatan umum tambahan
                        if (empty($fields) && $jenisBantuan) {
                            $fields[] = Textarea::make('catatan_monitoring_tambahan')
                                ->label("Catatan Tambahan untuk Bantuan {$jenisBantuan}")
                                ->rows(3)
                                ->columnSpanFull();
                        }


                        return $fields; // Kembalikan array field dinamis
                    })
                    ->columns(2) // Layout kolom untuk fieldset
                    ->visible(fn (Get $get) => $get('distribusi_bantuan_id') !== null), // Hanya tampil jika bantuan dipilih

                // --- Field Umum Lainnya ---
                Section::make('Dokumentasi & Catatan Umum')
                    ->schema([
                         FileUpload::make('foto_dokumentasi')
                            ->label('Foto Dokumentasi')
                            ->disk('public')->directory('monitoring-docs')->image()->maxSize(2048)->imageEditor()
                            ->columnSpanFull(),
                        Textarea::make('catatan_monitoring')
                            ->label('Catatan Tambahan Petugas (Umum)')
                            ->rows(3)->columnSpanFull(),
                        Hidden::make('user_id')->default(auth()->id()),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();

        return $table
            ->columns([
                TextColumn::make('id')->sortable()->label('ID'),
                TextColumn::make('distribusiBantuan.kelompok.nama_kelompok')
                    ->label('Kelompok')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('distribusiBantuan.bantuan.nama_bantuan')
                    ->label('Bantuan')
                    ->searchable()
                    ->sortable()
                    ->description(fn(MonitoringPemanfaatan $r) => "Dist: " . $r->distribusiBantuan->tanggal_distribusi->format('d/m/y')),
                TextColumn::make('tanggal_monitoring')
                    ->date('d M Y')
                    ->sortable(),
                // Tampilkan beberapa data kunci spesifik (contoh)
                TextColumn::make('jumlah_ternak_saat_ini')
                    ->label('Jml Ternak')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric(),
                TextColumn::make('luas_tanam_aktual')
                    ->label('Luas Tanam (Ha)')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric(decimalPlaces: 2),
                 TextColumn::make('jumlah_ikan_hidup')
                    ->label('Jml Ikan')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric(),
                BadgeColumn::make('persentase_perkembangan') // Tetap tampilkan persentase umum
                    ->label('Perkembangan (%)')
                    ->suffix('%')
                    ->colors([
                        'danger' => fn ($state): bool => $state < 25,
                        'warning' => fn ($state): bool => $state >= 25 && $state < 75,
                        'success' => fn ($state): bool => $state >= 75,
                        'gray'
                    ])
                    ->sortable(),
                ImageColumn::make('foto_dokumentasi')
                    ->label('Foto')
                    ->disk('public')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.name')
                    ->label('Petugas')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    // Tambahkan kolom Petugas PJ Kelompok (visible for Admin)
                 TextColumn::make('distribusiBantuan.kelompok.petugas.name')
                    ->label('Petugas PJ Kelompok')
                    ->placeholder('-')
                    ->visible(fn (): bool => $user?->hasRole('Admin') ?? false)
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
                SelectFilter::make('kelompok')
                    ->label('Filter Kelompok')
                    ->relationship('distribusiBantuan.kelompok', 'nama_kelompok', function (Builder $query) use ($user) {
                         // Terapkan filter petugas juga di sini jika bukan Admin
                         if ($user && !$user->hasRole('Admin')) {
                            $query->where('user_id', $user->id);
                        }
                    })
                    ->searchable()->preload(),
                SelectFilter::make('bantuan')
                    ->label('Filter Bantuan')
                    ->relationship('distribusiBantuan.bantuan', 'nama_bantuan')
                    ->searchable()->preload()
                    ->visible(fn (): bool => $user?->hasRole('Admin') ?? false), // <-- Tambahkan visible(),
                SelectFilter::make('jenis_bantuan') // Filter berdasarkan jenis utama
                    ->label('Filter Jenis Bantuan')
                    ->options(['Pertanian' => 'Pertanian', 'Peternakan' => 'Peternakan', 'Perikanan' => 'Perikanan'])
                    ->query(fn (Builder $query, array $data) =>
                        $query->when($data['value'], fn ($q, $v) =>
                            $q->whereHas('distribusiBantuan.bantuan', fn($bq) => $bq->where('jenis_bantuan', $v))
                        )
                    ),
                SelectFilter::make('user_id')
                    ->label('Filter Petugas')
                    ->relationship('user', 'name')
                    ->searchable()->preload()
                    ->visible(fn (): bool => $user?->hasRole('Admin') ?? false), // <-- Tambahkan visible(),
                // Filter Petugas PJ (hanya Admin)
                SelectFilter::make('petugas_pj')
                    ->label('Filter Petugas PJ')
                    ->query(fn (Builder $query, array $data): Builder => // Gunakan query builder
                        $query->when($data['value'], fn ($q, $v) =>
                        $q->whereHas('distribusiBantuan.kelompok', fn($kq) => $kq->where('user_id', $v))
                        )
                    )
                    ->relationship('distribusiBantuan.kelompok.petugas', 'name', fn (Builder $query) => $query->role('Petugas Lapangan'))
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => $user?->hasRole('Admin') ?? false),
            ])
            ->actions([
                 ActionGroup::make([
                    ViewAction::make(), // View akan menampilkan Infolist
                    EditAction::make(),
                    DeleteAction::make()
                        ->visible(fn () => auth()->user()->can('delete_monitoring::pemanfaatan')),
                ])->tooltip("Opsi Lain"),
            ])
            ->bulkActions([
                 Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('delete_monitoring::pemanfaatan')),
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
            ->defaultSort('tanggal_monitoring', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        /** @var MonitoringPemanfaatan $record */
        $record = $infolist->getRecord(); // Dapatkan record monitoring saat ini

        // Ambil data terkait untuk efisiensi dan digunakan di beberapa tempat
        $distribusi = $record?->distribusiBantuan;
        $bantuan = $distribusi?->bantuan;
        $jenisBantuan = $bantuan?->jenis_bantuan;
        $namaBantuanLower = strtolower($bantuan->nama_bantuan ?? '');
        $satuanLower = strtolower($bantuan->satuan ?? '');

        // Siapkan string info bantuan awal
        $jumlahBantuanAwal = $distribusi?->jumlah;
        $satuanBantuanAwal = $distribusi?->satuan;
        $infoBantuanAwal = ($jumlahBantuanAwal !== null && $satuanBantuanAwal)
                           ? "{$jumlahBantuanAwal} {$satuanBantuanAwal}"
                           : null;
        $infoBantuanAwalFormatted = $infoBantuanAwal ? "(Awal: {$infoBantuanAwal})" : null;

        return $infolist
            ->schema([
                // Gunakan Komponen Tabs untuk mengorganisir konten
                Tabs::make('Monitoring Tabs')
                    ->tabs([

                        // ========================================
                        // Tab 1: Detail Monitoring Saat Ini
                        // ========================================
                        Tab::make('Detail Monitoring Ini')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                // Section Informasi Umum Monitoring Ini
                                InfolistSection::make('Informasi Umum')
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('distribusiBantuan.kelompok.nama_kelompok')->label('Kelompok'),
                                        TextEntry::make('distribusiBantuan.bantuan.nama_bantuan')
                                            ->label('Jenis Bantuan Diterima')
                                            ->suffix($infoBantuanAwal ? " {$infoBantuanAwalFormatted}" : ''), // Tampilkan jumlah awal
                                        TextEntry::make('distribusiBantuan.tanggal_distribusi')->label('Tgl Distribusi')->date('d F Y'),
                                        TextEntry::make('tanggal_monitoring')->label('Tgl Monitoring Ini')->date('d F Y'),
                                        TextEntry::make('persentase_perkembangan')->label('Perkembangan Umum')->suffix('%'),
                                        TextEntry::make('user.name')->label('Dicatat Oleh'),
                                        TextEntry::make('deskripsi_pemanfaatan')->label('Deskripsi Umum')->columnSpanFull()->placeholder('N/A')
                                            ->extraAttributes(['class' => 'whitespace-normal break-words']), // Wrap text
                                        TextEntry::make('catatan_monitoring')->label('Catatan Umum Petugas')->columnSpanFull()->placeholder('N/A')
                                            ->extraAttributes(['class' => 'whitespace-normal break-words']), // Wrap text
                                    ]),

                                // Section Detail Spesifik Monitoring Ini (Dinamis)
                                InfolistSection::make('Detail Spesifik Monitoring Ini')
                                    ->columns(2)
                                    ->visible($jenisBantuan !== null) // Tampil jika jenis bantuan diketahui
                                    ->schema(function () use ($jenisBantuan, $namaBantuanLower, $satuanLower, $infoBantuanAwalFormatted): array {
                                        $entries = [];

                                        // --- Peternakan ---
                                        if ($jenisBantuan === 'Peternakan') {
                                            $entries = [
                                                TextEntry::make('jumlah_ternak_saat_ini')->numeric()->label('Jumlah Ternak Saat Ini')->suffix($infoBantuanAwalFormatted ?? ''),
                                                TextEntry::make('jumlah_kelahiran')->numeric()->label('Jumlah Kelahiran'),
                                                TextEntry::make('jumlah_kematian')->numeric()->label('Jumlah Kematian'),
                                                TextEntry::make('kondisi_kesehatan_ternak')->badge()->label('Kondisi Kesehatan')
                                                    ->color(fn ($state): string => match ($state) { 'Sakit Ringan' => 'warning', 'Sakit Berat' => 'danger', 'Sehat' => 'success', default => 'gray', }),
                                                TextEntry::make('kondisi_kandang')->badge()->label('Kondisi Kandang')
                                                    ->color(fn ($state): string => match ($state) { 'Kurang' => 'danger', 'Cukup' => 'warning', 'Baik' => 'success', default => 'gray', }),
                                                TextEntry::make('pemanfaatan_pakan_ternak')->columnSpanFull()->label('Pemanfaatan Pakan')
                                                    ->extraAttributes(['class' => 'whitespace-normal break-words']),
                                            ];
                                        }

                                        // --- Pertanian ---
                                        elseif ($jenisBantuan === 'Pertanian') {
                                             $isBibit = Str::contains($namaBantuanLower, ['bibit', 'benih']);
                                             $isPupuk = Str::contains($namaBantuanLower, ['pupuk', 'urea', 'npk', 'organik']);
                                             $isAlat = in_array($satuanLower, ['unit', 'paket', 'buah', 'roll']);

                                             if ($isBibit) {
                                                $entries = array_merge($entries, [
                                                    TextEntry::make('luas_tanam_aktual')->suffix(' Ha')->label('Luas Tanam Aktual')->suffix($infoBantuanAwalFormatted ?? ''),
                                                    TextEntry::make('kondisi_tanaman')->badge()->label('Kondisi Tanaman')
                                                        ->color(fn ($state): string => match ($state) { 'Kurang Subur' => 'warning', 'Terserang Hama/Penyakit' => 'danger', 'Subur','Cukup Subur' => 'success', default => 'gray', }),
                                                    TextEntry::make('fase_pertumbuhan')->label('Fase Pertumbuhan'),
                                                    TextEntry::make('estimasi_hasil_panen')->label('Estimasi Hasil Panen'),
                                                    TextEntry::make('satuan_hasil_panen')->label('Satuan Hasil Panen'),
                                                    TextEntry::make('jumlah_stok_bibit_disimpan')->numeric(decimalPlaces:2)->label('Stok Bibit Disimpan'), // Tampilkan stok bibit
                                                    TextEntry::make('satuan_stok_bibit')->label('Satuan Stok Bibit'),
                                                ]);
                                             }
                                             if ($isPupuk) {
                                                 $entries = array_merge($entries, [
                                                     TextEntry::make('luas_tanam_aktual')->suffix(' Ha')->label('Luas Lahan Dipupuk')->suffix($infoBantuanAwalFormatted ?? ''),
                                                     TextEntry::make('kondisi_tanaman')->badge()->label('Kondisi Tanaman Pasca Pupuk')
                                                        ->color(fn ($state): string => match ($state) { 'Kurang Subur' => 'warning', 'Terserang Hama/Penyakit' => 'danger', 'Subur','Cukup Subur' => 'success', default => 'gray', }),
                                                     TextEntry::make('pemanfaatan_alat')->label('Cara & Waktu Pemupukan')->columnSpanFull()
                                                        ->extraAttributes(['class' => 'whitespace-normal break-words']),
                                                 ]);
                                             }
                                             if ($isAlat && !$isPupuk && !$isBibit) {
                                                 $entries = array_merge($entries, [
                                                     TextEntry::make('kondisi_alat')->badge()->label('Kondisi Alat')
                                                        ->color(fn ($state): string => match ($state) { 'Rusak Ringan' => 'warning', 'Rusak Berat','Hilang' => 'danger', 'Baik' => 'success', default => 'gray', })->suffix($infoBantuanAwalFormatted ?? ''),
                                                     TextEntry::make('pemanfaatan_alat')->label('Cara Pemanfaatan Alat')->columnSpanFull()
                                                        ->extraAttributes(['class' => 'whitespace-normal break-words']),
                                                     TextEntry::make('frekuensi_penggunaan_alat')->label('Frekuensi Penggunaan'),
                                                     TextEntry::make('satuan_frekuensi_alat')->label('Satuan Frekuensi'),
                                                 ]);
                                             }
                                        }

                                        // --- Perikanan ---
                                        elseif ($jenisBantuan === 'Perikanan') {
                                             $isBibitIkan = Str::contains($namaBantuanLower, ['bibit', 'benih', 'nener']);
                                             $isPakanIkan = Str::contains($namaBantuanLower, 'pakan');
                                             $isAlatIkan = in_array($satuanLower, ['unit', 'paket', 'roll', 'lembar', 'buah']);

                                             if ($isBibitIkan) {
                                                 $entries = array_merge($entries, [
                                                     TextEntry::make('jumlah_ikan_hidup')->numeric()->label('Estimasi Jumlah Ikan Hidup')->suffix($infoBantuanAwalFormatted ?? ''),
                                                     TextEntry::make('ukuran_ikan_rata2')->label('Ukuran Ikan Rata-rata'),
                                                     TextEntry::make('kondisi_air_kolam')->badge()->label('Kondisi Air Kolam')
                                                        ->color(fn ($state): string => match ($state) { 'Keruh','Berbau','Hijau Pekat' => 'warning', 'Baik' => 'success', default => 'gray', }),
                                                 ]);
                                             }
                                             if ($isPakanIkan) {
                                                 $entries = array_merge($entries, [
                                                     TextEntry::make('pemberian_pakan_ikan')->columnSpanFull()->label('Cara & Frekuensi Pakan')->suffix($infoBantuanAwalFormatted ?? '')
                                                        ->extraAttributes(['class' => 'whitespace-normal break-words']),
                                                 ]);
                                             }
                                              if ($isAlatIkan && !$isBibitIkan && !$isPakanIkan) {
                                                 $entries = array_merge($entries, [
                                                     TextEntry::make('kondisi_alat')->badge()->label('Kondisi Alat/Sarana')
                                                        ->color(fn ($state): string => match ($state) { 'Rusak Ringan' => 'warning', 'Rusak Berat','Hilang' => 'danger', 'Baik' => 'success', default => 'gray', })->suffix($infoBantuanAwalFormatted ?? ''),
                                                     TextEntry::make('pemanfaatan_alat')->label('Cara Pemanfaatan Alat/Sarana')->columnSpanFull()
                                                        ->extraAttributes(['class' => 'whitespace-normal break-words']),
                                                     TextEntry::make('frekuensi_penggunaan_alat')->label('Frekuensi Penggunaan'),
                                                     TextEntry::make('satuan_frekuensi_alat')->label('Satuan Frekuensi'),
                                                     TextEntry::make('estimasi_hasil_tangkapan')->label('Estimasi Hasil Tangkapan'),
                                                     TextEntry::make('satuan_hasil_tangkapan')->label('Satuan Hasil Tangkapan'),
                                                 ]);
                                             }
                                         }

                                        // Sembunyikan field yang null dan tambahkan placeholder
                                        return array_map(fn ($entry) => $entry->placeholder('N/A')->hidden(fn ($state) => $state === null), $entries);
                                    }),

                                // Section Dokumentasi Monitoring Ini
                                InfolistSection::make('Dokumentasi Monitoring Ini')
                                    ->schema([
                                        ImageEntry::make('foto_dokumentasi')
                                            ->label(false)
                                            ->disk('public')
                                            ->columnSpanFull()
                                            ->width('100%')
                                            ->height('auto')
                                            ->visible(fn ($state) => $state !== null),
                                        TextEntry::make('no_foto')
                                            ->label(false)
                                            ->hidden(fn ($state, MonitoringPemanfaatan $record) => $record->foto_dokumentasi !== null)
                                            ->default('Tidak ada foto dokumentasi.')
                                            ->columnSpanFull()
                                            ->color('gray'),
                                    ]),
                            ]), // Akhir Schema Tab 1

                        // ========================================
                        // Tab 2: Riwayat Timeline
                        // ========================================
                        Tab::make('Riwayat Monitoring')
                            ->icon('heroicon-o-clock')
                            ->badge(fn (MonitoringPemanfaatan $record): ?int => $record->distribusiBantuan ? $record->distribusiBantuan->monitoringPemanfaatans()->count() : 0)
                            ->schema([
                                // Gunakan ViewEntry untuk merender komponen Livewire Timeline
                                ViewEntry::make('timeline')
                                    ->label(false)
                                    ->view('infolists.entries.livewire-timeline-wrapper') // Panggil view wrapper
                                    // Tidak perlu viewData karena wrapper ambil dari $getRecord()
                                    ->visible(fn (MonitoringPemanfaatan $record) => $record->distribusi_bantuan_id !== null),
                            ]), // Akhir Schema Tab 2

                    ]) // Akhir Tabs
                    ->columnSpanFull(), // Tabs ambil lebar penuh
            ]);
    }

    /**
     * Modifikasi query dasar untuk Resource ini.
     */
    public static function getEloquentQuery(): Builder // <-- Pastikan return type Builder
    {
        $user = Auth::user();
        // Mulai dengan query dasar dan eager load relasi yang dibutuhkan
        // Eager load sampai ke bantuan dan kelompok untuk filtering dan display
        $query = parent::getEloquentQuery()->with([
            'distribusiBantuan.kelompok.petugas', // Eager load petugas PJ kelompok
            'distribusiBantuan.bantuan',          // Eager load detail bantuan
            'user'                                // Eager load user yg input monitoring
        ]);

        // Jika user login dan BUKAN Admin, filter hanya monitoring
        // untuk kelompok yang ditangani user tersebut.
        if ($user && !$user->hasRole('Admin')) {
            // Filter berdasarkan user_id di tabel kelompok melalui relasi distribusiBantuan
            $query->whereHas('distribusiBantuan.kelompok', function (Builder $q) use ($user) { // <-- Gunakan Builder di closure
                $q->where('user_id', $user->id);
            });
        }

        return $query;
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
            'index' => Pages\ListMonitoringPemanfaatans::route('/'),
            'create' => Pages\CreateMonitoringPemanfaatan::route('/create'),
            'view' => Pages\ViewMonitoringPemanfaatan::route('/{record}'),
            'edit' => Pages\EditMonitoringPemanfaatan::route('/{record}/edit'),
        ];
    }
}
