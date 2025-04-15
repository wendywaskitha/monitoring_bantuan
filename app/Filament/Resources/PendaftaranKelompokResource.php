<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Infolists;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use App\Models\PendaftaranKelompok;
use App\Models\User; // Import User
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use App\Models\Anggota; // Import Anggota
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use App\Models\Kelompok; // Import Kelompok
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Support\Facades\DB; // Import DB
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Actions as FormActions;
use Filament\Forms\Components\Actions\Action as FormAction;
use App\Filament\Resources\PendaftaranKelompokResource\Pages;
use Filament\Infolists\Components\Actions as InfolistActions;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Tables\Actions\Action as TableAction; // Untuk aksi kustom
use App\Filament\Resources\PendaftaranKelompokResource\RelationManagers;
use Filament\Tables\Actions\EditAction; // Mungkin hanya untuk status/catatan
use Filament\Notifications\Actions\Action as NotificationAction;

class PendaftaranKelompokResource extends Resource
{
    protected static ?string $model = PendaftaranKelompok::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Manajemen Kelompok'; // Gabung ke grup Kelompok?
    protected static ?string $navigationLabel = 'Pendaftaran Kelompok';
    protected static ?string $pluralModelLabel = 'Pendaftaran Kelompok';
    protected static ?string $modelLabel = 'Pendaftaran';
    protected static ?int $navigationSort = 2; // Setelah Kelompok

