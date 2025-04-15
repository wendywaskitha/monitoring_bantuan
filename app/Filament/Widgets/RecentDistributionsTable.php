<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\DistribusiBantuanResource; // Import resource
use App\Models\DistribusiBantuan;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class RecentDistributionsTable extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full'; // Ambil lebar penuh

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Query untuk mengambil data distribusi terbaru
                DistribusiBantuan::query()
                    ->with(['kelompok', 'bantuan']) // Eager load relasi
                    ->latest('tanggal_distribusi') // Urutkan berdasarkan tanggal terbaru
                    ->limit(5) // Ambil 5 data teratas
            )
            ->columns([
                TextColumn::make('kelompok.nama_kelompok')
                    ->label('Kelompok')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bantuan.nama_bantuan')
                    ->label('Bantuan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jumlah')
                    ->alignRight(),
                TextColumn::make('satuan'),
                TextColumn::make('tanggal_distribusi')
                    ->date('d M Y')
                    ->sortable(),
                BadgeColumn::make('status_pemberian')
                    ->label('Status')
                    ->colors([
                        'gray' => 'Direncanakan',
                        'info' => 'Proses Pengiriman',
                        'success' => 'Diterima Kelompok',
                        'danger' => 'Dibatalkan',
                    ]),
            ])
            // Aksi untuk mengarahkan ke detail record jika diklik
            ->recordUrl(
                fn (DistribusiBantuan $record): string => DistribusiBantuanResource::getUrl('view', ['record' => $record]),
            );
            // Tidak perlu actions, filters, bulk actions di widget tabel ini
    }
}
