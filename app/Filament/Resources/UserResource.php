<?php

namespace App\Filament\Resources;

use Filament\Forms;
// use App\Filament\Resources\UserResource\RelationManagers; // Kita belum buat relation manager
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\HtmlColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash; // Import Hash
// use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use Filament\Forms\Components\Section; // Import Section
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Filament\Tables\Columns\BadgeColumn; // Import BadgeColumn
use Filament\Tables\Filters\SelectFilter; // Import SelectFilter
use App\Filament\Resources\UserResource\RelationManagers\KelompokYangDitanganiRelationManager;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users'; // Ganti ikon

    protected static ?string $navigationGroup = 'Pengaturan Pengguna'; // Kelompokkan menu

    protected static ?int $navigationSort = 1; // Urutan menu

    protected static ?string $modelLabel = 'Pengguna';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Pengguna')
                    ->description('Masukkan detail data pengguna.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Alamat Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true), // Unik kecuali record saat ini (edit)
                        // Field Password hanya required saat membuat user baru
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            // ->required() // Dihapus agar tidak wajib di edit
                            ->required(fn (string $context): bool => $context === 'create') // Hanya required saat create
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state)) // Hash password saat menyimpan
                            ->dehydrated(fn ($state) => filled($state)) // Hanya proses jika diisi (agar tidak menimpa password saat edit jika kosong)
                            ->revealable() // Tambahkan ikon mata untuk show/hide password
                            ->helperText('Kosongkan jika tidak ingin mengubah password saat edit.'),
                            // ->confirmed() // Jika ingin ada konfirmasi password (tambahkan field password_confirmation)
                        // TextInput::make('password_confirmation')->password()->label('Konfirmasi Password')->required(fn (string $context): bool => $context === 'create'),

                    ])->columns(2), // Bagi section jadi 2 kolom

                Section::make('Role Pengguna')
                    ->schema([
                        Select::make('roles') // Nama field harus 'roles' sesuai relasi Spatie
                            ->label('Role / Peran')
                            ->relationship('roles', 'name') // Relasi ke 'roles', tampilkan kolom 'name'
                            ->multiple() // Bisa pilih lebih dari satu role
                            ->preload() // Load opsi roles saat form dibuka
                            ->searchable() // Aktifkan pencarian role
                            // ->required() // Role sebaiknya wajib diisi
                            // Opsi untuk membatasi role yang bisa dipilih (misal Admin tidak bisa membuat Admin lain)
                            // ->options(fn() => \Spatie\Permission\Models\Role::where('name', '!=', 'Admin')->pluck('name', 'id'))
                            // ->visible(fn() => auth()->user()->hasRole('Admin')) // Hanya Admin yg bisa set role?
                            ->helperText('Pilih satu atau lebih peran untuk pengguna ini.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()->searchable()->label('ID'),
                TextColumn::make('name')
                    ->label('Nama Pengguna')
                    ->searchable()
                    ->sortable()
                    ->description(fn (User $record): string => $record->email), // Tampilkan email sebagai deskripsi
                // TextColumn::make('email') // Bisa dipisah jika mau
                //     ->label('Email')
                //     ->searchable()
                //     ->sortable(),
                // Gunakan BadgeColumn untuk Roles agar lebih menarik
                BadgeColumn::make('roles.name')
                    ->label('Roles')
                    ->colors([ // Atur warna badge berdasarkan nama role
                        'primary', // Warna default
                        'success' => 'Petugas Lapangan',
                        'danger' => 'Admin',
                    ])
                    ->searchable(), // Memungkinkan pencarian berdasarkan nama role
                // --- KOLOM BARU: Kelompok Ditangani (ViewColumn) ---
                ViewColumn::make('kelompok_ditangani_view') // Beri nama unik
                    ->label('Kelompok Ditangani')
                    ->visible(fn (): bool => auth()->user()->hasRole('Admin'))
                    // Tentukan view Blade yang akan digunakan
                    ->view('filament.tables.columns.bulleted-list')
                    // Gunakan state() untuk menyiapkan data (collection nama kelompok) yang akan dikirim ke view
                    ->state(function (User $record): ?\Illuminate\Support\Collection {
                        if ($record->hasRole('Petugas Lapangan')) {
                            // Ambil collection nama kelompok
                            $kelompoks = $record->kelompokYangDitangani()
                                            ->orderBy('nama_kelompok')
                                            ->pluck('nama_kelompok');

                            // Tidak perlu escaping di sini, view akan menampilkannya
                            return $kelompoks->isNotEmpty() ? $kelompoks : null; // Kirim null jika kosong
                        }
                        return null;
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Sembunyikan default, bisa ditampilkan user
                TextColumn::make('updated_at')
                    ->label('Tanggal Diperbarui')
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
                // Filter berdasarkan Role
                SelectFilter::make('roles')
                    ->label('Filter Berdasarkan Role')
                    ->relationship('roles', 'name') // Filter berdasarkan relasi roles
                    ->multiple()
                    ->preload()
                    ->searchable(),
                // Tables\Filters\TrashedFilter::make(), // Aktifkan jika model User menggunakan SoftDeletes
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton()->tooltip('Lihat Detail'),
                Tables\Actions\EditAction::make()->iconButton()->tooltip('Edit Pengguna'),
                Tables\Actions\DeleteAction::make()->iconButton()->tooltip('Hapus Pengguna'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    // Tables\Actions\ForceDeleteBulkAction::make(), // Jika pakai SoftDeletes
                    // Tables\Actions\RestoreBulkAction::make(), // Jika pakai SoftDeletes
                    // --- Tambahkan Export Bulk Action ---
                    ExportBulkAction::make()
                        ->visible(fn (): bool => Auth::user()?->hasRole('Admin') ?? false),
                    // ------------------------------------
                ]),
            ])
            ->emptyStateActions([ // Tombol jika tabel kosong
                Tables\Actions\CreateAction::make(),
            ])
            ->striped() // Tambahkan efek belang-belang pada tabel
            ->defaultSort('created_at', 'desc'); // Urutkan berdasarkan terbaru
    }

    // Modifikasi getEloquentQuery untuk eager load relasi roles dan kelompokYangDitangani
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['roles', 'kelompokYangDitangani']);
    }

    public static function getRelations(): array
    {
        return [
            KelompokYangDitanganiRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            // 'view' => Pages\ViewUser::route('/{record}'), // Aktifkan jika ingin halaman view terpisah
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    // Jika menggunakan SoftDeletes pada model User
    // public static function getEloquentQuery(): Builder
    // {
    //     return parent::getEloquentQuery()
    //         ->withoutGlobalScopes([
    //             SoftDeletingScope::class,
    //         ]);
    // }

}
