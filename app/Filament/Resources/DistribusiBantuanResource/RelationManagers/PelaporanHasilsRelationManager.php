<?php

namespace App\Filament\Resources\DistribusiBantuanResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str; // Import Str
use Filament\Forms\Components\Placeholder;
use App\Models\PelaporanHasil; // Import model
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;
use App\Models\Bantuan; // Import untuk akses detail bantuan
use App\Models\DistribusiBantuan; // Import untuk akses owner record

class PelaporanHasilsRelationManager extends RelationManager
{
    protected static string $relationship = 'pelaporanHasils';
    protected static ?string $recordTitleAttribute = 'tanggal_pelaporan';
    protected static ?string $modelLabel = 'Pelaporan Hasil';
    protected static ?string $pluralModelLabel = 'Riwayat Pelaporan Hasil';

    // Opsi Enum
    protected static array $kualitasHasilPanenOptions = ['Premium' => 'Premium', 'Baik' => 'Baik', 'Sedang' => 'Sedang', 'Kurang' => 'Kurang'];

    public function form(Form $form): Form
    {
        /** @var DistribusiBantuan $ownerRecord */
        $ownerRecord = $this->getOwnerRecord();
        $bantuan = $ownerRecord?->bantuan;
        $jenisBantuan = $bantuan?->jenis_bantuan;
        $namaBantuan = $bantuan?->nama_bantuan ?? 'Bantuan Tidak Diketahui';
        $namaBantuanLower = strtolower($namaBantuan);
        $satuanLower = strtolower($bantuan->satuan ?? '');

        return $form
            ->schema([
                // Section Tanggal di atas, 1 kolom
                Section::make('Informasi Dasar Pelaporan')
                    ->schema([
                        DatePicker::make('tanggal_pelaporan')
                            ->label('Tanggal Pelaporan')
                            ->required()
                            ->native(false)
                            ->default(now()),
                    ]),

                // Grid Utama untuk Form (2 kolom) & Contoh (1 kolom)
                Grid::make(3)
                    ->schema([
                        // --- Kolom Form (Kolom 1 & 2) ---
                        Group::make()
                            ->schema([
                                // Fieldset Dinamis Detail Hasil
                                Fieldset::make('Detail Hasil Spesifik')
                                    ->schema(function () use ($jenisBantuan, $namaBantuanLower, $satuanLower): array {
                                        $fields = [];
                                        // Pertanian
                                        if ($jenisBantuan === 'Pertanian') {
                                            $isTanaman = Str::contains($namaBantuanLower, ['bibit', 'benih', 'padi', 'jagung']);
                                            if ($isTanaman) {
                                                $fields = [
                                                    TextInput::make('hasil_panen_kg')->numeric()->nullable()->minValue(0)->label('Hasil Panen (Kg)'),
                                                    TextInput::make('hasil_panen_ton')->numeric()->nullable()->minValue(0)->label('Hasil Panen (Ton)'),
                                                    Select::make('kualitas_hasil_panen')->options(self::$kualitasHasilPanenOptions)->nullable()->label('Kualitas Hasil'),
                                                    TextInput::make('jumlah_dikonsumsi')->numeric()->nullable()->minValue(0)->label('Jumlah Dikonsumsi'),
                                                    TextInput::make('satuan_konsumsi')->nullable()->placeholder('Kg, Liter, dll')->label('Satuan Konsumsi'),
                                                ];
                                            } else {
                                                 $fields = [ Textarea::make('catatan_hasil_spesifik_pertanian')->label('Catatan Hasil/Dampak Bantuan Pertanian')->rows(3)->columnSpanFull(), ];
                                            }
                                        }
                                        // Peternakan
                                        elseif ($jenisBantuan === 'Peternakan') {
                                            $fields = [
                                                TextInput::make('jumlah_ternak_terjual')->numeric()->nullable()->minValue(0)->label('Jumlah Ternak Terjual (Ekor)'),
                                                TextInput::make('jenis_produk_ternak_terjual')->nullable()->label('Jenis Produk Terjual')->placeholder('Ternak Hidup, Daging, Susu, Telur, dll.'),
                                                TextInput::make('berat_total_terjual_kg')->numeric()->nullable()->minValue(0)->label('Berat Total Terjual (Kg)'),
                                                TextInput::make('volume_produk_liter')->numeric()->nullable()->minValue(0)->label('Volume Produk (Liter)'),
                                                TextInput::make('jumlah_dikonsumsi')->numeric()->nullable()->minValue(0)->label('Jumlah Dikonsumsi (Ekor/Unit)'),
                                            ];
                                        }
                                        // Perikanan
                                        elseif ($jenisBantuan === 'Perikanan') {
                                             $isBudidaya = Str::contains($namaBantuanLower, ['bibit', 'benih', 'nener', 'pakan', 'kolam']);
                                             $isTangkap = Str::contains($namaBantuanLower, ['jaring', 'perahu', 'alat tangkap']);
                                             if ($isBudidaya) {
                                                 $fields = [
                                                     TextInput::make('jumlah_tangkapan_kg')->label('Jumlah Hasil Panen (Kg)')->numeric()->nullable()->minValue(0),
                                                     TextInput::make('jenis_ikan_dominan')->label('Jenis Ikan Dominan')->nullable(),
                                                     TextInput::make('jumlah_diolah_kg')->label('Jumlah Diolah (Kg)')->numeric()->nullable()->minValue(0),
                                                     TextInput::make('jumlah_dikonsumsi')->label('Jumlah Dikonsumsi (Kg)')->numeric()->nullable()->minValue(0),
                                                 ];
                                             } elseif ($isTangkap) {
                                                 $fields = [
                                                     TextInput::make('jumlah_tangkapan_kg')->label('Jumlah Hasil Tangkapan (Kg)')->numeric()->nullable()->minValue(0),
                                                     TextInput::make('jenis_ikan_dominan')->label('Jenis Ikan Dominan')->nullable(),
                                                     TextInput::make('jumlah_dijual_kg')->label('Jumlah Dijual (Kg)')->numeric()->nullable()->minValue(0),
                                                 ];
                                             } else {
                                                  $fields = [ Textarea::make('catatan_hasil_spesifik_perikanan')->label('Catatan Hasil/Dampak Bantuan Perikanan')->rows(3)->columnSpanFull(), ];
                                             }
                                        }
                                        return $fields;
                                    })
                                    ->columns(2)
                                    ->visible($jenisBantuan !== null),

                                // Section Finansial
                                Section::make('Informasi Penjualan')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('jumlah_dijual')->label('Jumlah Dijual (Unit Utama)')->numeric()->nullable()->minValue(0)->helperText('Jumlah unit produk utama.'),
                                        TextInput::make('satuan_dijual')->label('Satuan Unit Dijual')->nullable()->placeholder('Kg, Ekor, Liter, Unit, dll.'),
                                        TextInput::make('harga_jual_per_satuan_dijual')->label('Harga Jual per Satuan')->prefix('Rp')->numeric()->nullable()->minValue(0)->formatStateUsing(fn ($state): ?string => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : null)->dehydrateStateUsing(fn ($state): ?string => is_numeric(preg_replace('/[^\d]/', '', $state ?? '')) ? preg_replace('/[^\d]/', '', $state ?? '') : null),
                                        TextInput::make('pendapatan_lain')->label('Pendapatan Lain-lain')->prefix('Rp')->numeric()->nullable()->minValue(0)->formatStateUsing(fn ($state): ?string => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : null)->dehydrateStateUsing(fn ($state): ?string => is_numeric(preg_replace('/[^\d]/', '', $state ?? '')) ? preg_replace('/[^\d]/', '', $state ?? '') : null),
                                        TextInput::make('total_penjualan')->label('Total Penjualan (Rp)')->prefix('Rp')->numeric()->nullable()->minValue(0)->formatStateUsing(fn ($state): string => is_numeric($state) ? number_format((float)$state, 0, ',', '.') : '0')->dehydrateStateUsing(fn ($state): ?string => is_numeric(preg_replace('/[^\d]/', '', $state ?? '')) ? preg_replace('/[^\d]/', '', $state ?? '') : null)->helperText('Isi manual atau biarkan kosong (akan dihitung otomatis jika memungkinkan).')->columnSpanFull(),
                                    ]),

                                // Section Dokumentasi & Catatan Akhir
                                Section::make('Dokumentasi & Catatan Akhir')
                                    ->schema([
                                        FileUpload::make('foto_hasil')->label('Foto Hasil')->disk('public')->directory('pelaporan-hasil-docs')->image()->maxSize(2048)->imageEditor()->columnSpanFull(),
                                        Textarea::make('catatan_hasil')->label('Catatan Keseluruhan Hasil')->rows(3)->columnSpanFull(),
                                        Hidden::make('user_id')->default(auth()->id()),
                                    ])
                            ])->columnSpan(2), // Akhir Grup Form (Kolom 1 & 2)

                        // --- Kolom Contoh/Analogi (Kolom 3) ---
                        Group::make()
                            ->schema([
                                Placeholder::make('contoh_pengisian_label')
                                    ->label(false)
                                    ->content('Contoh Pengisian untuk:')
                                    ->extraAttributes(['class' => 'text-sm font-medium text-gray-700 dark:text-gray-300 mb-1']),
                                Placeholder::make('contoh_pengisian_bantuan')
                                    ->label(false)
                                    ->content($namaBantuan) // Tampilkan nama bantuan
                                    ->extraAttributes(['class' => 'text-lg font-semibold text-primary-600 dark:text-primary-400 mb-3']),

                                Placeholder::make('contoh_pengisian_dinamis')
                                    ->label(false)
                                    ->content(function() use ($jenisBantuan, $namaBantuanLower, $satuanLower): HtmlString {
                                        $html = '<div class="space-y-3">'; // Jarak antar elemen utama
                                        $html .= '<h5 class="text-sm font-medium text-gray-900 dark:text-white">Contoh Pengisian:</h5>'; // Judul

                                        // --- Pertanian ---
                                        if ($jenisBantuan === 'Pertanian') {
                                            $isBibit = Str::contains($namaBantuanLower, ['bibit', 'benih', 'padi', 'jagung']);
                                            if ($isBibit) {
                                                $html .= '<div>';
                                                $html .= '<p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Detail Hasil Spesifik:</p>';
                                                $html .= '<ul class="list-disc list-inside text-xs space-y-1 text-gray-500 dark:text-gray-400">';
                                                $html .= '<li>Hasil Panen (Kg): <code class="text-primary-600 dark:text-primary-400">5000</code></li>';
                                                $html .= '<li>Kualitas Hasil Panen: <code class="text-primary-600 dark:text-primary-400">Baik</code></li>';
                                                $html .= '<li>Jumlah Dikonsumsi (Kg): <code class="text-primary-600 dark:text-primary-400">200</code></li>';
                                                $html .= '</ul>';
                                                $html .= '</div>';

                                                $html .= '<div>';
                                                $html .= '<p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1 mt-2">Informasi Penjualan:</p>';
                                                $html .= '<ul class="list-disc list-inside text-xs space-y-1 text-gray-500 dark:text-gray-400">';
                                                $html .= '<li>Jumlah Dijual (Unit Utama): <code class="text-primary-600 dark:text-primary-400">4800</code></li>';
                                                $html .= '<li>Satuan Unit Dijual: <code class="text-primary-600 dark:text-primary-400">Kg</code></li>';
                                                $html .= '<li>Harga Jual per Satuan: <code class="text-primary-600 dark:text-primary-400">Rp 5.500</code></li>';
                                                $html .= '<li>Pendapatan Lain: <code class="text-primary-600 dark:text-primary-400">Rp 150.000</code></li>';
                                                $html .= '<li>Total Penjualan: <code class="text-primary-600 dark:text-primary-400">Rp 26.550.000</code></li>';
                                                $html .= '</ul>';
                                                $html .= '</div>';
                                            } else { // Pupuk/Alat
                                                 $html .= '<div>';
                                                 $html .= '<p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Detail Hasil Spesifik:</p>';
                                                 $html .= '<ul class="list-disc list-inside text-xs space-y-1 text-gray-500 dark:text-gray-400">';
                                                 $html .= '<li>Catatan Hasil/Dampak: <code class="text-primary-600 dark:text-primary-400">Pupuk Urea membuat tanaman jagung lebih hijau dan tinggi di lahan 1 Ha.</code></li>';
                                                 $html .= '</ul>';
                                                 $html .= '</div>';
                                                 $html .= '<div>';
                                                 $html .= '<p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1 mt-2">Informasi Penjualan:</p>';
                                                 $html .= '<p class="text-xs text-gray-500 dark:text-gray-400 italic">(Isi jika ada hasil yang bisa dijual terkait penggunaan alat/pupuk, misal sewa alat)</p>';
                                                 $html .= '</div>';
                                            }
                                        }
                                        // --- Peternakan ---
                                        elseif ($jenisBantuan === 'Peternakan') {
                                             $html .= '<div>';
                                             $html .= '<p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Detail Hasil Spesifik:</p>';
                                             $html .= '<ul class="list-disc list-inside text-xs space-y-1 text-gray-500 dark:text-gray-400">';
                                             $html .= '<li>Jumlah Ternak Terjual (Ekor): <code class="text-primary-600 dark:text-primary-400">2</code></li>';
                                             $html .= '<li>Jenis Produk Terjual: <code class="text-primary-600 dark:text-primary-400">Sapi Hidup</code></li>';
                                             $html .= '<li>Berat Total Terjual (Kg): <code class="text-primary-600 dark:text-primary-400">850</code></li>';
                                             $html .= '<li>Volume Produk (Liter): <code class="text-primary-600 dark:text-primary-400">N/A</code></li>'; // Contoh jika tidak relevan
                                             $html .= '<li>Jumlah Dikonsumsi (Ekor/Unit): <code class="text-primary-600 dark:text-primary-400">0</code></li>';
                                             $html .= '</ul>';
                                             $html .= '</div>';

                                             $html .= '<div>';
                                             $html .= '<p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1 mt-2">Informasi Penjualan:</p>';
                                             $html .= '<ul class="list-disc list-inside text-xs space-y-1 text-gray-500 dark:text-gray-400">';
                                             $html .= '<li>Jumlah Dijual (Unit Utama): <code class="text-primary-600 dark:text-primary-400">2</code></li>';
                                             $html .= '<li>Satuan Unit Dijual: <code class="text-primary-600 dark:text-primary-400">Ekor</code></li>';
                                             $html .= '<li>Harga Jual per Satuan: <code class="text-primary-600 dark:text-primary-400">Rp 12.000.000</code></li>';
                                             $html .= '<li>Pendapatan Lain: <code class="text-primary-600 dark:text-primary-400">Rp 300.000</code> (Pupuk Kandang)</li>';
                                             $html .= '<li>Total Penjualan: <code class="text-primary-600 dark:text-primary-400">Rp 24.300.000</code></li>';
                                             $html .= '</ul>';
                                             $html .= '</div>';
                                        }
                                        // --- Perikanan ---
                                        elseif ($jenisBantuan === 'Perikanan') {
                                             $isBudidaya = Str::contains($namaBantuanLower, ['bibit', 'benih', 'nener', 'pakan', 'kolam']);
                                             $isTangkap = Str::contains($namaBantuanLower, ['jaring', 'perahu', 'alat tangkap']);

                                             if ($isBudidaya) {
                                                $html .= '<div>';
                                                $html .= '<p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Detail Hasil Spesifik (Budidaya):</p>';
                                                $html .= '<ul class="list-disc list-inside text-xs space-y-1 text-gray-500 dark:text-gray-400">';
                                                $html .= '<li>Jumlah Hasil Panen (Kg): <code class="text-primary-600 dark:text-primary-400">800</code></li>';
                                                $html .= '<li>Jenis Ikan Dominan: <code class="text-primary-600 dark:text-primary-400">Lele Sangkuriang</code></li>';
                                                $html .= '<li>Jumlah Diolah (Kg): <code class="text-primary-600 dark:text-primary-400">50</code> (Lele Asap)</li>';
                                                $html .= '<li>Jumlah Dikonsumsi (Kg): <code class="text-primary-600 dark:text-primary-400">25</code></li>';
                                                $html .= '</ul>';
                                                $html .= '</div>';

                                                $html .= '<div>';
                                                $html .= '<p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1 mt-2">Informasi Penjualan:</p>';
                                                $html .= '<ul class="list-disc list-inside text-xs space-y-1 text-gray-500 dark:text-gray-400">';
                                                $html .= '<li>Jumlah Dijual (Unit Utama): <code class="text-primary-600 dark:text-primary-400">725</code> (800 - 50 - 25)</li>';
                                                $html .= '<li>Satuan Unit Dijual: <code class="text-primary-600 dark:text-primary-400">Kg</code></li>';
                                                $html .= '<li>Harga Jual per Satuan: <code class="text-primary-600 dark:text-primary-400">Rp 18.000</code></li>';
                                                $html .= '<li>Pendapatan Lain: <code class="text-primary-600 dark:text-primary-400">Rp 1.000.000</code> (Lele Asap)</li>';
                                                $html .= '<li>Total Penjualan: <code class="text-primary-600 dark:text-primary-400">Rp 14.050.000</code></li>'; // 725*18000 + 1M
                                                $html .= '</ul>';
                                                $html .= '</div>';
                                             } elseif ($isTangkap) {
                                                $html .= '<div>';
                                                $html .= '<p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Detail Hasil Spesifik (Tangkap):</p>';
                                                $html .= '<ul class="list-disc list-inside text-xs space-y-1 text-gray-500 dark:text-gray-400">';
                                                $html .= '<li>Jumlah Hasil Tangkapan (Kg): <code class="text-primary-600 dark:text-primary-400">450</code></li>';
                                                $html .= '<li>Jenis Ikan Dominan: <code class="text-primary-600 dark:text-primary-400">Tongkol, Layang</code></li>';
                                                $html .= '<li>Jumlah Dijual (Kg): <code class="text-primary-600 dark:text-primary-400">430</code></li>';
                                                $html .= '</ul>';
                                                $html .= '</div>';

                                                $html .= '<div>';
                                                $html .= '<p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1 mt-2">Informasi Penjualan:</p>';
                                                $html .= '<ul class="list-disc list-inside text-xs space-y-1 text-gray-500 dark:text-gray-400">';
                                                $html .= '<li>Jumlah Dijual (Unit Utama): <code class="text-primary-600 dark:text-primary-400">430</code></li>';
                                                $html .= '<li>Satuan Unit Dijual: <code class="text-primary-600 dark:text-primary-400">Kg</code></li>';
                                                $html .= '<li>Harga Jual per Satuan: <code class="text-primary-600 dark:text-primary-400">Rp 25.000</code></li>';
                                                $html .= '<li>Pendapatan Lain: <code class="text-primary-600 dark:text-primary-400">Rp 0</code></li>';
                                                $html .= '<li>Total Penjualan: <code class="text-primary-600 dark:text-primary-400">Rp 10.750.000</code></li>';
                                                $html .= '</ul>';
                                                $html .= '</div>';
                                             } else { // Alat/sarana lain
                                                  $html .= '<div>';
                                                  $html .= '<p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Detail Hasil Spesifik:</p>';
                                                  $html .= '<ul class="list-disc list-inside text-xs space-y-1 text-gray-500 dark:text-gray-400">';
                                                  $html .= '<li>Catatan Hasil/Dampak: <code class="text-primary-600 dark:text-primary-400">Kolam terpal baru sudah terisi air dan siap tebar benih.</code></li>';
                                                  $html .= '</ul>';
                                                  $html .= '</div>';
                                                  $html .= '<div>';
                                                  $html .= '<p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1 mt-2">Informasi Penjualan:</p>';
                                                  $html .= '<p class="text-xs text-gray-500 dark:text-gray-400 italic">(Belum ada hasil penjualan)</p>';
                                                  $html .= '</div>';
                                             }
                                        }
                                        // --- Jika Jenis Bantuan Tidak Dikenali ---
                                        else {
                                            $html .= '<p class="text-sm text-gray-500 dark:text-gray-400">Belum ada contoh spesifik untuk jenis bantuan ini.</p>';
                                        }

                                        $html .= '</div>'; // Penutup div space-y-3

                                        // Bungkus dengan HtmlString
                                        return new HtmlString($html);
                                    })
                                    ->extraAttributes(['class' => 'bg-gray-50 dark:bg-gray-800/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700 h-full']), // Hapus prose

                            ])->columnSpan(1), // Akhir Grup Contoh (Kolom 3)
                    ]), // Akhir Grid Utama
            ]); // Akhir Schema Form
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal_pelaporan')->date('d M Y')->sortable(),
                // Tampilkan beberapa data kunci hasil spesifik (toggleable)
                TextColumn::make('hasil_panen_kg')->label('Panen (Kg)')->numeric()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('jumlah_ternak_terjual')->label('Ternak Jual (Ekor)')->numeric()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('jumlah_tangkapan_kg')->label('Tangkapan (Kg)')->numeric()->toggleable(isToggledHiddenByDefault: true),
                // Kolom Finansial Utama
                TextColumn::make('jumlah_dijual')->label('Jml Dijual')->numeric(decimalPlaces: 2)->alignRight()->toggleable(),
                TextColumn::make('satuan_dijual')->label('Satuan')->toggleable(),
                TextColumn::make('harga_jual_per_satuan_dijual')->label('Harga/Satuan')->money('IDR',0)->sortable()->toggleable(),
                TextColumn::make('pendapatan_lain')->label('Pendpt. Lain')->money('IDR',0)->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_penjualan')->label('Total Penjualan')->money('IDR',0)->sortable()->weight('medium'), // Buat lebih menonjol
                // Kolom Lain
                ImageColumn::make('foto_hasil')->label('Foto')->disk('public')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.name')->label('Petugas')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([ /* ... filters ... */ ])
            ->headerActions([ Tables\Actions\CreateAction::make(), ])
            ->actions([ Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()->visible(fn () => auth()->user()->can('delete_pelaporan::hasil')), ])
            ->bulkActions([ Tables\Actions\BulkActionGroup::make([ Tables\Actions\DeleteBulkAction::make()->visible(fn () => auth()->user()->can('delete_pelaporan::hasil')), ]), ])
            ->striped()
            ->defaultSort('tanggal_pelaporan', 'desc');
    }
}
