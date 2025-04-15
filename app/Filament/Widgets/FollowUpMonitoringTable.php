<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use App\Models\DistribusiBantuan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon; // Import Carbon
use App\Filament\Resources\DistribusiBantuanResource; // Untuk link

class FollowUpMonitoringTable extends BaseWidget
{
    protected static ?int $sort = 8; // Urutan setelah widget lain
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Distribusi Bantuan Perlu Tindak Lanjut';

    // protected static string $view = 'filament.widgets.follow-up-monitoring-table-widget';

    // Definisikan batas waktu (dalam bulan)
    protected int $monitoringDueMonths = 3;
    // protected int $reportingDueMonths = 6; // Jika perlu cek batas waktu pelaporan juga

    // Override getHeading untuk judul dinamis
    public function getHeading(): string
    {
        $user = Auth::user();
        $title = 'Distribusi Bantuan Perlu Tindak Lanjut';
         if ($user && !$user->hasRole('Admin')) { $title .= ' (Kelompok Anda)'; }
         return $title;
    }

    // --- TAMBAHKAN METHOD INI ---
    /**
     * Mendapatkan deskripsi atau hint untuk heading widget.
     */
    public function getHeadingDescription(): ?string
    {
        return "Menampilkan bantuan diterima yang belum dimonitor atau monitoring terakhir > {$this->monitoringDueMonths} bulan.";
    }
    // ---------------------------

    // --- ATAU COBA ICON PADA HEADING (Kurang Umum) ---
    // protected static ?string $headingIcon = 'heroicon-o-question-mark-circle';
    // protected static ?string $headingIconTooltip = 'Menampilkan bantuan diterima yang belum dimonitor atau monitoring terakhir > X bulan.';
    // -------------------------------------------------

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $isAdmin = $user?->hasRole('Admin') ?? false;

        // Hitung tanggal batas untuk monitoring terakhir
        $monitoringDueDate = Carbon::now()->subMonths($this->monitoringDueMonths);
        // $reportingDueDate = Carbon::now()->subMonths($this->reportingDueMonths); // Jika perlu

        return $table
            ->query(function (DistribusiBantuan $model) use ($user, $isAdmin, $monitoringDueDate): Builder {
                $query = $model->query()
                    ->with([
                        'kelompok.petugas', // Eager load petugas PJ
                        'bantuan',
                        'monitoringPemanfaatans' => fn ($q) => $q->latest('tanggal_monitoring'), // Ambil semua monitoring, urutkan terbaru
                        // 'pelaporanHasils' => fn ($q) => $q->latest('tanggal_pelaporan'), // Jika perlu cek pelaporan
                    ])
                    ->where('status_pemberian', 'Diterima Kelompok'); // Hanya yang sudah diterima

                // --- PEMBATASAN UNTUK PETUGAS LAPANGAN ---
                if (!$isAdmin) {
                    $query->whereHas('kelompok', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
                }
                // -----------------------------------------

                // --- KRITERIA PERLU TINDAK LANJUT ---
                $query->where(function (Builder $q) use ($monitoringDueDate) {
                    // Kondisi 1: Belum pernah dimonitor sama sekali
                    $q->whereDoesntHave('monitoringPemanfaatans')
                      // Kondisi 2: ATAU monitoring terakhir sudah lewat batas waktu
                      ->orWhereHas('monitoringPemanfaatans', function (Builder $mq) use ($monitoringDueDate) {
                          // Cek tanggal monitoring TERBARU untuk distribusi ini
                          $mq->whereRaw(DB::raw('(SELECT MAX(tanggal_monitoring) FROM monitoring_pemanfaatans WHERE monitoring_pemanfaatans.distribusi_bantuan_id = distribusi_bantuans.id) < ?'), [$monitoringDueDate]);
                          // Note: Query ini mungkin perlu dioptimalkan tergantung DB & jumlah data
                          // Alternatif: Menggunakan subquery atau window function jika didukung & lebih efisien
                      });
                      // Tambahkan kondisi untuk pelaporan jika perlu
                      // ->orWhereDoesntHave('pelaporanHasils')
                      // ->orWhereHas('pelaporanHasils', function (Builder $rq) use ($reportingDueDate) {
                      //     $rq->whereRaw(DB::raw('(SELECT MAX(tanggal_pelaporan) FROM pelaporan_hasils WHERE pelaporan_hasils.distribusi_bantuan_id = distribusi_bantuans.id) < ?'), [$reportingDueDate]);
                      // });
                });
                // ------------------------------------

                // Urutkan berdasarkan tanggal distribusi terlama yang perlu tindak lanjut
                return $query->orderBy('tanggal_distribusi', 'asc');
            })
            ->columns([
                TextColumn::make('kelompok.nama_kelompok')
                    ->label('Kelompok')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bantuan.nama_bantuan')
                    ->label('Bantuan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal_distribusi')
                    ->label('Tgl Diterima')
                    ->date('d M Y')
                    ->sortable(),
                // Kolom Status Monitoring/Pelaporan
                TextColumn::make('status_monitoring')
                    ->label('Monitoring Terakhir')
                    ->state(function (DistribusiBantuan $record): ?string {
                        // Ambil tanggal monitoring terakhir dari relasi yang sudah di-eager load
                        $monitoringTerakhir = $record->monitoringPemanfaatans->first(); // Karena sudah diorder desc
                        return $monitoringTerakhir?->tanggal_monitoring?->diffForHumans() ?? 'Belum Pernah';
                    })
                    ->color(function (DistribusiBantuan $record) use ($monitoringDueDate): string {
                        $monitoringTerakhir = $record->monitoringPemanfaatans->first();
                        if (!$monitoringTerakhir) return 'danger'; // Merah jika belum pernah
                        return $monitoringTerakhir->tanggal_monitoring->lt($monitoringDueDate) ? 'warning' : 'success'; // Kuning jika lewat batas, hijau jika belum
                    }),
                // Tambahkan kolom status pelaporan jika perlu
                // TextColumn::make('status_pelaporan')...
                TextColumn::make('kelompok.petugas.name')
                    ->label('Petugas PJ')
                    ->placeholder('-')
                    ->visible(fn (): bool => $isAdmin), // Hanya Admin
            ])
            ->recordUrl( // Link ke detail distribusi untuk tindak lanjut
                fn (DistribusiBantuan $record): string => DistribusiBantuanResource::getUrl('view', ['record' => $record]),
            )
            // ->defaultSort('tanggal_distribusi', 'asc') // Default sort sudah di query
            ->heading(function () use ($user, $isAdmin) { // Judul dinamis
                $title = 'Distribusi Bantuan Perlu Tindak Lanjut';
                 if (!$isAdmin) { $title .= ' (Kelompok Anda)'; }
                 return $title;
            });
            // Tidak perlu pagination atau filter kompleks di widget ini
            // ->paginated(false)
    }

     /**
     * Widget ini hanya tampil jika user adalah Admin atau Petugas Lapangan
     */
    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->hasAnyRole(['Admin', 'Petugas Lapangan']);
    }
}
