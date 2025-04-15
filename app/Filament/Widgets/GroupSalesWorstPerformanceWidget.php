<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\KelompokResource;
use App\Models\Kelompok;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GroupSalesWorstPerformanceWidget extends BaseWidget
{
    protected static ?int $sort = 6; // Urutan setelah widget Top 10
    // protected int | string | array $columnSpan = 'full'; // Ambil lebar penuh
    protected static ?string $heading = 'Kelompok dengan Penjualan Terendah (Bottom 5)'; // Judul default

    public function table(Table $table): Table
    {
        $user = Auth::user();

        return $table
            ->query(function (Kelompok $model) use ($user): Builder {
                // Query dasar untuk kelompok
                $query = $model->query()
                    ->select('kelompoks.*')
                    // Hitung total penjualan, pastikan relasi allPelaporanHasils() ada
                    ->withSum('allPelaporanHasils as total_penjualan_agg', 'total_penjualan');

                // Pembatasan untuk Petugas Lapangan
                if ($user && !$user->hasRole('Admin')) {
                    $query->where('user_id', $user->id);
                }

                // Urutkan berdasarkan total penjualan ASCENDING (dari terendah)
                // Kelompok dengan penjualan NULL atau 0 akan muncul di atas
                return $query->orderBy('total_penjualan_agg', 'asc') // <-- Urutkan ASC
                             ->limit(5); // Ambil Bottom 5
            })
            ->columns([
                // Kolom sama seperti widget Top 10
                TextColumn::make('nama_kelompok')
                    ->label('Nama Kelompok')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('desa.nama_desa')
                    ->label('Desa')
                    ->sortable(),
                TextColumn::make('petugas.name')
                    ->label('Petugas PJ')
                    ->placeholder('-')
                    ->visible(fn (): bool => $user?->hasRole('Admin') ?? false),
                TextColumn::make('total_penjualan_agg')
                    ->label('Total Penjualan Dilaporkan')
                    ->money('IDR', 0)
                    ->sortable(),
                TextColumn::make('total_nilai_bantuan_diterima')
                    ->label('Total Bantuan Diterima')
                    ->money('IDR', 0)
                    ->sortable(),
            ])
            ->recordUrl(
                fn (Kelompok $record): string => KelompokResource::getUrl('view', ['record' => $record]),
            )
            ->paginated(false)
            ->heading(function () use ($user) { // Judul dinamis
                if ($user && !$user->hasRole('Admin')) {
                    return 'Kelompok Anda dengan Penjualan Terendah (Bottom 5)';
                }
                return 'Kelompok dengan Penjualan Terendah (Bottom 5)';
            });
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
