<?php

namespace App\Livewire;

use App\Models\MonitoringPemanfaatan;
use Livewire\Component;
use Illuminate\Database\Eloquent\Collection; // Import Collection

class MonitoringTimeline extends Component
{
    public int $distribusiBantuanId; // ID distribusi yang ingin ditampilkan timelinenya
    public ?int $currentMonitoringId = null; // ID monitoring saat ini (opsional, untuk highlight)

    public Collection $riwayatMonitoring; // Properti untuk menyimpan data

    /**
     * Mount komponen, terima ID distribusi dan ID monitoring saat ini (opsional).
     */
    public function mount(int $distribusiBantuanId, ?int $currentMonitoringId = null)
    {
        $this->distribusiBantuanId = $distribusiBantuanId;
        $this->currentMonitoringId = $currentMonitoringId;
        $this->loadRiwayat(); // Panggil method untuk load data
    }

    /**
     * Method untuk mengambil data riwayat monitoring.
     */
    public function loadRiwayat()
    {
        $this->riwayatMonitoring = MonitoringPemanfaatan::where('distribusi_bantuan_id', $this->distribusiBantuanId)
            ->with('user') // Eager load user
            ->orderBy('tanggal_monitoring', 'desc') // Urutkan terbaru di atas
            ->get();
    }

    /**
     * Render view blade komponen.
     */
    public function render()
    {
        // Data sudah di-load di $this->riwayatMonitoring oleh mount() atau loadRiwayat()
        return view('livewire.monitoring-timeline');
    }

     /**
      * Helper untuk mendapatkan URL view detail (jika diperlukan di view)
      */
     public function getDetailUrl(MonitoringPemanfaatan $monitoring): string
     {
         // Pastikan namespace Resource benar
         return \App\Filament\Resources\MonitoringPemanfaatanResource::getUrl('view', ['record' => $monitoring->id]);
     }
}
