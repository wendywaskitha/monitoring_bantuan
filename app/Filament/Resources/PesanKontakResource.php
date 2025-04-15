<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Infolists;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\PesanKontak;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PesanKontakResource\Pages;
use Illuminate\Support\HtmlString; // Untuk format pesan
use Filament\Tables\Filters\Filter; // Untuk filter status
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Tables\Columns\IconColumn; // Untuk status dibaca
use App\Filament\Resources\PesanKontakResource\RelationManagers;
use Filament\Tables\Columns\BadgeColumn; // Alternatif status dibaca
use Filament\Tables\Actions\Action as TableAction; // Untuk aksi kustom
use Filament\Infolists\Components\Actions as InfolistActions; // Untuk aksi di infolist
use Filament\Infolists\Components\Actions\Action as InfolistAction; // Untuk aksi di infolist

class PesanKontakResource extends Resource
{
    protected static ?string $model = PesanKontak::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationGroup = 'Interaksi'; // Grup baru atau yg sesuai
    protected static ?string $navigationLabel = 'Pesan Masuk';
    protected static ?string $pluralModelLabel = 'Pesan Masuk';
    protected static ?string $modelLabel = 'Pesan Kontak';
    protected static ?int $navigationSort = 10; // Urutan di navigasi

    // --- Badge Jumlah Pesan Baru di Navigasi ---
    public static function getNavigationBadge(): ?string
    {
        // Hitung jumlah pesan yang belum dibaca
        $count = static::getModel()::where('sudah_dibaca', false)->count();
        return $count > 0 ? (string) $count : null; // Tampilkan hanya jika > 0
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning'; // Warna badge (misal: kuning)
    }
    // -------------------------------------------

    // --- Form (Biasanya tidak perlu form create/edit dari sini) ---
    // public static function form(Form $form): Form
    // {
    //     return $form->schema([ /* ... */ ]);
    // }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_lengkap')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn(PesanKontak $record) => $record->email), // Tampilkan email di deskripsi
                TextColumn::make('subjek')
                    ->searchable()
                    ->limit(40) // Batasi panjang subjek
                    ->tooltip(fn(PesanKontak $record) => $record->subjek),
                TextColumn::make('pesan')
                    ->label('Ringkasan Pesan')
                    ->limit(60)
                    ->wrap()
                    ->tooltip(fn(PesanKontak $record) => $record->pesan),
                TextColumn::make('nama_instansi')
                    ->label('Instansi')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true), // Sembunyikan default
                TextColumn::make('nomor_telepon')
                    ->label('Telepon')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                // Status Dibaca (Pilih salah satu: Icon atau Badge)
                IconColumn::make('sudah_dibaca')
                    ->label('Dibaca')
                    ->boolean()
                    ->trueIcon('heroicon-s-check-circle')
                    ->falseIcon('heroicon-s-envelope') // Ikon amplop jika belum
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->alignCenter(),
                // BadgeColumn::make('sudah_dibaca')
                //     ->label('Status')
                //     ->formatStateUsing(fn (bool $state): string => $state ? 'Sudah Dibaca' : 'Baru')
                //     ->color(fn (bool $state): string => $state ? 'success' : 'warning')
                //     ->alignCenter(),
                TextColumn::make('created_at')
                    ->label('Diterima')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false), // Tampilkan default
            ])
            ->filters([
                // Filter Status Dibaca
                Filter::make('belum_dibaca')
                    ->label('Hanya Pesan Baru')
                    ->query(fn (Builder $query): Builder => $query->where('sudah_dibaca', false))
                    ->toggle() // Buat jadi toggle switch
                    ->indicator('Pesan Baru'), // Teks indikator saat aktif
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()->label('Lihat Pesan'), // Arahkan ke Infolist
                    // Aksi Kustom: Tandai Dibaca/Belum Dibaca
                    TableAction::make('toggleDibaca')
                        ->label(fn(PesanKontak $record): string => $record->sudah_dibaca ? 'Tandai Belum Dibaca' : 'Tandai Sudah Dibaca')
                        ->icon(fn(PesanKontak $record): string => $record->sudah_dibaca ? 'heroicon-o-envelope-open' : 'heroicon-o-envelope')
                        ->color(fn(PesanKontak $record): string => $record->sudah_dibaca ? 'gray' : 'success')
                        ->requiresConfirmation()
                        ->action(function (PesanKontak $record): void {
                            $record->update(['sudah_dibaca' => !$record->sudah_dibaca]);
                            $status = $record->sudah_dibaca ? 'dibaca' : 'belum dibaca';
                            Notification::make()->title("Pesan ditandai {$status}")->success()->send();
                        }),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    // Bulk Action: Tandai Sudah Dibaca
                    Tables\Actions\BulkAction::make('tandaiSudahDibaca')
                        ->label('Tandai Sudah Dibaca')
                        ->icon('heroicon-o-envelope-open')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['sudah_dibaca' => true]))
                        ->deselectRecordsAfterCompletion(),
                    // Bulk Action: Tandai Belum Dibaca
                    Tables\Actions\BulkAction::make('tandaiBelumDibaca')
                        ->label('Tandai Belum Dibaca')
                        ->icon('heroicon-o-envelope')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['sudah_dibaca' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc') // Tampilkan pesan terbaru dulu
            ->striped();
    }

    // --- Infolist untuk Halaman View ---
    public static function infolist(Infolist $infolist): Infolist
    {
        /** @var PesanKontak $record */
        $record = $infolist->getRecord();

        // Tandai sudah dibaca saat view dibuka (jika belum)
        if (!$record->sudah_dibaca) {
            $record->update(['sudah_dibaca' => true]);
        }

        return $infolist
            ->schema([
                InfolistSection::make('Detail Pengirim')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('nama_lengkap'),
                        TextEntry::make('email')->copyable()->url(fn($state) => "mailto:{$state}"), // Bisa dicopy & link mailto
                        TextEntry::make('nomor_telepon')->placeholder('-'),
                        TextEntry::make('nama_instansi')->label('Instansi')->placeholder('-'),
                        TextEntry::make('created_at')->label('Waktu Diterima')->dateTime('d F Y H:i:s'),
                        TextEntry::make('sudah_dibaca')->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Sudah Dibaca' : 'Baru')
                            ->color(fn (bool $state): string => $state ? 'success' : 'warning'),
                    ]),
                InfolistSection::make('Isi Pesan')
                    ->schema([
                        TextEntry::make('subjek')->weight('bold'),
                        TextEntry::make('pesan')
                            ->label(false) // Hilangkan label
                            // Render sebagai HTML (nl2br sudah di Mailable, tapi bisa juga di sini)
                            ->formatStateUsing(fn (?string $state): ?HtmlString => $state ? new HtmlString(nl2br(e($state))) : null)
                            ->extraAttributes(['class' => 'prose prose-sm dark:prose-invert max-w-none']), // Styling prose
                    ]),
            ])->columns(1); // Layout infolist 1 kolom
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
            'index' => Pages\ListPesanKontaks::route('/'),
            // 'create' => Pages\CreatePesanKontak::route('/create'), // Biasanya tidak perlu create dari admin
            'view' => Pages\ViewPesanKontak::route('/{record}'), // Aktifkan view
            // 'edit' => Pages\EditPesanKontak::route('/{record}/edit'), // Edit mungkin tidak perlu
        ];
    }

    public static function canViewAny(): bool
    {
        // Hanya izinkan jika user adalah Admin
        return Auth::user()?->hasRole('Admin') ?? false;
    }
    // ------------------------------------
}
