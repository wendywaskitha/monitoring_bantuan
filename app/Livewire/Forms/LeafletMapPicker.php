<?php
namespace App\Livewire\Forms;
use Livewire\Component;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;

class LeafletMapPicker extends Component implements HasForms
{
    use InteractsWithForms;

    public $latitude;
    public $longitude;
    public array $defaultLocation = [-4.84, 122.49];
    public int $defaultZoom = 10;
    public string $mapId;
    public ?string $latitudeFieldName = null;
    public ?string $longitudeFieldName = null;
    public ?string $statePath = null;
    public ?int $mapHeight = 400; // Terima tinggi peta

    protected $listeners = ['updateMapCenter']; // Listener untuk update dari Filament

    public function mount($latitude = null, $longitude = null, $statePath = null, $latitudeFieldName = null, $longitudeFieldName = null, $defaultLocation = null, $defaultZoom = null, $mapHeight = null)
    {
        $this->latitude = $latitude ?? ($defaultLocation[0] ?? $this->defaultLocation[0]);
        $this->longitude = $longitude ?? ($defaultLocation[1] ?? $this->defaultLocation[1]);
        $this->defaultLocation = $defaultLocation ?? $this->defaultLocation;
        $this->defaultZoom = $defaultZoom ?? $this->defaultZoom;
        $this->mapId = 'leaflet-map-' . ($statePath ? str_replace(['.', '[', ']'], '-', $statePath) : uniqid());
        $this->statePath = $statePath;
        $this->latitudeFieldName = $latitudeFieldName;
        $this->longitudeFieldName = $longitudeFieldName;
        $this->mapHeight = $mapHeight ?? $this->mapHeight; // Set tinggi peta
    }

    public function updateLocation($lat, $lng)
    {
        $this->latitude = $lat;
        $this->longitude = $lng;
        // Kirim event ke JS untuk update field Filament
        $this->dispatch('updateFilamentFields', [
             $this->latitudeFieldName => $this->latitude,
             $this->longitudeFieldName => $this->longitude,
        ]);
    }

     // Dipanggil oleh JS ketika field Filament berubah
     public function updateMapCenter($latitude, $longitude)
     {
         if (is_numeric($latitude) && is_numeric($longitude)) {
             $this->latitude = $latitude;
             $this->longitude = $longitude;
             // Kirim event ke JS untuk memindahkan marker
             $this->dispatch('move-leaflet-marker-' . $this->mapId, lat: $this->latitude, lng: $this->longitude);
         }
     }

    public function render()
    {
        return view('livewire.forms.leaflet-map-picker');
    }
}
