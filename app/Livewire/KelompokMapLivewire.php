<?php

namespace App\Livewire; // Pastikan namespace benar

use Livewire\Component;
use App\Models\Kelompok;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class KelompokMapLivewire extends Component
{
    // Properti untuk menyimpan data yang akan dikirim ke view
    public Collection $kelompokMapData;

    /**
     * Mount komponen: Ambil data awal.
     */
    public function mount()
    {
        $this->loadKelompokData();
    }

    /**
     * Method untuk mengambil dan memformat data kelompok.
     */
    public function loadKelompokData(): void
    {
        $user = Auth::user();
        $query = Kelompok::query()
            ->with(['desa', 'petugas']) // Eager load relasi
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        // Batasi untuk petugas lapangan
        if ($user && !$user->hasRole('Admin')) {
            $query->where('user_id', $user->id);
        }

        // Ambil data dan format
        $this->kelompokMapData = $query->limit(500) // Batasi data
                ->get()
                ->map(function(Kelompok $kelompok) {
                    $colorMap = [
                        'Pertanian' => '#10B981', 'Peternakan' => '#F59E0B',
                        'Perikanan' => '#3B82F6', 'default' => '#6B7280'
                    ];
                    $warna = $colorMap[$kelompok->jenis_kelompok] ?? $colorMap['default'];

                    return [
                        'id' => $kelompok->id,
                        'latitude' => (float) $kelompok->latitude,
                        'longitude' => (float) $kelompok->longitude,
                        'nama' => $kelompok->nama_kelompok,
                        'jenis' => $kelompok->jenis_kelompok,
                        'warna' => $warna,
                        'desa' => $kelompok->desa?->nama_desa ?? 'N/A',
                        'kecamatan' => $kelompok->desa?->kecamatan?->nama_kecamatan ?? 'N/A',
                        'petugas' => $kelompok->petugas?->name ?? 'N/A',
                        'detail_url' => \App\Filament\Resources\KelompokResource::getUrl('view', ['record' => $kelompok->id]),
                    ];
                });
    }

    /**
     * Render view blade komponen Livewire.
     */
    public function render()
    {
        // Data sudah ada di $this->kelompokMapData
        return view('livewire.kelompok-map-livewire');
    }
}
