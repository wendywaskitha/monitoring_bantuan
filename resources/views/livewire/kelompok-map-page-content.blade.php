<div> {{-- Div utama komponen Livewire --}}

    {{-- Section Filter --}}
    <x-filament::section collapsible collapsed> {{-- Collapsible & collapsed by default --}}
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                 <x-heroicon-o-funnel class="h-5 w-5 text-gray-500 dark:text-gray-400"/>
                 <span>Filter Peta Kelompok</span>
            </div>
        </x-slot>

        {{-- Form Filter --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 p-4">

            {{-- Filter Kecamatan --}}
            <div>
                <label for="mapFilterKecamatan" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Kecamatan</label>
                <select wire:model.live="filterKecamatan" id="mapFilterKecamatan"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                    <option value="">-- Semua Kecamatan --</option>
                    {{-- Pastikan $kecamatans dikirim dari render() --}}
                    @if(isset($kecamatans))
                        @foreach($kecamatans as $id => $nama)
                            <option value="{{ $id }}">{{ $nama }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            {{-- Filter Desa (Dinamis) --}}
            <div>
                <label for="mapFilterDesa" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Desa/Kelurahan</label>
                <select wire:model.live="filterDesa" id="mapFilterDesa"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                        @if(!isset($desas) || $desas->isEmpty()) disabled @endif> {{-- Disable jika tidak ada kecamatan dipilih atau tidak ada desa --}}
                    <option value="">-- Semua Desa --</option>
                    {{-- Opsi desa diisi dari $desas --}}
                    @if(isset($desas))
                        @foreach($desas as $id => $nama)
                            <option value="{{ $id }}">{{ $nama }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            {{-- Filter Jenis Kelompok --}}
            <div>
                <label for="mapFilterJenisKelompok" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Jenis Kelompok</label>
                <select wire:model.live="filterJenisKelompok" id="mapFilterJenisKelompok"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                    <option value="">-- Semua Jenis --</option>
                    {{-- Pastikan $jenisKelompokOptions dikirim dari render() atau didefinisikan di sini --}}
                    @php
                        $jenisOpts = ['Pertanian' => 'Pertanian', 'Peternakan' => 'Peternakan', 'Perikanan' => 'Perikanan'];
                    @endphp
                    @foreach($jenisOpts as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Petugas PJ (Hanya Admin) --}}
            @if(auth()->user()?->hasRole('Admin')) {{-- Cek role Admin --}}
                <div>
                    <label for="mapFilterPetugas" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Petugas PJ</label>
                    <select wire:model.live="filterPetugas" id="mapFilterPetugas"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                        <option value="">-- Semua Petugas --</option>
                        <option value="none">Belum Ditugaskan</option> {{-- Opsi khusus --}}
                        {{-- Pastikan $petugasList dikirim dari render() --}}
                        @if(isset($petugasList))
                            @foreach($petugasList as $id => $nama)
                                <option value="{{ $id }}">{{ $nama }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            @endif

        </div>
        {{-- Akhir Form Filter --}}
    </x-filament::section>

    <x-filament::section class="mt-4"> {{-- Beri margin atas --}}
        <div wire:key="map-container-{{ rand() }}"
             id="kelompokMapPageContainer" style="height: 600px;"
             class="w-full rounded-lg z-0 relative bg-gray-50 dark:bg-gray-800/50">
             {{-- Placeholder Loading --}}
             <div id="kelompokMapPageLoading" class="absolute inset-0 flex items-center justify-center z-20">
                 <x-filament::loading-indicator class="h-8 w-8 text-primary-500" />
                 <span class="ml-2 text-gray-500 dark:text-gray-400">Memuat peta...</span>
             </div>
             {{-- Pesan Error/No Data --}}
             <div id="kelompokMapPageMessage" class="absolute inset-0 hidden items-center justify-center z-20">
                 <p class="text-center text-gray-500 dark:text-gray-400 p-4"></p>
             </div>
             {{-- Div Peta Aktual --}}
             <div id="kelompokMapPageActual" class="w-full h-full rounded-lg z-10" style="display: none;"></div>
        </div>
    </x-filament::section>


    {{-- Load CSS & JS Leaflet & MarkerCluster --}}
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
        {{-- CSS untuk ikon kustom dan popup --}}
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
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
        <script>
            // Gunakan IIFE untuk scope lokal
            (function() {
                // Beri nama unik untuk flag dan instance peta halaman ini
                const mapPageContainerId = 'kelompokMapPageContainer';
                const mapPageElementId = 'kelompokMapPageActual';
                const loadingPageElement = document.getElementById('kelompokMapPageLoading');
                const messagePageElement = document.getElementById('kelompokMapPageMessage');
                const mapPageElement = document.getElementById(mapPageElementId);
                let mapPageInstance = null; // Instance peta untuk halaman ini

                const containerPageElement = document.getElementById(mapPageContainerId);
                // Cek flag inisialisasi pada container
                if (!containerPageElement || containerPageElement.dataset.mapInitialized === 'true') {
                    console.log(`Map ${mapPageContainerId} already initialized or element not found.`);
                    return; // Keluar jika sudah diinisialisasi atau elemen tidak ada
                }
                containerPageElement.dataset.mapInitialized = 'true'; // Tandai sudah diinisialisasi

                // Ambil data kelompok dari properti Livewire $kelompokMapData
                const kelompokData = @json($kelompokMapData);

                const showMapMessage = (message) => {
                    if(loadingPageElement) loadingPageElement.style.display = 'none';
                    if(messagePageElement) {
                        messagePageElement.querySelector('p').textContent = message;
                        messagePageElement.classList.remove('hidden');
                        messagePageElement.classList.add('flex');
                    }
                     if(mapPageElement) mapPageElement.style.display = 'none';
                };

                // Fungsi inisialisasi peta
                function initializeKelompokPageMap() {
                    console.log('Attempting to initialize map on page...');
                    if (!mapPageElement || typeof L === 'undefined' || typeof L.markerClusterGroup === 'undefined') {
                        console.error('Leaflet or MarkerCluster not loaded properly or map element not found.');
                        showMapMessage('Gagal memuat komponen peta.');
                        return;
                    }
                     if (!kelompokData || kelompokData.length === 0) {
                        console.warn('No kelompok data with coordinates.');
                        showMapMessage('Tidak ada data kelompok dengan koordinat.');
                        return;
                    }

                    try {
                        if(loadingPageElement) loadingPageElement.style.display = 'none';
                        if(messagePageElement) messagePageElement.classList.add('hidden');
                        if(mapPageElement) mapPageElement.style.display = 'block'; // Tampilkan div peta

                        const firstRecordWithCoords = kelompokData.find(k => k.latitude && k.longitude);
                        const centerLat = firstRecordWithCoords ? firstRecordWithCoords.latitude : -4.84; // Default Muna Barat
                        const centerLng = firstRecordWithCoords ? firstRecordWithCoords.longitude : 122.49;
                        const initialZoom = firstRecordWithCoords ? 12 : 10; // Zoom lebih dekat jika ada data

                        // Gunakan variabel instance peta halaman ini
                        mapPageInstance = L.map(mapPageElementId).setView([centerLat, centerLng], initialZoom);
                        // Simpan instance global jika perlu (opsional)
                        // window.kelompokPageMapInstance = mapPageInstance;

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                        }).addTo(mapPageInstance);

                        const markers = L.markerClusterGroup(); // Buat cluster group
                        const colorMap = {
                            'Pertanian': '#10B981', 'Peternakan': '#F59E0B',
                            'Perikanan': '#3B82F6', 'default': '#6B7280'
                        };

                        // Fungsi membuat ikon berwarna
                        function createCustomIcon(color) {
                            return L.divIcon({
                                className: 'custom-leaflet-marker',
                                html: `<div style="background-color: ${color}; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>`,
                                iconSize: [16, 16], iconAnchor: [8, 8], popupAnchor: [0, -10]
                            });
                        }

                        // Tambahkan marker ke cluster group
                        kelompokData.forEach(kelompok => {
                            const lat = parseFloat(kelompok.latitude);
                            const lng = parseFloat(kelompok.longitude);
                            if (!isNaN(lat) && !isNaN(lng)) {
                                const markerColor = colorMap[kelompok.jenis] || colorMap['default'];
                                const icon = createCustomIcon(markerColor);
                                // Buat konten popup
                                let popupContent = `<div class="text-sm space-y-1 max-w-xs">`;
                                popupContent += `<div><b class="text-base">${kelompok.nama}</b></div>`;
                                popupContent += `<div>Jenis: <span class="font-medium text-gray-700 dark:text-gray-300">${kelompok.jenis}</span></div>`;
                                popupContent += `<div>Desa: <span class="font-medium text-gray-700 dark:text-gray-300">${kelompok.desa}</span></div>`;
                                popupContent += `<div>Petugas: <span class="font-medium text-gray-700 dark:text-gray-300">${kelompok.petugas}</span></div>`;
                                popupContent += `<div class="mt-2"><a href="${kelompok.detail_url}" target="_blank" class="text-primary-600 hover:underline text-xs font-medium">Lihat Detail Kelompok</a></div>`;
                                popupContent += `</div>`;

                                const marker = L.marker([lat, lng], { icon: icon, title: kelompok.nama })
                                    .bindPopup(popupContent);
                                markers.addLayer(marker); // Tambahkan ke cluster
                            }
                        });

                        mapPageInstance.addLayer(markers); // Tambahkan cluster group ke peta

                        // Fit bounds (opsional dengan cluster)
                        // if (markers.getLayers().length > 0) { mapPageInstance.fitBounds(markers.getBounds().pad(0.15)); }

                        // Invalidate size
                        setTimeout(() => { if (mapPageInstance) mapPageInstance.invalidateSize(); }, 150);
                        console.log('Leaflet map initialized on page.');

                    } catch (error) {
                        console.error("Error initializing Leaflet map:", error);
                        showMapMessage('Terjadi kesalahan saat memuat peta.');
                    }
                } // Akhir initializeKelompokPageMap

                // --- Logika Pemuatan Script (Hanya Leaflet & MarkerCluster) ---
                if (typeof L === 'undefined') {
                    const leafletScript = document.createElement('script');
                    leafletScript.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                    leafletScript.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
                    leafletScript.crossOrigin = '';
                    document.head.appendChild(leafletScript);
                    leafletScript.onload = loadMarkerClusterForPage; // Panggil loader cluster
                } else {
                    loadMarkerClusterForPage(); // Langsung panggil jika leaflet ada
                }

                function loadMarkerClusterForPage() {
                    if (typeof L.markerClusterGroup === 'undefined') {
                        const markerClusterScript = document.createElement('script');
                        markerClusterScript.src = 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js';
                        document.head.appendChild(markerClusterScript);
                        markerClusterScript.onload = initializeKelompokPageMap; // Panggil init map
                    } else {
                        // Panggil init map jika DOM sudah siap
                         if (document.readyState === 'loading') {
                            document.addEventListener('DOMContentLoaded', initializeKelompokPageMap);
                        } else {
                            initializeKelompokPageMap();
                        }
                    }
                }
                // --------------------------------------

            })(); // Akhir IIFE
        </script>
    @endpush
</div>
