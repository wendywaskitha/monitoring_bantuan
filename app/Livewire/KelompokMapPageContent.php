<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Kelompok;
use App\Models\Kecamatan;
use App\Models\Desa;
use App\Models\Bantuan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class KelompokMapPageContent extends Component
{
    // Properti Filter yang sudah ada
    public ?string $filterKecamatan = null;
    public ?string $filterDesa = null;
    public ?string $filterJenisKelompok = null;
    // Hapus atau komentari jika filter petugas belum diimplementasikan
    public ?string $filterPetugas = null; // Untuk filter Petugas PJ (ID User atau 'none')

    public Collection $kelompokMapData;

    public function mount()
    {
        $this->loadKelompokData();
    }

    public function loadKelompokData(): void
    {
        $user = Auth::user();
        $isAdmin = $user?->hasRole('Admin') ?? false;

        $query = Kelompok::query()
            ->with(['desa.kecamatan', 'petugas']) // Eager load desa.kecamatan juga
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        // Terapkan Filter
        if ($this->filterKecamatan) {
            $query->whereHas('desa', fn(Builder $q) => $q->where('kecamatan_id', $this->filterKecamatan));
        }
        if ($this->filterDesa) {
            $query->where('desa_id', $this->filterDesa);
        }
        if ($this->filterJenisKelompok) {
            $query->where('jenis_kelompok', $this->filterJenisKelompok);
        }
        // --- TERAPKAN FILTER PETUGAS (Hanya jika Admin) ---
        if ($isAdmin && $this->filterPetugas) {
            if ($this->filterPetugas === 'none') { // Cek opsi 'Belum Ditugaskan'
                $query->whereNull('user_id');
            } else {
                $query->where('user_id', $this->filterPetugas); // Filter berdasarkan ID petugas
            }
        }
        // -------------------------------------------------

        // Pembatasan Petugas Lapangan
        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        // Pembatasan Petugas Lapangan
        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        // Ambil dan format data
        $this->kelompokMapData = $query->limit(1000)
                ->get()
                ->map(function(Kelompok $kelompok) {
                    // ... (logika map seperti sebelumnya) ...
                     // --- TAMBAHKAN KEMBALI DEFINISI $colorMap DI SINI ---
                    $colorMap = [
                        'Pertanian' => '#10B981', // Hijau Success
                        'Peternakan' => '#F59E0B', // Kuning/Orange Warning
                        'Perikanan' => '#3B82F6', // Biru Info
                        'default' => '#6B7280'  // <-- PASTIKAN DEFAULT ADA
                    ];
                    // ----------------------------------------------------
                     $warna = $colorMap[$kelompok->jenis_kelompok] ?? $colorMap['default'];
                     return [
                         'id' => $kelompok->id,
                         'latitude' => (float) $kelompok->latitude,
                         'longitude' => (float) $kelompok->longitude,
                         'nama' => $kelompok->nama_kelompok,
                         'jenis' => $kelompok->jenis_kelompok,
                         'warna' => $warna,
                         'desa' => $kelompok->desa?->nama_desa ?? 'N/A',
                         'kecamatan' => $kelompok->desa?->kecamatan?->nama_kecamatan ?? 'N/A', // Pastikan kecamatan di-load
                         'petugas' => $kelompok->petugas?->name ?? 'N/A',
                         'detail_url' => \App\Filament\Resources\KelompokResource::getUrl('view', ['record' => $kelompok->id]),
                     ];
                });
    }

    // Hook updated untuk filter
    public function updated($propertyName): void
    {
        // Tambahkan filterPetugas ke pengecekan
        if (in_array($propertyName, ['filterKecamatan', 'filterDesa', 'filterJenisKelompok', 'filterPetugas'])) {
            if ($propertyName === 'filterKecamatan') {
                $this->filterDesa = null;
            }
            $this->loadKelompokData();
        }
    }

    public function render()
    {
        $kecamatans = Kecamatan::orderBy('nama_kecamatan')->pluck('nama_kecamatan', 'id');
        $desas = collect();
        if ($this->filterKecamatan) {
            $desas = Desa::where('kecamatan_id', $this->filterKecamatan)->orderBy('nama_desa')->pluck('nama_desa', 'id');
        }
        $jenisKelompokOptions = ['Pertanian' => 'Pertanian', 'Peternakan' => 'Peternakan', 'Perikanan' => 'Perikanan'];
        // Ambil daftar petugas HANYA jika user adalah Admin
        $petugasList = collect();
        if (Auth::user()?->hasRole('Admin')) {
            $petugasList = User::role('Petugas Lapangan')->orderBy('name')->pluck('name', 'id');
        }

        return view('livewire.kelompok-map-page-content', [
            'kecamatans' => $kecamatans,
            'desas' => $desas,
            'jenisKelompokOptions' => $jenisKelompokOptions,
            'petugasList' => $petugasList, // Kirim daftar petugas ke view
        ]);
    }
}
