<div>
    {{-- Section Filter --}}
    <x-filament::section collapsible collapsed> {{-- Buat collapsible dan default tertutup --}}
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-funnel class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                <span>Filter Data Analitik</span>
            </div>
        </x-slot>

        {{-- Form Filter dengan Elemen HTML Biasa --}}
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4 p-4"> {{-- Ubah grid jadi 4 kolom --}}
            {{-- Filter Tanggal Mulai --}}
            <div>
                <label for="startDate" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Tanggal
                    Mulai</label>
                <input wire:model.live.debounce.500ms="startDate" type="date" id="startDate"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
            </div>

            {{-- Filter Tanggal Akhir --}}
            <div>
                <label for="endDate" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Tanggal
                    Akhir</label>
                <input wire:model.live.debounce.500ms="endDate" type="date" id="endDate" min="{{ $startDate }}"
                    {{-- Atur min date --}}
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
            </div>

            {{-- Filter Kecamatan --}}
            <div>
                <label for="filterKecamatan"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Kecamatan</label>
                <select wire:model.live="filterKecamatan" id="filterKecamatan"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                    <option value="">-- Semua Kecamatan --</option>
                    {{-- Pastikan $kecamatans dikirim dari render() --}}
                    @if (isset($kecamatans))
                        @foreach ($kecamatans as $id => $nama)
                            <option value="{{ $id }}">{{ $nama }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            {{-- Filter Desa (Dinamis) --}}
            <div>
                <label for="filterDesa"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Desa/Kelurahan</label>
                <select wire:model.live="filterDesa" id="filterDesa"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                    @if (!isset($desas) || $desas->isEmpty()) disabled @endif> {{-- Disable jika tidak ada kecamatan dipilih atau tidak ada desa --}}
                    <option value="">-- Semua Desa --</option>
                    {{-- Opsi desa diisi dari $desas --}}
                    @if (isset($desas))
                        @foreach ($desas as $id => $nama)
                            <option value="{{ $id }}">{{ $nama }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            {{-- Filter Bantuan Spesifik (Ambil 2 kolom agar lebih lebar) --}}
            <div class="md:col-span-2">
                <label for="filterBantuan" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Jenis
                    Bantuan Spesifik</label>
                <select wire:model.live="filterBantuan" id="filterBantuan"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                    <option value="">-- Semua Bantuan --</option>
                    {{-- Opsi bantuan diisi dari $bantuans --}}
                    @if (isset($bantuans))
                        @foreach ($bantuans as $id => $nama)
                            <option value="{{ $id }}">{{ $nama }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            {{-- Filter Search (Ambil 2 kolom agar sejajar) --}}
            <div class="md:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Cari
                    (Kelompok)</label> {{-- Label diperjelas --}}
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <x-heroicon-m-magnifying-glass class="h-5 w-5 text-gray-400 dark:text-gray-500" />
                    </div>
                    <input wire:model.live.debounce.500ms="search" type="search" id="search"
                        placeholder="Nama Kelompok..."
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 sm:text-sm ps-10">
                </div>
            </div>

        </div>
        {{-- Akhir Form Filter --}}
    </x-filament::section>

    {{-- Section Overview Stats --}}
    {{-- Section Overview Stats --}}
    {{-- Pastikan grid ini langsung di dalam div utama komponen Livewire atau section halaman --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mt-6 mb-6 px-4 md:px-6 lg:px-8">

        {{-- Stat Total Kelompok Terdampak --}}
        {{-- Gunakan div biasa sebagai card, style manual --}}
        <div class="filament-stats-card p-4 md:p-6 rounded-xl bg-primary-50 dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col justify-between"> {{-- Tambah flex flex-col --}}
            <div> {{-- Konten utama --}}
                <div class="flex items-center gap-x-4">
                    <div class="flex-shrink-0 rounded-lg p-3 bg-primary-100 dark:bg-primary-500/20"> {{-- Warna BG ikon lebih solid --}}
                        <x-heroicon-o-user-group class="h-6 w-6 text-primary-600 dark:text-primary-400"/>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                            Kelompok Terdampak (Filter)
                        </p>
                        <p class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                            {{ number_format($overviewData['total_kelompok'] ?? 0, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="mt-4 pt-2 border-t border-gray-200 dark:border-white/10"> {{-- Footer dengan border atas --}}
                 <x-filament::link
                    :href="\App\Filament\Resources\KelompokResource::getUrl('index')"
                    tag="a"
                    icon="heroicon-m-arrow-top-right-on-square"
                    icon-position="after"
                    class="text-sm font-medium text-prima{{--  --}}dark:text-primary-400 dark:hover:text-primary-300" {{-- Warna link eksplisit --}}
                 >
                    Lihat Semua Kelompok
                </x-filament::link>
            </div>
        </div>

        {{-- Stat Total Nilai Bantuan --}}
        <div class="filament-stats-card p-4 md:p-6 rounded-xl bg-warning-50 dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-x-4">
                    <div class="flex-shrink-0 rounded-lg p-3 bg-warning-100 dark:bg-warning-500/20">
                        <x-heroicon-o-gift class="h-6 w-6 text-warning-600 dark:text-warning-400"/>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                            Total Nilai Bantuan (Filter)
                        </p>
                        <p class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                            Rp {{ number_format($overviewData['total_nilai_bantuan'] ?? 0, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>
             <div class="mt-4 pt-2 border-t border-gray-200 dark:border-white/10">
                 <x-filament::link
                    :href="\App\Filament\Resources\DistribusiBantuanResource::getUrl('index')"
                    tag="a"
                    icon="heroicon-m-arrow-top-right-on-square"
                    icon-position="after"
                    class="text-sm font-medium text-warning-600 hover:text-warning-700 dark:text-warning-400 dark:hover:text-warning-300"
                 >
                    Lihat Distribusi
                </x-filament::link>
            </div>
        </div>

        {{-- Stat Total Penjualan --}}
        <div class="filament-stats-card p-4 md:p-6 rounded-xl bg-success-50 dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-x-4">
                    <div class="flex-shrink-0 rounded-lg p-3 bg-success-100 dark:bg-success-500/20">
                        <x-heroicon-o-currency-dollar class="h-6 w-6 text-success-600 dark:text-success-400"/>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                            Total Penjualan (Filter)
                        </p>
                        <p class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                             Rp {{ number_format($overviewData['total_penjualan'] ?? 0, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>
             <div class="mt-4 pt-2 border-t border-gray-200 dark:border-white/10">
                 <x-filament::link
                    {{-- href="#" --}}
                    tag="a"
                    icon="heroicon-m-arrow-top-right-on-square"
                    icon-position="after"
                    class="text-sm font-medium text-success-600 hover:text-success-700 dark:text-success-400 dark:hover:text-success-300"
                 >
                    Lihat Detail Penjualan
                </x-filament::link>
            </div>
        </div>

        {{-- Stat Rata-rata Perkembangan --}}
        <div class="filament-stats-card p-4 md:p-6 rounded-xl bg-info-50 dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-x-4">
                    <div class="flex-shrink-0 rounded-lg p-3 bg-info-100 dark:bg-info-500/20">
                        <x-heroicon-s-arrow-trending-up class="h-6 w-6 text-info-600 dark:text-info-400"/>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                            Avg. Perkembangan (Filter)
                        </p>
                        <p class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                            {{ $overviewData['avg_perkembangan'] !== null ? number_format($overviewData['avg_perkembangan'], 1, ',', '.') . '%' : 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>
             <div class="mt-4 pt-2 border-t border-gray-200 dark:border-white/10">
                 <x-filament::link
                    :href="\App\Filament\Resources\MonitoringPemanfaatanResource::getUrl('index')"
                    tag="a"
                    icon="heroicon-m-arrow-top-right-on-square"
                    icon-position="after"
                    class="text-sm font-medium text-info-600 hover:text-info-700 dark:text-info-400 dark:hover:text-info-300"
                 >
                    Lihat Monitoring
                </x-filament::link>
            </div>
        </div>

    </div> {{-- Akhir Section Overview Stats --}}

    {{-- Grid untuk menata chart --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">

        {{-- Chart Tren Penjualan (Kolom 1) --}}
        <x-filament::section>
            <x-slot name="heading">
                Tren Total Penjualan Bulanan
                @if ($startDate && $endDate && Carbon\Carbon::parse($startDate)->year === Carbon\Carbon::parse($endDate)->year)
                    ({{ Carbon\Carbon::parse($startDate)->year }})
                @elseif($startDate && $endDate)
                    ({{ Carbon\Carbon::parse($startDate)->format('M Y') }} -
                    {{ Carbon\Carbon::parse($endDate)->format('M Y') }})
                @endif
                <div wire:loading wire:target="loadSalesChartData, loadAllChartData" class="ml-2">
                    {{-- Target ke kedua method --}}
                    <x-filament::loading-indicator class="h-5 w-5 text-primary-500" />
                </div>
            </x-slot>
            <div wire:ignore class="h-96">
                <canvas id="monthlySalesChart"></canvas>
            </div>
        </x-filament::section>

        {{-- Chart Komposisi Bantuan (Kolom 2) --}}
        <x-filament::section>
            <x-slot name="heading">
                Komposisi Nilai Bantuan per Jenis
                <div wire:loading wire:target="loadAidCompositionChartData, loadAllChartData" class="ml-2">
                    <x-filament::loading-indicator class="h-5 w-5 text-primary-500" />
                </div>
            </x-slot>
            <div wire:ignore class="h-96">
                <canvas id="aidCompositionChart"></canvas> {{-- ID Baru --}}
            </div>
        </x-filament::section>

        {{-- Chart Tren Monitoring (Lebar Penuh) --}}
        <x-filament::section>
            <x-slot name="heading">
                Tren Rata-rata Perkembangan Monitoring Bulanan (%)
                <div wire:loading wire:target="loadMonitoringTrendChartData, loadAllChartData" class="ml-2">
                    <x-filament::loading-indicator class="h-5 w-5 text-primary-500" />
                </div>
            </x-slot>
            <div wire:ignore class="h-96">
                <canvas id="monitoringTrendChart"></canvas> {{-- ID Baru --}}
            </div>
        </x-filament::section>

        {{-- Chart Performa Kelompok (Lebar Penuh) --}}
        <x-filament::section>
            <x-slot name="heading">
                Performa Penjualan Kelompok (Top 10)
                <div wire:loading wire:target="loadGroupPerformanceChartData, loadAllChartData" class="ml-2">
                    <x-filament::loading-indicator class="h-5 w-5 text-primary-500" />
                </div>
            </x-slot>
            <div wire:ignore class="h-96">
                <canvas id="groupPerformanceChart"></canvas> {{-- ID Baru --}}
            </div>
        </x-filament::section>

    </div> {{-- Akhir Grid Chart --}}

    {{-- Script untuk Inisialisasi dan Update Chart.js --}}
    @push('scripts')
        {{-- Pastikan Chart.js sudah ter-load --}}
        <script src="https://cdn.jsdelivr.net/npm/chart.js@^4"></script>
        <script>
            // Pastikan script hanya dijalankan sekali per page load penuh
            if (typeof window.analyticsChartsInitialized === 'undefined') {
                window.analyticsChartsInitialized = true;

                window.salesChartInstance = null;
                window.aidChartInstance = null;
                window.groupChartInstance = null;
                window.monitoringChartInstance = null; // <-- Variabel baru

                const salesChartCtx = document.getElementById('monthlySalesChart');
                const aidChartCtx = document.getElementById('aidCompositionChart');
                const groupChartCtx = document.getElementById('groupPerformanceChart');
                const monitoringChartCtx = document.getElementById('monitoringTrendChart'); // <-- Canvas baru

                // --- Fungsi Helper Format ---
                const formatCurrency = (value) => {
                    if (value === null || typeof value === 'undefined') return 'Rp 0';
                    if (value >= 1000000) {
                        return 'Rp ' + (value / 1000000).toLocaleString('id-ID', {
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 1
                        }) + ' Jt';
                    }
                    if (value >= 1000) {
                        return 'Rp ' + (value / 1000).toLocaleString('id-ID', {
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 0
                        }) + ' Rb';
                    }
                    return 'Rp ' + new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    }).format(value);
                };
                const formatTooltipLabel = (context) => {
                    let label = context.dataset.label || '';
                    if (label) {
                        label += ': ';
                    }
                    let val = context.parsed.y ?? context.parsed.x;
                    if (val !== null && !isNaN(val)) {
                        label += 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
                    }
                    return label;
                };
                const formatPercent = (value) => {
                    if (value === null || typeof value === 'undefined') return '0%';
                    return new Intl.NumberFormat('id-ID', {
                        maximumFractionDigits: 1
                    }).format(value) + '%';
                };
                const formatTooltipPercent = (context) => {
                    let label = context.dataset.label || '';
                    if (label) {
                        label += ': ';
                    }
                    if (context.parsed.y !== null && !isNaN(context.parsed.y)) {
                        label += new Intl.NumberFormat('id-ID', {
                            maximumFractionDigits: 1
                        }).format(context.parsed.y) + '%';
                    }
                    return label;
                };
                // ---------------------------

                // --- Opsi Chart ---
                const defaultChartOptions = {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: formatCurrency
                            }
                        },
                        x: {
                            ticks: {
                                autoSkip: true,
                                maxRotation: 0
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: formatTooltipLabel
                            }
                        },
                        legend: {
                            display: true
                        }
                    }
                };
                const aidChartOptions = {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                callback: formatCurrency
                            }
                        },
                        y: {
                            ticks: {
                                autoSkip: false
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: formatTooltipLabel
                            }
                        },
                        legend: {
                            display: false
                        }
                    }
                };
                const groupChartOptions = {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: formatCurrency
                            }
                        },
                        x: {
                            ticks: {
                                autoSkip: false,
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: formatTooltipLabel
                            }
                        },
                        legend: {
                            display: false
                        }
                    }
                };
                const monitoringChartOptions = {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: formatPercent
                            }
                        },
                        x: {
                            ticks: {
                                autoSkip: true,
                                maxRotation: 0
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: formatTooltipPercent
                            }
                        },
                        legend: {
                            display: true
                        }
                    }
                };
                // ------------------

                // --- Fungsi Update/Create Chart ---
                const updateOrCreateChart = (ctx, instanceVar, chartId, chartType, chartData, chartOptions) => {
                    if (!ctx) {
                        console.warn(`Canvas element with ID '${chartId}' not found.`);
                        return;
                    }
                    const dataToUse = (chartData && chartData.labels && chartData.datasets) ? chartData : {
                        labels: [],
                        datasets: []
                    };
                    if (window[instanceVar]) {
                        window[instanceVar].data.labels = dataToUse.labels;
                        window[instanceVar].data.datasets = dataToUse.datasets;
                        window[instanceVar].options = chartOptions;
                        window[instanceVar].update();
                    } else {
                        window[instanceVar] = new Chart(ctx, {
                            type: chartType,
                            data: dataToUse,
                            options: chartOptions
                        });
                    }
                };
                // ---------------------------------

                // --- Fungsi Update Semua Chart ---
                const updateAllCharts = (eventData) => {
                    console.log('Received updateCharts event:', eventData);
                    if (eventData && typeof eventData === 'object') {
                        updateOrCreateChart(salesChartCtx, 'salesChartInstance', 'monthlySalesChart', 'line', eventData
                            .sales, defaultChartOptions);
                        updateOrCreateChart(aidChartCtx, 'aidChartInstance', 'aidCompositionChart', 'bar', eventData.aid,
                            aidChartOptions);
                        updateOrCreateChart(groupChartCtx, 'groupChartInstance', 'groupPerformanceChart', 'bar', eventData
                            .group, groupChartOptions);
                        updateOrCreateChart(monitoringChartCtx, 'monitoringChartInstance', 'monitoringTrendChart', 'line',
                            eventData.monitoring, monitoringChartOptions); // <-- Panggil untuk chart baru
                    } else {
                        console.error('Invalid or missing data in updateCharts event.');
                    }
                };
                // -----------------------------

                // --- Listener Livewire ---
                document.addEventListener('livewire:init', () => {
                    console.log('Livewire init, initializing charts...');
                    // Panggil updateAllCharts dengan data awal dari Livewire
                    updateAllCharts({
                        sales: @json($this->salesChartData),
                        aid: @json($this->aidCompositionChartData),
                        group: @json($this->groupPerformanceChartData),
                        monitoring: @json($this->monitoringTrendChartData) // <-- Tambah data monitoring
                    });

                    // Listener untuk event gabungan 'updateCharts'
                    Livewire.on('updateCharts', event => {
                        // Livewire v3 event data ada di event[0]
                        updateAllCharts(event[0]);
                    });
                });

                // Re-init saat navigasi
                document.addEventListener('livewire:navigated', () => {
                    console.log('Livewire navigated, destroying and reloading charts...');
                    if (window.salesChartInstance) {
                        window.salesChartInstance.destroy();
                        window.salesChartInstance = null;
                    }
                    if (window.aidChartInstance) {
                        window.aidChartInstance.destroy();
                        window.aidChartInstance = null;
                    }
                    if (window.groupChartInstance) {
                        window.groupChartInstance.destroy();
                        window.groupChartInstance = null;
                    }
                    if (window.monitoringChartInstance) {
                        window.monitoringChartInstance.destroy();
                        window.monitoringChartInstance = null;
                    } // <-- Destroy chart baru
                    // Panggil method load utama di backend untuk memicu event updateCharts
                    @this.call('prepareChartData');
                }, {
                    once: false
                });
                // -----------------------

            } // Akhir cek window.analyticsChartsInitialized
        </script>
    @endpush

</div>
