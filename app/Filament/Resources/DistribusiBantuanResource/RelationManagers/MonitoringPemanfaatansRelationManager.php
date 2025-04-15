<?php

// Pastikan namespace sudah benar
namespace App\Filament\Resources\DistribusiBantuanResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\MonitoringPemanfaatan; // Import model
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\BadgeColumn; // <-- Pastikan menggunakan BadgeColumn

class MonitoringPemanfaatansRelationManager extends RelationManager
{
    // Nama relasi dari model DistribusiBantuan ke MonitoringPemanfaatan
    protected static string $relationship = 'monitoringPemanfaatans';

    // Judul record di tabel relation manager
    protected static ?string $recordTitleAttribute = 'tanggal_monitoring';

    // Label untuk menu/tab relation manager
    protected static ?string $modelLabel = 'Monitoring Pemanfaatan';
    protected static ?string $pluralModelLabel = 'Riwayat Monitoring';

    // Definisi form untuk membuat/mengedit data monitoring via relation manager
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // distribusi_bantuan_id tidak perlu diinput, otomatis dari relasi
                DatePicker::make('tanggal_monitoring')
                    ->label('Tanggal Monitoring') // Tambahkan label jika perlu
                    ->required()
                    ->native(false) // Gunakan datepicker JS
                    ->default(now()), // Default tanggal hari ini
                TextInput::make('persentase_perkembangan')
                    ->label('Persentase Perkembangan (%)')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%'),
                Textarea::make('deskripsi_pemanfaatan')
                    ->label('Deskripsi Pemanfaatan/Perkembangan')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(), // Ambil lebar penuh
                FileUpload::make('foto_dokumentasi')
                    ->label('Foto Dokumentasi')
                    ->disk('public') // Gunakan disk public (pastikan storage:link sudah jalan)
                    ->directory('monitoring-docs') // Folder penyimpanan
                    ->image() // Hanya gambar
                    ->maxSize(2048) // Max 2MB
                    ->imageEditor() // Aktifkan editor gambar
                    ->columnSpanFull(),
                Textarea::make('catatan_monitoring')
                    ->label('Catatan Tambahan Petugas')
                    ->rows(3)
                    ->columnSpanFull(),
                Hidden::make('user_id')->default(auth()->id()), // ID petugas yg login
            ])->columns(1); // Buat form 1 kolom saja di relation manager
    }

    // Definisi tabel untuk menampilkan data monitoring di relation manager
    public function table(Table $table): Table
    {
        return $table
            // ->recordTitleAttribute('tanggal_monitoring') // Bisa diaktifkan jika perlu
            ->columns([
                TextColumn::make('tanggal_monitoring')
                    ->date('d M Y') // Format tanggal
                    ->sortable(),
                // Gunakan BadgeColumn sebagai pengganti ProgressColumn
                BadgeColumn::make('persentase_perkembangan')
                    ->label('Perkembangan')
                    ->suffix('%')
                    ->colors([ // Atur warna badge berdasarkan persentase
                        'danger' => fn ($state): bool => $state < 25,
                        'warning' => fn ($state): bool => $state >= 25 && $state < 75,
                        'success' => fn ($state): bool => $state >= 75,
                        'gray' // Warna default
                    ])
                    ->sortable(),
                TextColumn::make('deskripsi_pemanfaatan')
                    ->label('Deskripsi') // Label lebih pendek
                    ->limit(50) // Batasi panjang teks di tabel
                    ->tooltip(fn (MonitoringPemanfaatan $record): string => $record->deskripsi_pemanfaatan), // Tampilkan full di tooltip
                ImageColumn::make('foto_dokumentasi')
                    ->label('Foto')
                    ->disk('public')
                    ->toggleable(isToggledHiddenByDefault: true), // Sembunyikan default
                TextColumn::make('user.name') // Tampilkan nama petugas
                    ->label('Petugas')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Dicatat')
                    ->dateTime('d M Y H:i') // Format tanggal dan waktu
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filter mungkin tidak terlalu relevan di konteks relation manager ini
            ])
            ->headerActions([
                // Tombol untuk membuat record baru di relation manager
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Aksi per baris (edit, delete)
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                 // Kontrol visibilitas berdasarkan permission (sesuaikan nama permission jika perlu)
                 ->visible(fn () => Auth::user()->can('delete_monitoring::pemanfaatan')),
            ])
            ->bulkActions([
                // Aksi untuk multiple selection
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                     // Kontrol visibilitas berdasarkan permission
                     ->visible(fn () => Auth::user()->can('delete_monitoring::pemanfaatan')),
                ]),
            ])
            ->striped() // Efek belang-belang
            ->defaultSort('tanggal_monitoring', 'desc'); // Urutkan terbaru dulu
    }
}