    // Badge Pendaftaran Baru
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'Baru')->count();
        return $count > 0 ? (string) $count : null;
    }
    public static function getNavigationBadgeColor(): string|array|null { return 'warning'; }

    // --- Form (Hanya untuk Edit Status/Catatan oleh Admin) ---
    public static function form(Form $form): Form
    {
        // Form ini mungkin hanya digunakan di action edit status, bukan create/edit utama
        return $form
            ->schema([
                Select::make('status')
                    ->options([
                        'Baru' => 'Baru',
                        'Diproses' => 'Diproses',
                        'Ditolak' => 'Ditolak',
                        'Diterima' => 'Diterima (Buat Kelompok)',
                    ])
                    ->required(),
                Textarea::make('catatan_verifikasi')
                    ->label('Catatan Verifikasi/Alasan Penolakan')
                    ->rows(3),
                // Hidden field untuk verifikator & waktu (diisi saat action)
            ]);
    }

    // --- Tabel List Pendaftaran ---
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('nama_kelompok_diajukan')
                    ->label('Nama Kelompok Diajukan')
                    ->searchable()
                    ->sortable()
                    ->description(fn(PendaftaranKelompok $r) => "Kontak: " . $r->nama_kontak . " (" . $r->telepon_kontak . ")"),
                TextColumn::make('jenis_kelompok')->sortable(),
                TextColumn::make('desa.nama_desa')
                    ->label('Lokasi')
                    ->sortable()
                    ->description(fn(PendaftaranKelompok $r) => "Kec. " . $r->kecamatan?->nama_kecamatan),
                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'Baru',
                        'info' => 'Diproses',
                        'danger' => 'Ditolak',
                        'success' => 'Diterima',
                    ])
                    ->sortable(),
                // --- Kolom Baru: Link ke Kelompok Jadi ---
                TextColumn::make('kelompokBaru.nama_kelompok') // Akses via relasi
                    ->label('Kelompok Jadi')
                    ->placeholder('-')
                    // Buat link ke halaman view KelompokResource
                    ->url(fn (PendaftaranKelompok $record): ?string =>
                        // Pengecekan $record->kelompok_baru_id sudah cukup di sini
                        $record->kelompok_baru_id ? KelompokResource::getUrl('view', ['record' => $record->kelompok_baru_id]) : null
                    )
                    ->openUrlInNewTab()
                    // --- PERBAIKI TYPE HINT DI SINI ---
                    ->visible(fn(?PendaftaranKelompok $record): bool => // <-- Tambahkan tanda tanya (?)
                        // Tambahkan pengecekan null untuk $record
                        $record !== null && $record->status === 'Diterima'
                    ),
                // -----------------------------------------
                TextColumn::make('created_at')->label('Tgl Daftar')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('verifikator.name')->label('Diverifikasi Oleh')->placeholder('-')->sortable(),
                TextColumn::make('diverifikasi_at')->label('Tgl Verifikasi')->dateTime('d M Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'Baru' => 'Baru',
                        'Diproses' => 'Diproses',
                        'Ditolak' => 'Ditolak',
                        'Diterima' => 'Diterima',
                    ]),
                SelectFilter::make('kecamatan')->relationship('kecamatan', 'nama_kecamatan')->searchable()->preload(),
                SelectFilter::make('jenis_kelompok')
                    ->options(['Pertanian' => 'Pertanian', 'Peternakan' => 'Peternakan', 'Perikanan' => 'Perikanan']),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()->label('Lihat Detail'),
                    // Action untuk memproses/verifikasi
                    TableAction::make('prosesVerifikasi')
                        ->label('Proses/Ubah Status')
                        ->icon('heroicon-o-check-badge')
                        ->form([ // Form di dalam modal action
                            Select::make('status_baru')
                                ->label('Ubah Status Menjadi')
                                ->options([
                                    'Diproses' => 'Diproses',
                                    'Ditolak' => 'Ditolak',
                                    // Opsi 'Diterima' akan ada di action terpisah
                                ])
                                ->required(),
                            Textarea::make('catatan_verifikasi_baru')
                                ->label('Catatan Verifikasi / Alasan Penolakan')
                                ->rows(3)
                                ->requiredIf('status_baru', 'Ditolak'), // Wajib jika ditolak
                        ])
                        ->action(function (PendaftaranKelompok $record, array $data): void {
                            $record->update([
                                'status' => $data['status_baru'],
                                'catatan_verifikasi' => $data['catatan_verifikasi_baru'],
                                'verifikator_id' => Auth::id(),
                                'diverifikasi_at' => now(),
                            ]);
                            Notification::make()->title('Status pendaftaran diperbarui')->success()->send();
                        })

                        // Tampilkan jika status BUKAN Diterima atau Ditolak
                        ->visible(fn(PendaftaranKelompok $record) => !in_array($record->status, ['Diterima', 'Ditolak'])),

                    // --- Action Baru: Terima & Buat Kelompok ---
                    TableAction::make('terimaDanBuatKelompok')
                        ->label('Buat Kelompok dari Pendaftaran Ini')
                        ->icon('heroicon-o-user-group')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Buat Kelompok Baru?')
                        ->modalDescription('Anda yakin ingin membuat data Kelompok resmi berdasarkan pendaftaran ini? Status pendaftaran akan diubah menjadi Diterima.')
                        ->action(function (PendaftaranKelompok $record): void {
                            // Cek ulang jika kelompok sudah pernah dibuat
                            if ($record->kelompok_baru_id) {
                                Notification::make()->warning()->title('Kelompok sudah pernah dibuat (ID: ' . $record->kelompok_baru_id . ').')->send();
                                return;
                            }

                            try {
                                $createdKelompokId = null;
                                DB::transaction(function () use ($record, &$createdKelompokId) { // Gunakan reference untuk ID
                                    // 1. Buat Kelompok baru
                                    $kelompokBaru = Kelompok::create([
                                        'nama_kelompok' => $record->nama_kelompok_diajukan,
                                        'jenis_kelompok' => $record->jenis_kelompok,
                                        'desa_id' => $record->desa_id,
                                        'alamat_sekretariat' => $record->alamat_singkat,
                                        'tanggal_dibentuk' => $record->created_at->toDateString(), // Gunakan tanggal daftar sebagai tgl bentuk
                                        // user_id (petugas PJ) bisa diisi nanti
                                    ]);
                                    $createdKelompokId = $kelompokBaru->id; // Simpan ID

                                    // 2. Buat Anggota baru untuk ketua
                                    $ketuaBaru = Anggota::create([
                                        'kelompok_id' => $kelompokBaru->id,
                                        'nama_anggota' => $record->nama_kontak,
                                        'nomor_telepon' => $record->telepon_kontak,
                                        'is_ketua' => true,
                                    ]);

                                    // 3. Update ketua_id di kelompok baru
                                    $kelompokBaru->update(['ketua_id' => $ketuaBaru->id]);

                                    // 4. Update status pendaftaran & simpan ID kelompok baru
                                    $record->update([
                                        'status' => 'Diterima',
                                        'catatan_verifikasi' => ($record->catatan_verifikasi ? $record->catatan_verifikasi . "\n" : '') . 'Kelompok baru dibuat (ID: ' . $kelompokBaru->id . ').',
                                        'verifikator_id' => Auth::id(),
                                        'diverifikasi_at' => now(),
                                        'kelompok_baru_id' => $kelompokBaru->id, // <-- Simpan ID kelompok
                                    ]);
                                }); // Akhir transaksi

                                Notification::make()
                                    ->title('Kelompok baru berhasil dibuat!')
                                    ->success()
                                    // Tambahkan action notifikasi untuk lihat kelompok baru
                                    ->actions([
                                        NotificationAction::make('viewKelompok')
                                            ->label('Lihat Kelompok')
                                            ->url(KelompokResource::getUrl('view', ['record' => $createdKelompokId]), shouldOpenInNewTab: true)
                                            ->button(),
                                    ])
                                    ->send();

                            } catch (\Exception $e) {
                                Log::error("Gagal buat kelompok dari pendaftaran ID {$record->id}: " . $e->getMessage());
                                Notification::make()->title('Gagal membuat kelompok baru')->body($e->getMessage())->danger()->send();
                            }
                        })
                        // Tampilkan hanya jika status 'Baru' atau 'Diproses' DAN kelompok_baru_id masih null
                        ->visible(fn(PendaftaranKelompok $record) => in_array($record->status, ['Baru', 'Diproses']) && $record->kelompok_baru_id === null),
                    // -----------------------------------------

                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    // Bulk action ubah status?
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    // --- Infolist untuk Halaman View ---
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Data Pendaftaran')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('nama_kelompok_diajukan')->label('Nama Kelompok'),
                        TextEntry::make('jenis_kelompok')->badge(),
                        TextEntry::make('status')->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Baru' => 'warning', 'Diproses' => 'info', 'Ditolak' => 'danger', 'Diterima' => 'success', default => 'gray',
                            }),
                        TextEntry::make('kecamatan.nama_kecamatan')->label('Kecamatan'),
                        TextEntry::make('desa.nama_desa')->label('Desa/Kelurahan'),
                        TextEntry::make('alamat_singkat'),
                        TextEntry::make('perkiraan_jumlah_anggota')->label('Perk. Jml Anggota'),
                        TextEntry::make('created_at')->label('Tgl Daftar')->dateTime('d F Y H:i'),
                        // --- Perbaiki Deskripsi Kegiatan ---
                        TextEntry::make('deskripsi_kegiatan')
                            ->columnSpanFull()
                            ->label('Deskripsi Kegiatan')
                            // ->wrap() // Hapus ini
                            ->extraAttributes(['class' => 'whitespace-normal break-words']) // <-- Tambahkan ini
                            ->placeholder('-'),
                        // --------------------------------
                    ]),
                InfolistSection::make('Kontak Penanggung Jawab')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('nama_kontak'),
                        TextEntry::make('telepon_kontak'),
                        TextEntry::make('email_kontak')->placeholder('-'),
                    ]),
                InfolistSection::make('Dokumen Pendukung')
                    ->schema([
                        // --- GANTI InfolistAction DENGAN TextEntry ---
                        TextEntry::make('dokumen_pendukung_path')
                            ->label(false) // Tanpa label
                            // Format state menjadi link HTML jika path ada
                            ->formatStateUsing(function (?string $state, PendaftaranKelompok $record): ?HtmlString {
                                if ($state && Storage::disk('public')->exists($state)) {
                                    $url = Storage::disk('public')->url($state);
                                    $namaFile = $record->dokumen_pendukung_nama_asli ?? basename($state); // Ambil nama asli atau nama file saja
                                    // Buat link HTML
                                    return new HtmlString(
                                        '<a href="' . e($url) . '" target="_blank" class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-500 dark:hover:text-primary-400">' .
                                        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v4.59L7.3 9.24a.75.75 0 00-1.1 1.02l3.25 3.5a.75.75 0 001.1 0l3.25-3.5a.75.75 0 10-1.1-1.02l-1.95 2.1V6.75z" clip-rule="evenodd" /></svg>' .
                                        e($namaFile) .
                                        '</a>'
                                    );
                                }
                                return new HtmlString('<span class="text-sm text-gray-500 dark:text-gray-400">Tidak ada dokumen pendukung.</span>'); // Tampilkan teks jika tidak ada
                            }),
                        // Hapus TextEntry 'no_dokumen' karena sudah dihandle di atas
                        // TextEntry::make('no_dokumen') ...
                        // ----------------------------------------------------
                    ]),
                InfolistSection::make('Status & Tindak Lanjut')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('status')->badge() /* ... */,
                        // --- Link ke Kelompok Jadi ---
                        TextEntry::make('kelompokBaru.nama_kelompok')
                           ->label('Kelompok yang Dibuat')
                           ->url(fn(PendaftaranKelompok $record): ?string =>
                               $record->kelompok_baru_id ? KelompokResource::getUrl('view', ['record' => $record->kelompok_baru_id]) : null
                           )
                           ->openUrlInNewTab()
                           ->placeholder('-')
                           ->visible(fn(PendaftaranKelompok $record) => $record->status === 'Diterima'),
                        // ---------------------------
                        TextEntry::make('verifikator.name')->label('Diverifikasi Oleh')->placeholder('-'),
                        TextEntry::make('diverifikasi_at')->label('Tgl Verifikasi')->dateTime('d F Y H:i')->placeholder('-'),
                        TextEntry::make('catatan_verifikasi')->columnSpanFull()->extraAttributes(['class' => 'whitespace-normal break-words'])->placeholder('-')
                           ->extraAttributes(['class' => 'whitespace-normal break-words']),
                    ]),
                    // ->visible(fn(PendaftaranKelompok $record) => $record->status !== 'Baru'),
                InfolistSection::make('Verifikasi')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('verifikator.name')->label('Diverifikasi Oleh')->placeholder('-'),
                        TextEntry::make('diverifikasi_at')->label('Tgl Verifikasi')->dateTime('d F Y H:i')->placeholder('-'),
                        // --- Perbaiki Catatan Verifikasi ---
                        TextEntry::make('catatan_verifikasi')
                            ->columnSpanFull()
                            // ->wrap() // Hapus ini
                            ->extraAttributes(['class' => 'whitespace-normal break-words']) // <-- Tambahkan ini
                            ->placeholder('-'),
                        // ---------------------------------
                    ])
                    ->visible(fn(PendaftaranKelompok $record) => $record->status !== 'Baru'), // Tampil jika sudah diproses
            ])->columns(1);
    }

    // ... (getRelations, getPages) ...
    public static function getRelations(): array {

        return [

        ];

    }
    public static function getPages(): array {
        return [
            'index' => Pages\ListPendaftaranKelompoks::route('/'),
            // 'create' => Pages\CreatePendaftaranKelompok::route('/create'), // Create dari frontend
            'view' => Pages\ViewPendaftaranKelompok::route('/{record}'),
            // 'edit' => Pages\EditPendaftaranKelompok::route('/{record}/edit'), // Edit via action
        ];
    }

    // Kontrol Akses Resource (Hanya Admin?)
    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('Admin') ?? false; // Sesuaikan role jika perlu
    }
}
