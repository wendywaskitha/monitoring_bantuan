<?php

namespace App\Filament\Resources\KelompokResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Models\DokumenKelompok; // Import model
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth; // Import Auth
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\BadgeColumn; // Untuk kategori
use Filament\Notifications\Notification; // Import Notifikasi
use Illuminate\Support\HtmlString; // Untuk format ukuran file
use Filament\Tables\Columns\IconColumn; // Untuk ikon tipe file
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class DokumenKelompoksRelationManager extends RelationManager
{
    protected static string $relationship = 'dokumenKelompoks';

    protected static ?string $recordTitleAttribute = 'nama_dokumen';
    protected static ?string $modelLabel = 'Dokumen'; // Lebih singkat
    protected static ?string $pluralModelLabel = 'Dokumen Kelompok';

    // Opsi Kategori Dokumen
    protected static array $kategoriOptions = [
        'Proposal' => 'Proposal Bantuan',
        'SK' => 'SK Pembentukan/Pengukuhan',
        'Foto Kegiatan' => 'Foto Kegiatan (Umum)',
        'LPJ' => 'Laporan Pertanggungjawaban',
        'Surat Menyurat' => 'Surat Menyurat',
        'Dokumen Pendukung' => 'Dokumen Pendukung Lain',
        'Lainnya' => 'Lainnya',
    ];

    // --- FORM UNTUK CREATE/EDIT DOKUMEN ---
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('path_file')
                    ->label('Upload File')
                    ->required(fn (string $operation): bool => $operation === 'create') // Wajib saat create
                    ->disk('public')
                    ->directory('dokumen-kelompok') // Folder di storage/app/public/
                    // ->preserveFilenames() // Opsional: pertahankan nama asli
                    ->maxSize(5120) // Max 5MB
                    ->acceptedFileTypes([ // Jenis file umum
                        'application/pdf', // PDF
                        'image/jpeg', 'image/png', 'image/gif', 'image/webp', // Gambar
                        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // Word
                        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // Excel
                        'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', // Powerpoint
                        'text/plain', // Teks biasa
                        'application/zip', 'application/x-rar-compressed', // Arsip
                    ])
                    ->validationMessages([ // Pesan validasi kustom
                        'maxSize' => 'Ukuran file maksimal :maxSize KB.',
                        'acceptedFileTypes' => 'Jenis file tidak didukung.',
                    ])
                    ->columnSpanFull()
                    ->imagePreviewHeight('100') // Tinggi preview gambar
                    ->loadingIndicatorPosition('left')
                    ->panelLayout('integrated')
                    ->removeUploadedFileButtonPosition('right')
                    ->uploadButtonPosition('left')
                    ->uploadProgressIndicatorPosition('left')
                    ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, callable $set, callable $get) {
                        // Simpan file dengan nama unik atau nama asli
                        // $filename = $file->getClientOriginalName(); // Nama asli
                        $filename = uniqid() . '.' . $file->getClientOriginalExtension(); // Nama unik
                        $path = $file->storeAs('dokumen-kelompok', $filename, 'public'); // Simpan dengan nama spesifik

                        // Set state untuk kolom lain
                        $set('tipe_file', $file->getMimeType());
                        $set('ukuran_file', $file->getSize());
                        // Isi nama dokumen otomatis jika kosong
                        if (empty($get('nama_dokumen'))) {
                            $set('nama_dokumen', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                        }
                        return $path; // Kembalikan path relatif dari disk public
                    })
                    ->helperText('Upload file baru untuk mengganti yang lama (jika mode edit). Maks 5MB.'),

                TextInput::make('nama_dokumen')
                    ->label('Nama/Judul Dokumen')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Nama deskriptif untuk dokumen ini.'),

                Select::make('kategori')
                    ->options(self::$kategoriOptions)
                    ->searchable()
                    ->required() // Kategori wajib?
                    ->placeholder('Pilih kategori dokumen'),

                Textarea::make('deskripsi')
                    ->label('Deskripsi Tambahan')
                    ->rows(3)
                    ->nullable(),

                Hidden::make('user_id')->default(fn() => Auth::id()), // User yang upload
            ])->columns(1);
    }

    // --- TABEL DAFTAR DOKUMEN ---
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nama_dokumen') // Gunakan nama dokumen sebagai judul record
            ->columns([
                // Kolom Ikon Tipe File
                IconColumn::make('tipe_file')
                    ->label('')
                    ->icon(fn (?string $state): string => match (true) {
                        !$state => 'heroicon-o-question-mark-circle', // Jika tipe null
                        Str::contains($state, 'pdf') => 'heroicon-s-document-text', // Solid icon
                        Str::contains($state, 'image') => 'heroicon-s-photo',
                        Str::contains($state, ['word', 'document']) => 'heroicon-s-document-text', // Bisa bedakan ikon jika punya
                        Str::contains($state, ['excel', 'spreadsheet']) => 'heroicon-s-table-cells',
                        Str::contains($state, ['powerpoint', 'presentation']) => 'heroicon-s-presentation-chart-line',
                        Str::contains($state, ['zip', 'rar', 'archive']) => 'heroicon-s-archive-box',
                        default => 'heroicon-s-document',
                    })
                    ->color(fn (?string $state): string => match (true) {
                        !$state => 'gray',
                        Str::contains($state, 'pdf') => 'danger',
                        Str::contains($state, 'image') => 'warning',
                        Str::contains($state, ['word', 'document']) => 'info',
                        Str::contains($state, ['excel', 'spreadsheet']) => 'success',
                        Str::contains($state, ['powerpoint', 'presentation']) => 'warning',
                        Str::contains($state, ['zip', 'rar', 'archive']) => 'gray',
                        default => 'gray',
                    })
                    ->tooltip(fn(?string $state) => $state ?? 'Tipe file tidak diketahui'), // Tooltip MIME type

                // Kolom Nama & Deskripsi
                TextColumn::make('nama_dokumen')
                    ->label('Nama Dokumen')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn (DokumenKelompok $record): ?string => Str::limit($record->deskripsi, 70))
                    ->tooltip(fn (DokumenKelompok $record): ?string => $record->deskripsi)
                    ->wrap(),

                // Kolom Kategori
                BadgeColumn::make('kategori')
                    ->colors([
                        'primary' => 'Proposal',
                        'success' => 'SK',
                        'warning' => 'Foto Kegiatan',
                        'info' => 'Laporan Lain',
                        'danger' => 'LPJ', // Contoh warna lain
                        'gray' => fn ($state) => in_array($state, ['Dokumen Pendukung', 'Lainnya']) || $state === null,
                    ])
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                // Kolom Ukuran File
                TextColumn::make('ukuran_file')
                    ->label('Ukuran')
                    ->formatStateUsing(fn (?int $state): string => $state ? \Illuminate\Support\Number::fileSize($state, precision: 1) : '-')
                    ->alignRight()
                    ->sortable(),

                // Kolom Tanggal Upload
                TextColumn::make('created_at')
                    ->label('Tgl Upload')
                    ->dateTime('d M Y, H:i') // Format lebih detail
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false), // Tampilkan default

                // Kolom Pengupload
                TextColumn::make('user.name')
                    ->label('Diupload Oleh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Sembunyikan default
            ])
            ->filters([
                SelectFilter::make('kategori')
                    ->options(self::$kategoriOptions)
                    ->multiple() // Izinkan filter multi kategori
                    ->label('Filter Kategori'),
                // Filter berdasarkan pengupload (jika perlu)
                // SelectFilter::make('user_id')->relationship('user', 'name')->label('Filter Pengupload'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Pastikan user_id terisi saat create (meskipun ada default di hidden)
                        $data['user_id'] = Auth::id();
                        return $data;
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    // Aksi Lihat/Unduh
                    Action::make('lihatDownload')
                        ->label('Lihat/Unduh')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('secondary') // Warna sekunder
                        ->url(fn (DokumenKelompok $record): ?string => $record->url, shouldOpenInNewTab: true)
                        ->visible(fn (DokumenKelompok $record): bool => $record->url !== null),

                    // Aksi Edit Info
                    EditAction::make()
                        ->label('Edit Info')
                        ->icon('heroicon-o-pencil-square')
                        // Sembunyikan field upload saat edit? Atau biarkan bisa diganti?
                        // ->form(fn(Form $form) => $this->getEditFormSchema($form)) // Jika form edit beda
                        ,

                    // Aksi Hapus
                    DeleteAction::make()
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        // Model event sudah handle hapus file
                ])->tooltip('Opsi Dokumen'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    // Tambahkan bulk action lain jika perlu
                ]),
            ])
            ->striped()
            ->defaultSort('created_at', 'desc'); // Urutkan berdasarkan upload terbaru
    }

    // Opsional: Definisikan form terpisah untuk edit jika berbeda
    // protected function getEditFormSchema(Form $form): Form
    // {
    //     return $form->schema([
    //         // Field nama, kategori, deskripsi (tanpa FileUpload required)
    //     ]);
    // }
}
