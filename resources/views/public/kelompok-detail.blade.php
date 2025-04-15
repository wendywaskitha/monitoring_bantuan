{{-- resources/views/public/kelompok-detail.blade.php --}}
@extends('layouts.app') {{-- Pastikan menggunakan layout utama frontend Anda --}}

{{-- Ambil nama kelompok untuk judul --}}
@section('title', $kelompok->nama_kelompok . ' - Detail Kelompok Binaan')

@push('styles')
    {{-- CSS Leaflet --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    {{-- CSS Lightbox2 (jika pakai) --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet"
        integrity="sha512-ZKX+BvQihRJPA8CROKBhDNvoc2aDMOdAlcm7TUQY+35XYtrd3yh95QOOhsPDQY9KLHZGJmLGYS_3Te4jpS7Lvg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    {{-- Bootstrap Icons (jika belum ada di layout utama) --}}
    {{-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> --}}
    <style>
        #detailMap {
            height: 300px;
            border-radius: 0.5rem;
        }

        .profile-img {
            max-height: 400px;
            width: 100%;
            object-fit: cover;
            border-radius: 0.5rem;
        }

        .gallery-img {
            height: 100px;
            width: 100%;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.2s ease;
            border-radius: 0.375rem;
        }

        /* Ukuran galeri disesuaikan */
        .gallery-img:hover {
            transform: scale(1.05);
        }

        /* Styling Kartu KPI */
        .kpi-card {
            border-radius: 0.5rem;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            background-color: #fff;
        }

        .dark .kpi-card {
            background-color: #212529;
        }

        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
        }

        .kpi-card .card-body {
            padding: 1rem;
        }

        .kpi-card .kpi-value {
            font-size: 1.5rem;
            /* Ukuran disesuaikan */
            font-weight: 600;
            line-height: 1.3;
            margin-bottom: 0.25rem;
        }

        .kpi-card .kpi-label {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 0;
        }

        .dark .kpi-card .kpi-label {
            color: #adb5bd;
        }

        /* Warna border kiri KPI */
        .kpi-card.border-primary {
            border-left: 4px solid var(--bs-primary);
        }

        .kpi-card.border-success {
            border-left: 4px solid var(--bs-success);
        }

        .kpi-card.border-warning {
            border-left: 4px solid var(--bs-warning);
        }

        .kpi-card.border-info {
            border-left: 4px solid var(--bs-info);
        }

        /* Warna teks nilai KPI */
        .kpi-value.text-primary {
            color: var(--bs-primary) !important;
        }

        .kpi-value.text-success {
            color: var(--bs-success) !important;
        }

        .kpi-value.text-warning {
            color: var(--bs-warning) !important;
        }

        .kpi-value.text-info {
            color: var(--bs-info) !important;
        }

        /* Styling List Group */
        .list-group-item {
            background-color: transparent;
            border-left: 0;
            border-right: 0;
            padding-left: 0;
            padding-right: 0;
        }

        .dark .list-group-item {
            border-color: rgba(255, 255, 255, 0.1);
        }

        /* Styling Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        /* Tinggi chart disesuaikan */
    </style>
@endpush

@section('content')
    <div class="container py-5 px-4 px-lg-5">
        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Beranda</a></li>
                <li class="breadcrumb-item"><a href="{{ route('peta.potensi') }}">Peta Potensi</a></li>
                <li class="breadcrumb-item"><a href="{{ route('kelompok.list') }}">Daftar Kelompok</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $kelompok->nama_kelompok }}</li>
            </ol>
        </nav>

        {{-- Header Utama --}}
        <div class="row align-items-center mb-4 pb-3 border-bottom">
            <div class="col-md-8">
                @php
                    $badgeColor = match ($kelompok->jenis_kelompok) {
                        'Pertanian' => 'success',
                        'Peternakan' => 'warning',
                        'Perikanan' => 'info',
                        default => 'secondary',
                    };
                @endphp
                <span class="badge bg-{{ $badgeColor }} me-2 fs-6 mb-2">{{ $kelompok->jenis_kelompok }}</span>
                <h1 class="display-5 fw-bold mb-1">{{ $kelompok->nama_kelompok }}</h1>
                <p class="text-muted fs-5 mb-2">
                    <i class="bi bi-geo-alt-fill me-1 text-secondary"></i> {{ $kelompok->desa?->nama_desa ?? 'N/A' }}, Kec.
                    {{ $kelompok->desa?->kecamatan?->nama_kecamatan ?? 'N/A' }}
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <!-- Data-toggle diganti menjadi data-bs-toggle untuk Bootstrap 5 -->
                <a href="#" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#kemitraanModal">
                    <i class="bi bi-telephone-fill me-1"></i> Ajukan Kemitraan
                </a>
            </div>
        </div>

        <!-- Modal Ajukan Kemitraan -->
        <div class="modal fade" id="kemitraanModal" tabindex="-1" aria-labelledby="kemitraanModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="kemitraanModalLabel">Ajukan Kemitraan dengan
                            {{ $kelompok->nama_kelompok }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <form action="{{ route('kemitraan.store') }}" method="POST" enctype="multipart/form-data"
                        class="needs-validation" novalidate>
                        @csrf
                        <div class="modal-body">
                            <!-- Nama Lengkap -->
                            <div class="mb-3">
                                <label for="nama_kontak" class="form-label">Nama Lengkap <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nama_kontak') is-invalid @enderror"
                                    id="nama_kontak" name="nama_kontak" value="{{ old('nama_kontak') }}" required>
                                @error('nama_kontak')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div class="mb-3">
                                <label for="email_kontak" class="form-label">Alamat Email <span
                                        class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email_kontak') is-invalid @enderror"
                                    id="email_kontak" name="email_kontak" value="{{ old('email_kontak') }}" required>
                                @error('email_kontak')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Nomor Telepon -->
                            <div class="mb-3">
                                <label for="telepon_kontak" class="form-label">Nomor Telepon Aktif <span
                                        class="text-danger">*</span></label>
                                <input type="tel" class="form-control @error('telepon_kontak') is-invalid @enderror"
                                    id="telepon_kontak" name="telepon_kontak" value="{{ old('telepon_kontak') }}" required>
                                @error('telepon_kontak')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Pesan -->
                            <div class="mb-3">
                                <label for="pesan" class="form-label">Pesan Anda (Opsional)</label>
                                <textarea class="form-control @error('pesan') is-invalid @enderror" id="pesan" name="pesan" rows="4"
                                    placeholder="Jelaskan maksud kemitraan Anda...">{{ old('pesan') }}</textarea>
                                @error('pesan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Hidden Input: Kelompok ID -->
                            <input type="hidden" name="kelompok_id" value="{{ $kelompok->id }}">

                            <!-- CAPTCHA (Opsional) -->
                            {{-- {!! NoCaptcha::display(['data-theme' => 'light']) !!} --}}
                            {{-- @error('g-recaptcha-response') <div class="text-danger small mt-1">{{ $message }}</div> @enderror --}}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Kirim Permintaan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Konten Detail (Layout 2 Kolom) --}}
        <div class="row gx-lg-5">

            {{-- Kolom Kiri: Profil, Peta, Anggota --}}
            <div class="col-lg-7 order-lg-1"> {{-- order-lg-1: Kolom kiri di desktop --}}

                {{-- Peta Lokasi --}}
                @if ($kelompok->latitude && $kelompok->longitude)
                    <div class="mb-4">
                        <h5 class="fw-semibold mb-2">Lokasi Kelompok</h5>
                        <div class="rounded overflow-hidden shadow-sm border">
                            <div id="detailMap"></div>
                        </div>
                    </div>
                @endif

                {{-- Informasi Detail Kelompok --}}
                <div class="mb-4">
                    <h5 class="fw-semibold mb-3">Informasi Kelompok</h5>
                    <dl class="row gy-2">
                        <dt class="col-sm-4">Ketua Kelompok</dt>
                        <dd class="col-sm-8">{{ $kelompok->ketua?->nama_anggota ?? 'Belum Ditentukan' }}</dd>
                        <dt class="col-sm-4">Jumlah Anggota</dt>
                        <dd class="col-sm-8">{{ $kelompok->anggotas_count ?? $kelompok->anggotas->count() }} Orang</dd>
                        {{-- Gunakan count jika di-load --}}
                        <dt class="col-sm-4">Tanggal Dibentuk</dt>
                        <dd class="col-sm-8">
                            {{ $kelompok->tanggal_dibentuk ? $kelompok->tanggal_dibentuk->format('d F Y') : '-' }}</dd>
                        <dt class="col-sm-4">Alamat Sekretariat</dt>
                        <dd class="col-sm-8">{{ $kelompok->alamat_sekretariat ?? '-' }}</dd>
                        <dt class="col-sm-4">Petugas Pendamping</dt>
                        <dd class="col-sm-8">{{ $kelompok->petugas?->name ?? 'Belum Ditugaskan' }}</dd>
                    </dl>
                </div>

                {{-- Daftar Anggota (Accordion) --}}
                <div class="accordion mb-4" id="accordionAnggota">
                    <div class="accordion-item border rounded"> {{-- Tambah border & rounded --}}
                        <h2 class="accordion-header" id="headingAnggota">
                            <button class="accordion-button collapsed fw-semibold" type="button"
                                data-bs-toggle="collapse" data-bs-target="#collapseAnggota" aria-expanded="false"
                                aria-controls="collapseAnggota">
                                Daftar Anggota ({{ $kelompok->anggotas->count() }})
                            </button>
                        </h2>
                        <div id="collapseAnggota" class="accordion-collapse collapse" aria-labelledby="headingAnggota">
                            {{-- Hapus data-bs-parent --}}
                            <div class="accordion-body p-0">
                                <ul class="list-group list-group-flush">
                                    @forelse ($kelompok->anggotas as $anggota)
                                        {{-- Tampilkan semua --}}
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                            <span><i
                                                    class="bi bi-person me-2 text-muted"></i>{{ $anggota->nama_anggota }}</span>
                                            @if ($anggota->id === $kelompok->ketua_id)
                                                <span class="badge bg-primary rounded-pill fw-normal">Ketua</span>
                                            @endif
                                        </li>
                                    @empty
                                        <li class="list-group-item py-2 text-muted fst-italic">Belum ada data anggota.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Kolom Kanan: Kinerja, Bantuan, Chart, Galeri, Kebutuhan --}}
            <div class="col-lg-5 order-lg-2"> {{-- order-lg-2: Kolom kanan di desktop --}}

                {{-- Ringkasan Kinerja (KPI Cards) --}}
                <h5 class="fw-semibold mb-3">Ringkasan Kinerja</h5>
                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div class="card kpi-card p-3 h-100 border-start border-primary border-4">
                            <div class="kpi-value text-primary">Rp
                                {{ number_format($totalNilaiBantuan ?? 0, 0, ',', '.') }}</div>
                            <div class="kpi-label">Total Estimasi Bantuan</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card kpi-card p-3 h-100 border-start border-success border-4">
                            <div class="kpi-value text-success">Rp {{ number_format($totalPenjualan ?? 0, 0, ',', '.') }}
                            </div>
                            <div class="kpi-label">Total Penjualan Dilaporkan</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card kpi-card p-3 h-100 border-start border-warning border-4">
                            <div class="kpi-value text-warning">
                                {{ $avgPerkembangan !== null ? number_format($avgPerkembangan, 1, ',', '.') . '%' : 'N/A' }}
                            </div>
                            <div class="kpi-label">Avg. Perkembangan</div>
                        </div>
                    </div>
                    {{-- KPI Stok Terakhir --}}
                    <div class="col-6">
                        @php
                            $stokLabel = 'Stok Utama';
                            $stokValue = 'N/A';
                        @endphp
                        @if ($kelompok->jenis_kelompok === 'Peternakan' && $estimasiTernak !== null)
                            @php
                                $stokLabel = 'Estimasi Ternak';
                                $stokValue = $estimasiTernak . ' Ekor';
                            @endphp
                        @elseif($kelompok->jenis_kelompok === 'Perikanan' && $estimasiIkan !== null)
                            @php
                                $stokLabel = 'Estimasi Ikan';
                                $stokValue = $estimasiIkan . ' Ekor';
                            @endphp
                        @elseif($kelompok->jenis_kelompok === 'Pertanian' && $stokBibit !== null)
                            @php
                                $stokLabel = 'Stok Bibit Simpan';
                                $stokValue = $stokBibit . ' ' . ($satuanStokBibit ?? '');
                            @endphp
                        @endif
                        <div class="card kpi-card p-3 h-100 border-start border-info border-4">
                            <div class="kpi-value text-info">{{ $stokValue }}</div>
                            <div class="kpi-label">{{ $stokLabel }} (Terakhir)</div>
                        </div>
                    </div>
                </div>

                {{-- Riwayat Bantuan Diterima --}}
                <div class="mb-4">
                    <h5 class="fw-semibold mb-2">Riwayat Bantuan Diterima</h5>
                    <ul class="list-group list-group-flush">
                        @forelse ($kelompok->distribusiBantuans as $distribusi)
                            <li
                                class="list-group-item text-sm d-flex justify-content-between align-items-center ps-0 py-2">
                                <span>
                                    <span class="fw-medium">{{ $distribusi->bantuan->nama_bantuan }}</span>
                                    <small class="text-muted">({{ number_format($distribusi->jumlah, 0, ',', '.') }}
                                        {{ $distribusi->satuan }})</small>
                                </span>
                                <small class="text-muted">{{ $distribusi->tanggal_distribusi->format('d/m/Y') }}</small>
                            </li>
                        @empty
                            <li class="list-group-item ps-0 py-2 text-muted text-sm fst-italic">Belum ada riwayat bantuan
                                diterima.</li>
                        @endforelse
                    </ul>
                </div>

                {{-- Card Chart Tren Penjualan --}}
                <div class="card shadow-sm border-light-subtle mb-4">
                    <div class="card-header bg-light fw-semibold">
                        <i class="bi bi-bar-chart-line-fill me-1"></i> Tren Penjualan Bulanan
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="salesTrendChart"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Galeri Foto --}}
                <div class="mb-4">
                    <h5 class="fw-semibold mb-3">Galeri Kegiatan</h5>
                    @if (isset($galeriFoto) && $galeriFoto->isNotEmpty())
                        <div class="row g-2">
                            @foreach ($galeriFoto as $fotoPath)
                                <div class="col-4 col-md-3"> {{-- Kolom lebih banyak untuk galeri --}}
                                    <a href="{{ Storage::disk('public')->url($fotoPath) }}"
                                        data-lightbox="gallery-{{ $kelompok->id }}"
                                        data-title="{{ $kelompok->nama_kelompok }} - Foto Kegiatan {{ $loop->iteration }}">
                                        <img src="{{ Storage::disk('public')->url($fotoPath) }}"
                                            alt="Foto Kegiatan {{ $kelompok->nama_kelompok }}"
                                            class="img-fluid gallery-img shadow-sm img-thumbnail">
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted text-sm fst-italic">Belum ada foto kegiatan.</p>
                    @endif
                </div>

                {{-- Kebutuhan Pengembangan/Kemitraan --}}
                <div class="card shadow-sm border-light-subtle mb-4" id="kemitraan">
                    <div class="card-header bg-primary text-white fw-semibold">
                        <i class="bi bi-lightbulb-fill me-1"></i> Peluang Kemitraan & Kebutuhan
                    </div>
                    <div class="card-body">
                        <p class="text-body-secondary small mb-0 fst-italic">
                            {{ $kebutuhan ?? 'Kelompok ini terbuka untuk diskusi mengenai peluang kemitraan dan pengembangan lebih lanjut. Hubungi kami untuk informasi detail.' }}
                        </p>
                    </div>
                </div>

                {{-- Tombol Kontak/Kemitraan --}}
                <div class="text-center mt-4 d-grid">
                    <a href="#" class="btn btn-primary"><i class="bi bi-telephone-fill me-1"></i> Ajukan Kemitraan
                        / Hubungi Kami</a>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- JS Leaflet --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    {{-- JS Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@^4"></script>
    {{-- JS Lightbox2 (jika pakai) --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"
        integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"
        integrity="sha512-Ixzuzfxv1EqafeN+VZEKLzzBWGCgzv7KYz4IbNdLik9dqdVQBCVrtMM/WZNJPGegcY/JFNO/gwXtMp/74sDujg=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script>
        // Inisialisasi Peta Detail
        @if ($kelompok->latitude && $kelompok->longitude)
            const initDetailMap = () => {
                const mapDetailElement = document.getElementById('detailMap');
                if (!mapDetailElement || typeof L === 'undefined' || mapDetailElement._leaflet_id) return;
                try {
                    const lat = {{ $kelompok->latitude }};
                    const lng = {{ $kelompok->longitude }};
                    const mapDetail = L.map(mapDetailElement).setView([lat, lng], 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OSM'
                    }).addTo(mapDetail);
                    L.marker([lat, lng]).addTo(mapDetail).bindPopup('Lokasi {{ e($kelompok->nama_kelompok) }}')
                        .openPopup();
                    setTimeout(() => {
                        if (mapDetail) mapDetail.invalidateSize();
                    }, 150);
                } catch (e) {
                    console.error("Error initializing detail map:", e);
                }
            };
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initDetailMap);
            } else {
                initDetailMap();
            }
        @endif

        // --- Fungsi Format Angka Rupiah ---
        const formatCurrency = (value) => {
            if (value === null || typeof value !== 'number' || isNaN(value)) return 'Rp 0';
            // Gunakan NumberFormat dengan locale Indonesia ('id-ID')
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(value).replace(/(\.|,)00$/, ''); // Hapus .00 atau ,00 di akhir
        };

        // --- Fungsi Format Tooltip Label (untuk Chart.js) ---
        const formatTooltipLabel = (context) => {
            let label = context.dataset.label || ''; // Ambil label dataset jika ada
            if (label) {
                label += ': ';
            }
            const value = context.parsed.y ?? context.parsed.x; // Cek sumbu y atau x
            if (value === null || typeof value !== 'number' || isNaN(value)) return 'N/A';
            // Format angka sebagai Rupiah
            return label + formatCurrency(value);
        };

        // Inisialisasi Chart Tren Penjualan
        const initSalesTrendChart = () => {
            const salesChartCtx = document.getElementById('salesTrendChart');
            const salesChartData = @json($salesTrendData ?? ['labels' => [], 'datasets' => []]); // Ambil data dari Controller
            if (!salesChartCtx || !salesChartData || typeof Chart === 'undefined') {
                console.error("Sales chart elements not ready.");
                return;
            }
            if (window.detailSalesChartInstance) {
                window.detailSalesChartInstance.destroy();
            }

            const salesOptions = {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: formatCurrency
                        }, // Format nilai sumbu Y
                    },
                    x: {
                        ticks: {
                            autoSkip: true,
                            maxRotation: 0
                        }, // Sumbu X tanpa rotasi
                    },
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: formatTooltipLabel
                        }
                    }, // Custom tooltip
                    legend: {
                        display: false
                    }, // Sembunyikan legend jika hanya 1 dataset
                },
            };

            window.detailSalesChartInstance = new Chart(salesChartCtx, {
                type: 'line',
                data: salesChartData,
                options: salesOptions,
            });
            console.log('Sales trend chart initialized for detail page.');
        };
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initSalesTrendChart);
        } else {
            initSalesTrendChart();
        }

        // Inisialisasi Lightbox (jika pakai)
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lightbox !== 'undefined') {
                lightbox.option({
                    'resizeDuration': 200,
                    'wrapAround': true,
                    'fadeDuration': 300,
                    'imageFadeDuration': 300
                });
            }
        });
    </script>
@endpush
