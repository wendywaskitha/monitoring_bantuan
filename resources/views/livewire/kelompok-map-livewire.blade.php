<div> {{-- Div utama komponen Livewire --}}

    {{-- Container Peta --}}
    <div id="kelompokMapLivewireContainer" style="height: 500px;" class="w-full rounded-lg z-0 relative">
         {{-- Placeholder Loading Awal --}}
         <div id="kelompokMapLivewireLoading" class="absolute inset-0 flex items-center justify-center bg-gray-50 dark:bg-gray-700/50 rounded-lg z-20">
             <x-filament::loading-indicator class="h-8 w-8 text-primary-500" />
             <span class="ml-2 text-gray-500 dark:text-gray-400">Memuat peta...</span>
         </div>
         {{-- Pesan jika tidak ada data atau error --}}
         <div id="kelompokMapLivewireMessage" class="absolute inset-0 hidden items-center justify-center bg-gray-50 dark:bg-gray-700/50 rounded-lg z-20">
             <p class="text-center text-gray-500 dark:text-gray-400 p-4"></p>
         </div>
         {{-- Div peta aktual --}}
         <div id="kelompokMapLivewireActual" class="w-full h-full rounded-lg z-10" style="display: none;"></div>
    </div>

    {{-- Load CSS & JS Leaflet & MarkerCluster --}}
    {{-- @push tidak berfungsi di view Livewire biasa, load di sini atau di layout utama --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

    {{-- CSS Kustom untuk Ikon & Popup --}}
    <style>
        .custom-leaflet-marker div { box-shadow: 0 1px 3px rgba(0,0,0,0.4); transition: transform 0.1s ease-in-out; }
        .custom-leaflet-marker:hover div { transform: scale(1.2); }
        .leaflet-popup-content-wrapper { border-radius: 0.5rem; }
        .leaflet-popup-content { margin: 12px 18px; font-size: 0.875rem; line-height: 1.25rem; }
        .leaflet-popup-content b { font-size: 1rem; font-weight: 600; color: #111827; }
        .dark .leaflet-popup-content-wrapper { background: #1f2937; color: #d1d5db; border: 1px solid #374151; }
        .dark .leaflet-popup-content b { color: #f9fafb; }
        .dark .leaflet-popup-tip { background: #1f2937; }
        .dark .leaflet-popup-close-button { color: #d1d5db; }
        .dark .leaflet-popup-close-button:hover { background-color: #374151; }
    </style>

    {{-- Script Inisialisasi Peta (dijalankan setelah Livewire update) --}}
    <script data-navigate-once> // Agar script tidak jalan ulang saat navigasi SPA biasa
        document.addEventListener('livewire:init', () => {
            // Cek jika peta sudah ada di window (untuk re-init saat navigasi)
            if (window.kelompokLivewireMapInstance) {
                window.kelompokLivewireMapInstance.remove();
                window.kelompokLivewireMapInstance = null;
            }
            // Panggil init map saat komponen Livewire ini di-mount/dirender
            initKelompokLivewireMap(@json($kelompokMapData)); // Kirim data awal
        });

        // Listener jika data di-refresh oleh Livewire (misal karena filter di komponen ini)
        // @this.on('kelompokDataUpdated', (event) => {
        //     console.log('Received kelompokDataUpdated event');
        //     initKelompokLivewireMap(event.data); // Panggil init lagi dengan data baru
        // });

        // Fungsi inisialisasi peta
        function initKelompokLivewireMap(kelompokData) {
            const mapContainerId = 'kelompokMapLivewireContainer';
            const mapElementId = 'kelompokMapLivewireActual';
            const loadingElement = document.getElementById('kelompokMapLivewireLoading');
            const messageElement = document.getElementById('kelompokMapLivewireMessage');
            const mapElement = document.getElementById(mapElementId);

            const showMapMessage = (message) => { /* ... fungsi show message ... */ };

            // Hancurkan instance peta lama jika ada
            if (window.kelompokLivewireMapInstance) {
                window.kelompokLivewireMapInstance.remove();
                window.kelompokLivewireMapInstance = null;
            }

            if (!mapElement) { console.error('Map element not found.'); return; }

            // Cek library dan data
            if (typeof L === 'undefined' || typeof L.markerClusterGroup === 'undefined') { showMapMessage('Gagal memuat komponen peta.'); return; }
            if (!kelompokData || kelompokData.length === 0) { showMapMessage('Tidak ada data kelompok dengan koordinat.'); return; }

            try {
                if(loadingElement) loadingElement.style.display = 'none';
                if(messageElement) messageElement.classList.add('hidden');
                if(mapElement) mapElement.style.display = 'block';

                const firstRecordWithCoords = kelompokData.find(k => k.latitude && k.longitude);
                const centerLat = firstRecordWithCoords ? firstRecordWithCoords.latitude : -4.84;
                const centerLng = firstRecordWithCoords ? firstRecordWithCoords.longitude : 122.49;
                const initialZoom = firstRecordWithCoords ? 12 : 10;

                const map = L.map(mapElementId).setView([centerLat, centerLng], initialZoom);
                window.kelompokLivewireMapInstance = map; // Simpan instance baru

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { /* ... options ... */ }).addTo(map);
                const markers = L.markerClusterGroup();
                const colorMap = { /* ... colors ... */ };
                function createCustomIcon(color) { /* ... fungsi ikon ... */ }

                kelompokData.forEach(kelompok => {
                    const lat = parseFloat(kelompok.latitude);
                    const lng = parseFloat(kelompok.longitude);
                    if (!isNaN(lat) && !isNaN(lng)) {
                        const icon = createCustomIcon(kelompok.warna);
                        let popupContent = `...`; // Buat popup content
                        const marker = L.marker([lat, lng], { icon: icon, title: kelompok.nama }).bindPopup(popupContent);
                        markers.addLayer(marker);
                    }
                });

                map.addLayer(markers);
                // if (markers.getLayers().length > 0) { map.fitBounds(markers.getBounds().pad(0.15)); }

                setTimeout(() => { if (map) map.invalidateSize(); }, 150);
                console.log('Leaflet map initialized via Livewire component.');

            } catch (error) {
                console.error("Error initializing Leaflet map:", error);
                showMapMessage('Terjadi kesalahan saat memuat peta.');
            }
        } // Akhir initKelompokLivewireMap

    </script>
</div>
