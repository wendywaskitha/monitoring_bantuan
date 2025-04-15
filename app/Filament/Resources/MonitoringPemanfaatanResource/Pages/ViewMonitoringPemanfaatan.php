<?php

namespace App\Filament\Resources\MonitoringPemanfaatanResource\Pages;

use App\Filament\Resources\MonitoringPemanfaatanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable; // Import Htmlable

class ViewMonitoringPemanfaatan extends ViewRecord
{
    protected static string $resource = MonitoringPemanfaatanResource::class;

    // --- Opsi 1: Override Properti $title (Jika judulnya statis) ---
    // protected static ?string $title = 'Detail Monitoring Pemanfaatan'; // Judul statis

    // --- Opsi 2: Override Method getTitle() (Untuk judul dinamis berdasarkan record) ---
    public function getTitle(): string | Htmlable
    {
        /** @var \App\Models\MonitoringPemanfaatan $record */
        $record = $this->getRecord(); // Dapatkan record saat ini

        // Buat judul yang lebih deskriptif
        $kelompok = $record->distribusiBantuan?->kelompok?->nama_kelompok ?? 'N/A';
        $bantuan = $record->distribusiBantuan?->bantuan?->nama_bantuan ?? 'N/A';
        $tanggal = $record->tanggal_monitoring?->format('d M Y') ?? 'N/A';

        // Gabungkan menjadi judul
        // return "Monitoring: {$kelompok} - {$bantuan} ({$tanggal})";
        // Atau bisa juga hanya menampilkan tanggal monitoringnya saja jika terlalu panjang
        return "Detail Monitoring ({$tanggal})";
    }

    // --- Opsi 3: Override getSubheading() untuk info tambahan ---
    public function getSubheading(): string | Htmlable | null
    {
         /** @var \App\Models\MonitoringPemanfaatan $record */
        $record = $this->getRecord();
        $kelompok = $record->distribusiBantuan?->kelompok?->nama_kelompok ?? 'N/A';
        $bantuan = $record->distribusiBantuan?->bantuan?->nama_bantuan ?? 'N/A';

        return "Kelompok: {$kelompok} | Bantuan: {$bantuan}"; // Tampilkan info kelompok & bantuan di subheading
    }


    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    // Method getFooter() jika Anda menggunakannya untuk timeline
    // protected function getFooter(): ?\Illuminate\Contracts\View\View
    // {
    //     // ... (kode footer jika ada) ...
    // }

    /**
     * Override breadcrumbs untuk halaman View.
     */
    public function getBreadcrumbs(): array
    {
        $resource = static::getResource(); // Dapatkan class resource
        $record = $this->getRecord(); // Dapatkan record saat ini

        // Ambil tanggal untuk ditampilkan di breadcrumb
        $tanggal = $record->tanggal_monitoring?->format('d M Y') ?? $record->id; // Fallback ke ID jika tanggal null

        return [
            // Link ke halaman index resource
            $resource::getUrl('index') => $resource::getBreadcrumb(),
            // Teks untuk record saat ini (tidak bisa diklik)
            // Gunakan tanggal atau info lain yang relevan
            "Monitoring ({$tanggal})",
            // Teks untuk halaman view saat ini
            // static::getNavigationLabel(), // Bisa pakai label navigasi
            'Lihat Detail', // Atau teks kustom
        ];
    }
}
