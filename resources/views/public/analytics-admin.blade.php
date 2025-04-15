{{-- resources/views/public/analytics-admin.blade.php --}}
@extends('layouts.app') {{-- Pastikan menggunakan layout frontend Anda --}}

@section('title', 'Analitik Program Bantuan (Admin)')

@php
    // Deklarasi untuk membantu VS Code (nilai aktual tetap dari Controller)
    $sortColumn = $sortColumn ?? request('sort', 'nama_kelompok');
    $sortDirection = $sortDirection ?? request('direction', 'asc');
    $sortPetugasColumn = $sortPetugasColumn ?? 'name';
    $sortPetugasDirection = $sortPetugasDirection ?? 'asc';
    // Deklarasikan variabel lain dari compact jika perlu
    // $kelompoks = $kelompoks ?? collect();
    // $kinerjaPetugas = $kinerjaPetugas ?? collect();
@endphp

@push('styles')
    {{-- Bootstrap Icons (jika belum ada di layout utama) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    {{-- CSS Kustom untuk halaman ini --}}
    <style>
        /* Styling Kartu Overview */
        .overview-card {
            border: none;
            border-radius: 0.75rem;
            transition: all 0.2s ease-in-out;
            background-color: #fff;
            overflow: hidden;
            position: relative;
        }

        .overview-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        .overview-card .card-body {
            padding: 1.25rem;
            position: relative;
            z-index: 2;
        }

        .overview-card .icon-bg {
            position: absolute;
            top: -15px;
            right: -15px;
            font-size: 4rem;
            opacity: 0.06;
            transform: rotate(-15deg);
            z-index: 1;
        }

        .overview-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .overview-card .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0;
        }

        .dark .overview-card {
            background-color: #212529;
            border: 1px solid #343a40;
        }

        .dark .overview-card .stat-label {
            color: #adb5bd;
        }

        .dark .overview-card .stat-value {
            color: #f8f9fa;
        }

        .overview-card.card-kelompok .icon-bg,
        .overview-card.card-kelompok .stat-value {
            color: var(--bs-primary);
        }

        .overview-card.card-bantuan .icon-bg,
        .overview-card.card-bantuan .stat-value {
            color: var(--bs-warning);
        }

        .overview-card.card-penjualan .icon-bg,
        .overview-card.card-penjualan .stat-value {
            color: var(--bs-success);
        }

        .overview-card.card-perkembangan .icon-bg,
        .overview-card.card-perkembangan .stat-value {
            color: var(--bs-info);
        }

        /* Styling Chart Container */
        .chart-container {
            position: relative;
            height: 350px;
            width: 100%;
        }

        /* Styling Filter Card */
        .filter-card .card-body {
            padding: 1rem;
        }

        .filter-card .form-label {
            margin-bottom: 0.3rem;
            font-weight: 500;
        }

        /* Styling Tabel */
        .table th {
            white-space: nowrap;
        }

        .table td {
            vertical-align: middle;
            font-size: 0.9rem;
        }

        .table thead {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            z-index: 10;
        }

        .dark .table thead {
            background-color: #212529;
        }

        /* Dark mode header */
        .dark .table {
            color: #dee2e6;
        }

        /* Dark mode teks tabel */
        .dark .table-striped>tbody>tr:nth-of-type(odd)>* {
            background-color: rgba(255, 255, 255, .03);
        }

        /* Dark mode strip */
        .sortable-link {
            color: inherit;
            text-decoration: none;
        }

        .sortable-link:hover {
            color: var(--bs-primary);
        }

        .sort-icon {
            font-size: 0.8em;
            margin-left: 0.3em;
        }

        /* Styling Tab */
        .nav-tabs {
            border-bottom-color: #dee2e6;
        }

        /* Warna border bawah tab */
        .dark .nav-tabs {
            border-bottom-color: #495057;
        }

        .nav-tabs .nav-link {
            font-weight: 500;
            color: var(--bs-secondary);
            border: none;
            border-bottom: 2px solid transparent;
            padding: 0.75rem 1rem;
        }

        .nav-tabs .nav-link:hover {
            border-bottom-color: #e9ecef;
        }

        .dark .nav-tabs .nav-link:hover {
            border-bottom-color: #495057;
        }

        .nav-tabs .nav-link.active {
            color: var(--bs-primary);
            border-color: var(--bs-primary) var(--bs-primary) #fff;
            border-bottom-width: 2px;
            background-color: transparent;
        }

        .dark .nav-tabs .nav-link.active {
            border-color: var(--bs-primary) var(--bs-primary) #212529;
        }

        /* Warna border tab aktif dark */
        .tab-content>.tab-pane {
            display: none;
        }

        .tab-content>.active {
            display: block;
        }
    </style>
@endpush

@section('content')
    <div class="container py-5 px-4 px-lg-5">
        {{-- Judul Halaman --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2 fw-bold mb-0">Analitik Program Bantuan</h1>
            {{-- Tombol Aksi Tambahan (Opsional) --}}
            {{-- <a href="#" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download me-1"></i> Unduh Laporan</a> --}}
        </div>

        {{-- Section Filter --}}
        <div class="card shadow-sm mb-4 filter-card">
            <div class="card-body">
                <form action="{{ route('analytics.admin') }}" method="GET">
                    <div class="row g-3 align-items-end">
                        {{-- Filter Tanggal Mulai --}}
                        <div class="col-md-6 col-lg-2">
                            <label for="start_date" class="form-label form-label-sm">Tgl Mulai</label>
                            <input type="date" class="form-control form-control-sm" id="start_date" name="start_date"
                                value="{{ $startDate ?? '' }}">
                        </div>
                        {{-- Filter Tanggal Akhir --}}
                        <div class="col-md-6 col-lg-2">
                            <label for="end_date" class="form-label form-label-sm">Tgl Akhir</label>
                            <input type="date" class="form-control form-control-sm" id="end_date" name="end_date"
                                value="{{ $endDate ?? '' }}" min="{{ $startDate ?? '' }}">
                        </div>
                        {{-- Filter Kecamatan --}}
                        <div class="col-md-6 col-lg-2">
                            <label for="filterKecamatan" class="form-label form-label-sm">Kecamatan</label>
                            <select class="form-select form-select-sm" id="filterKecamatan" name="kecamatan">
                                <option value="">Semua</option>
                                @foreach ($kecamatans ?? [] as $id => $nama)
                                    <option value="{{ $id }}" {{ $filterKecamatan == $id ? 'selected' : '' }}>
                                        {{ $nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Filter Desa --}}
                        <div class="col-md-6 col-lg-2">
                            <label for="filterDesa" class="form-label form-label-sm">Desa</label>
                            <select class="form-select form-select-sm" id="filterDesa" name="desa"
                                @if (!isset($desas) || $desas->isEmpty()) disabled @endif>
                                <option value="">Semua</option>
                                @foreach ($desas ?? [] as $id => $nama)
                                    <option value="{{ $id }}" {{ $filterDesa == $id ? 'selected' : '' }}>
                                        {{ $nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Filter Jenis Kelompok --}}
                        <div class="col-md-6 col-lg-2">
                            <label for="filterJenis" class="form-label form-label-sm">Jenis Kelompok</label>
                            <select class="form-select form-select-sm" id="filterJenis" name="jenis">
                                <option value="">Semua</option>
                                @foreach ($jenisKelompokOptions ?? [] as $value => $label)
                                    <option value="{{ $value }}" {{ $filterJenis == $value ? 'selected' : '' }}>
                                        {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Filter Bantuan Spesifik --}}
                        <div class="col-md-6 col-lg-2">
                            <label for="filterBantuan" class="form-label form-label-sm">Bantuan</label>
                            <select class="form-select form-select-sm" id="filterBantuan" name="bantuan">
                                <option value="">Semua</option>
                                @foreach ($bantuans ?? [] as $id => $nama)
                                    <option value="{{ $id }}" {{ $filterBantuan == $id ? 'selected' : '' }}>
                                        {{ $nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Filter Petugas PJ --}}
                        <div class="col-md-6 col-lg-2">
                            <label for="filterPetugas" class="form-label form-label-sm">Petugas PJ</label>
                            <select class="form-select form-select-sm" id="filterPetugas" name="petugas">
                                <option value="">Semua</option>
                                @foreach ($petugasList ?? [] as $id => $nama)
                                    <option value="{{ $id }}" {{ $filterPetugas == $id ? 'selected' : '' }}>
                                        {{ $nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Search Kelompok --}}
                        <div class="col-md-6 col-lg-2">
                            <label for="search" class="form-label form-label-sm">Cari Kelompok</label>
                            <input type="text" class="form-control form-control-sm" id="search" name="search"
                                value="{{ request('search') }}" placeholder="Nama kelompok...">
                        </div>
                        {{-- Tombol --}}
                        <div class="col-md-12 col-lg-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm w-100 me-2">Terapkan</button>
                            <a href="{{ route('analytics.admin') }}" class="btn btn-outline-secondary btn-sm w-100"
                                title="Reset Filter">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Section Overview Stats --}}
        <div class="row g-4 mb-4">
            {{-- Card Total Kelompok --}}
            <div class="col-lg-3 col-md-6">
                <div class="card overview-card card-kelompok h-100 shadow-sm border">
                    <div class="card-body">
                        <i class="bi bi-people-fill icon-bg"></i>
                        <div class="stat-value">{{ number_format($overviewData['total_kelompok'] ?? 0, 0, ',', '.') }}
                        </div>
                        <p class="stat-label">Kelompok Terdampak (Filter)</p>
                    </div>
                </div>
            </div>
            {{-- Card Total Nilai Bantuan --}}
            <div class="col-lg-3 col-md-6">
                <div class="card overview-card card-bantuan h-100 shadow-sm border">
                    <div class="card-body">
                        <i class="bi bi-gift-fill icon-bg"></i>
                        <div class="stat-value">Rp
                            {{ number_format($overviewData['total_nilai_bantuan'] ?? 0, 0, ',', '.') }}</div>
                        <p class="stat-label">Total Nilai Bantuan (Filter)</p>
                    </div>
                </div>
            </div>
            {{-- Card Total Penjualan --}}
            <div class="col-lg-3 col-md-6">
                <div class="card overview-card card-penjualan h-100 shadow-sm border">
                    <div class="card-body">
                        <i class="bi bi-currency-dollar icon-bg"></i>
                        <div class="stat-value">Rp {{ number_format($overviewData['total_penjualan'] ?? 0, 0, ',', '.') }}
                        </div>
                        <p class="stat-label">Total Penjualan (Filter)</p>
                    </div>
                </div>
            </div>
            {{-- Card Avg. Perkembangan --}}
            <div class="col-lg-3 col-md-6">
                <div class="card overview-card card-perkembangan h-100 shadow-sm border">
                    <div class="card-body">
                        <i class="bi bi-graph-up icon-bg"></i>
                        <div class="stat-value">
                            {{ $overviewData['avg_perkembangan'] !== null ? number_format($overviewData['avg_perkembangan'], 1, ',', '.') . '%' : 'N/A' }}
                        </div>
                        <p class="stat-label">Avg. Perkembangan (Filter)</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Struktur Tab --}}
        <ul class="nav nav-tabs mb-4" id="analyticsTab" role="tablist">
            <li class="nav-item" role="presentation">
                {{-- Beri ID unik dan data-tab-id --}}
                <button class="nav-link {{ request('tab', 'visualisasi') == 'visualisasi' ? 'active' : '' }}"
                    id="visualisasi-tab" data-bs-toggle="tab" data-bs-target="#visualisasi-tab-pane" type="button"
                    role="tab" aria-controls="visualisasi-tab-pane"
                    aria-selected="{{ request('tab', 'visualisasi') == 'visualisasi' ? 'true' : 'false' }}"
                    data-tab-id="visualisasi"> {{-- Data ID untuk JS --}}
                    <i class="bi bi-bar-chart-line-fill me-1"></i> Visualisasi Data
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ request('tab') == 'resume' ? 'active' : '' }}" id="resume-tab"
                    data-bs-toggle="tab" data-bs-target="#resume-tab-pane" type="button" role="tab"
                    aria-controls="resume-tab-pane" aria-selected="{{ request('tab') == 'resume' ? 'true' : 'false' }}"
                    data-tab-id="resume">
                    <i class="bi bi-table me-1"></i> Resume per Kelompok
                </button>
            </li>
            @if (Auth::check() && Auth::user()->hasRole('Admin'))
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ request('tab') == 'petugas' ? 'active' : '' }}" id="petugas-tab"
                        data-bs-toggle="tab" data-bs-target="#petugas-tab-pane" type="button" role="tab"
                        aria-controls="petugas-tab-pane"
                        aria-selected="{{ request('tab') == 'petugas' ? 'true' : 'false' }}" data-tab-id="petugas">
                        <i class="bi bi-person-badge me-1"></i> Kinerja Petugas
                    </button>
                </li>
            @endif
        </ul>
        <div class="tab-content pt-2" id="analyticsTabContent"> {{-- Tambah pt-2 --}}

            {{-- Tab Pane 1: Visualisasi Chart --}}
            <div class="tab-pane fade {{ request('tab', 'visualisasi') == 'visualisasi' ? 'show active' : '' }}"
                id="visualisasi-tab-pane" role="tabpanel" aria-labelledby="visualisasi-tab" tabindex="0">
                <div class="row g-4">
                    {{-- Chart Tren Penjualan --}}
                    <div class="col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div
                                class="card-header bg-light fw-semibold d-flex justify-content-between align-items-center">
                                <span>Tren Penjualan Bulanan</span>
                                <div wire:loading wire:target="loadSalesChartData, loadAllChartData" class="ms-2">
                                    <x-filament::loading-indicator class="h-4 w-4 text-primary-500" />
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container"><canvas id="salesTrendChart"></canvas></div>
                            </div>
                        </div>
                    </div>
                    {{-- Chart Komposisi Bantuan --}}
                    <div class="col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div
                                class="card-header bg-light fw-semibold d-flex justify-content-between align-items-center">
                                <span>Komposisi Nilai Bantuan</span>
                                <div wire:loading wire:target="loadAidCompositionChartData, loadAllChartData"
                                    class="ms-2"> <x-filament::loading-indicator class="h-4 w-4 text-primary-500" />
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container"><canvas id="aidCompositionChart"></canvas></div>
                            </div>
                        </div>
                    </div>
                    {{-- Chart Tren Monitoring --}}
                    <div class="col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div
                                class="card-header bg-light fw-semibold d-flex justify-content-between align-items-center">
                                <span>Tren Perkembangan Monitoring (%)</span>
                                <div wire:loading wire:target="loadMonitoringTrendChartData, loadAllChartData"
                                    class="ms-2"> <x-filament::loading-indicator class="h-4 w-4 text-primary-500" />
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container"><canvas id="monitoringTrendChart"></canvas></div>
                            </div>
                        </div>
                    </div>
                    {{-- Chart Performa Kelompok --}}
                    <div class="col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div
                                class="card-header bg-light fw-semibold d-flex justify-content-between align-items-center">
                                <span>Performa Penjualan Kelompok (Top 10)</span>
                                <div wire:loading wire:target="loadGroupPerformanceChartData, loadAllChartData"
                                    class="ms-2"> <x-filament::loading-indicator class="h-4 w-4 text-primary-500" />
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container"><canvas id="groupPerformanceChart"></canvas></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab Pane 2: Resume per Kelompok (Tabel) --}}
            <div class="tab-pane fade {{ request('tab') == 'resume' ? 'show active' : '' }}" id="resume-tab-pane"
                role="tabpanel" aria-labelledby="resume-tab" tabindex="0">
                <div class="card shadow-sm border">
                    <div class="card-header bg-light fw-semibold">Resume Perkembangan per Kelompok</div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    {{-- Helper untuk link sorting kelompok --}}
                                    @php
                                        // Pastikan nama variabel ini konsisten
                                        $makeSortLinkKelompok = function ($column, $label) use (
                                            $sortColumn,
                                            $sortDirection,
                                        ) {
                                            $direction =
                                                $sortColumn == $column && $sortDirection == 'asc' ? 'desc' : 'asc';
                                            $icon = '';
                                            if ($sortColumn == $column) {
                                                $icon =
                                                    $sortDirection == 'asc'
                                                        ? '<i class="bi bi-sort-up sort-icon"></i>'
                                                        : '<i class="bi bi-sort-down sort-icon"></i>';
                                            }
                                            $url = route(
                                                'analytics.admin',
                                                array_merge(request()->query(), [
                                                    'sort' => $column,
                                                    'direction' => $direction,
                                                    'page' => 1,
                                                    'tab' => request('tab', 'resume'),
                                                ]),
                                            );
                                            return '<a href="' .
                                                e($url) .
                                                '" class="sortable-link text-decoration-none">' .
                                                e($label) .
                                                ' ' .
                                                $icon .
                                                '</a>';
                                        };
                                    @endphp
                                    {{-- Gunakan nama helper yang benar --}}
                                    <th scope="col">{!! $makeSortLinkKelompok('nama_kelompok', 'Nama Kelompok') !!}</th>
                                    <th scope="col">Lokasi</th>
                                    <th scope="col">{!! $makeSortLinkKelompok('jenis_kelompok', 'Jenis') !!}</th>
                                    <th scope="col" class="text-end">Total Bantuan (Rp)</th>
                                    <th scope="col" class="text-end">{!! $makeSortLinkKelompok('total_penjualan_agg', 'Total Penjualan (Rp)') !!}</th>
                                    <th scope="col" class="text-center">{!! $makeSortLinkKelompok('avg_perkembangan_agg', 'Avg. Perkembangan (%)') !!}</th>
                                    <th scope="col" class="text-center">{!! $makeSortLinkKelompok('monitoring_terakhir', 'Monitor Terakhir') !!}</th>
                                    @if (Auth::check() && Auth::user()->hasRole('Admin'))
                                        <th scope="col">Petugas PJ</th>
                                    @endif
                                    <th scope="col" class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($kelompoks ?? [] as $kelompok)
                                    <tr>
                                        <td class="fw-medium">{{ $kelompok->nama_kelompok }}</td>
                                        <td class="small text-muted">{{ $kelompok->desa?->nama_desa ?? 'N/A' }}, Kec.
                                            {{ $kelompok->desa?->kecamatan?->nama_kecamatan ?? 'N/A' }}</td>
                                        <td>
                                            @php
                                                $badgeColor = match ($kelompok->jenis_kelompok) {
                                                    'Pertanian' => 'success',
                                                    'Peternakan' => 'warning',
                                                    'Perikanan' => 'info',
                                                    default => 'secondary',
                                                };
                                            @endphp
                                            <span
                                                class="badge bg-{{ $badgeColor }}">{{ $kelompok->jenis_kelompok }}</span>
                                        </td>
                                        <td class="text-end">Rp
                                            {{ number_format($kelompok->total_nilai_bantuan_agg ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-end text-success fw-medium">Rp
                                            {{ number_format($kelompok->total_penjualan_agg ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-center text-warning fw-medium">
                                            {{ $kelompok->avg_perkembangan_agg !== null ? number_format($kelompok->avg_perkembangan_agg, 1, ',', '.') . '%' : '-' }}
                                        </td>
                                        <td class="text-center small text-muted">
                                            {{ $kelompok->monitoring_terakhir ? \Carbon\Carbon::parse($kelompok->monitoring_terakhir)->format('d/m/y') : '-' }}
                                        </td>
                                        @if (Auth::check() && Auth::user()->hasRole('Admin'))
                                            <td class="small text-muted">{{ $kelompok->petugas?->name ?? '-' }}</td>
                                        @endif
                                        <td class="text-end">
                                            <a href="{{ route('kelompok.detail', $kelompok->id) }}"
                                                class="btn btn-outline-primary btn-sm py-0 px-1" title="Lihat Detail"> <i
                                                    class="bi bi-eye-fill"></i> </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ Auth::check() && Auth::user()->hasRole('Admin') ? 9 : 8 }}"
                                            class="text-center text-muted py-4 fst-italic"> Tidak ada data kelompok
                                            ditemukan. </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if (isset($kelompoks) && $kelompoks->hasPages())
                        <div class="card-footer bg-light border-top-0 d-flex justify-content-center">
                            {{-- Tambahkan parameter 'tab' ke link pagination --}}
                            {{ $kelompoks->appends(array_merge(request()->except('page_petugas'), ['tab' => request('tab', 'resume')]))->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- TAB PANE Kinerja Petugas --}}
            @if (Auth::check() && Auth::user()->hasRole('Admin'))
                <div class="tab-pane fade {{ request('tab') == 'petugas' ? 'show active' : '' }}" id="petugas-tab-pane"
                    role="tabpanel" aria-labelledby="petugas-tab" tabindex="0">
                    <div class="card shadow-sm border">
                        <div class="card-header bg-light fw-semibold"><i class="bi bi-person-badge me-1"></i> Analisis
                            Kinerja Petugas Lapangan</div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm mb-0">
                                <thead class="table-light">
                                    {{-- DEBUGGING --}}
                                    {{-- @php
                                        dump(isset($sortPetugasColumn), isset($sortPetugasDirection));
                                    @endphp --}}
                                    {{-- AKHIR DEBUGGING --}}
                                    <tr>
                                        {{-- Modifikasi Helper Sorting Petugas --}}
                                        @php
                                            $makeSortLinkPetugas = function ($column, $label) use (
                                                $sortPetugasColumn,
                                                $sortPetugasDirection,
                                            ) {
                                                $direction =
                                                    $sortPetugasColumn == $column && $sortPetugasDirection == 'asc'
                                                        ? 'desc'
                                                        : 'asc';
                                                $icon = '';
                                                if ($sortPetugasColumn == $column) {
                                                    $icon =
                                                        $sortPetugasDirection == 'asc'
                                                            ? '<i class="bi bi-sort-up sort-icon"></i>'
                                                            : '<i class="bi bi-sort-down sort-icon"></i>';
                                                }
                                                // Tambahkan parameter 'tab' saat ini ke URL sorting
                                                $url = route(
                                                    'analytics.admin',
                                                    array_merge(request()->except(['page', 'page_petugas']), [
                                                        'sort_petugas' => $column,
                                                        'direction_petugas' => $direction,
                                                        'page_petugas' => 1,
                                                        'tab' => request('tab', 'petugas'),
                                                    ]),
                                                ); // Default ke tab petugas
                                                return '<a href="' .
                                                    e($url) .
                                                    '" class="sortable-link text-decoration-none">' .
                                                    e($label) .
                                                    ' ' .
                                                    $icon .
                                                    '</a>';
                                            };
                                        @endphp
                                        <th scope="col">{!! $makeSortLinkPetugas('name', 'Nama Petugas') !!}</th>
                                        <th scope="col" class="text-center">{!! $makeSortLinkPetugas('jumlah_kelompok_aktif', 'Jml Kelompok') !!}</th>
                                        <th scope="col" class="text-end">{!! $makeSortLinkPetugas('total_penjualan_petugas', 'Total Penjualan (Rp)') !!}</th>
                                        <th scope="col" class="text-center">{!! $makeSortLinkPetugas('avg_perkembangan_petugas', 'Avg. Perkembangan (%)') !!}</th>
                                        <th scope="col" class="text-center">{!! $makeSortLinkPetugas('count_monitoring_dilakukan', 'Jml Monitoring') !!}</th>
                                        <th scope="col" class="text-center">{!! $makeSortLinkPetugas('count_pelaporan_dilakukan', 'Jml Pelaporan') !!}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($kinerjaPetugas ?? [] as $petugas)
                                        <tr>
                                            <td class="fw-medium">{{ $petugas->name }}</td>
                                            <td class="text-center">{{ $petugas->jumlah_kelompok_aktif ?? 0 }}</td>
                                            <td class="text-end text-success">
                                                {{ number_format($petugas->total_penjualan_petugas ?? 0, 0, ',', '.') }}
                                            </td>
                                            <td class="text-center text-warning">
                                                {{ $petugas->avg_perkembangan_petugas !== null ? number_format($petugas->avg_perkembangan_petugas, 1, ',', '.') . '%' : '-' }}
                                            </td>
                                            <td class="text-center">{{ $petugas->count_monitoring_dilakukan ?? 0 }}</td>
                                            <td class="text-center">{{ $petugas->count_pelaporan_dilakukan ?? 0 }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4 fst-italic"> Tidak ada
                                                data kinerja petugas. </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if (isset($kinerjaPetugas) && $kinerjaPetugas->hasPages())
                            <div class="card-footer bg-light border-top-0 d-flex justify-content-center">
                                {{-- Tambahkan parameter 'tab' ke link pagination --}}
                                {{ $kinerjaPetugas->appends(array_merge(request()->except('page'), ['tab' => request('tab', 'petugas')]))->links('pagination::bootstrap-5') }}
                            </div>
                        @endif
                    </div>
                </div>
            @endif

        </div> {{-- Akhir Tab Content --}}

    </div>
@endsection

@push('scripts')
    {{-- JS Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@^4"></script> {{-- Pastikan Chart.js v4 atau sesuai --}}
    <script>
        // Pastikan script hanya dijalankan sekali per page load penuh
        if (typeof window.adminAnalyticsChartsInitialized === 'undefined') {
            window.adminAnalyticsChartsInitialized = true;

            // Variabel instance chart (disimpan di window agar bisa diakses/dihancurkan)
            window.adminSalesChart = null;
            window.adminAidChart = null;
            window.adminGroupChart = null;
            window.adminMonitoringChart = null;

            // --- Fungsi Helper Format Angka (Rupiah, Ribuan, Jutaan) ---
            const formatCurrency = (value) => {
                if (value === null || typeof value === 'undefined' || isNaN(value)) return 'Rp 0';
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

            // --- Fungsi Helper Format Tooltip (Label) Rupiah ---
            const formatTooltipLabel = (context) => {
                // --- DEBUGGING TOOLTIP ---
                // console.log('Tooltip Context:', context);
                // Lihat properti apa saja yang ada di context.parsed
                // Untuk bar horizontal, kita harapkan context.parsed.x berisi nilai Rupiah
                // -------------------------

                let label = context.dataset.label || '';
                if (label) { label += ': '; }

                // Coba akses nilai dengan lebih hati-hati
                let value = null;
                if (context.parsed) {
                    // Prioritaskan X untuk bar horizontal, Y untuk lainnya
                    value = context.chart.options.indexAxis === 'y' ? context.parsed.x : context.parsed.y;
                }

                if (value !== null && !isNaN(value)) {
                    label += 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                } else {
                    // Jika value tidak valid, coba ambil dari data asli berdasarkan index
                    const dataIndex = context.dataIndex;
                    const datasetIndex = context.datasetIndex;
                    const originalValue = context.chart.data.datasets[datasetIndex]?.data[dataIndex];
                    if (originalValue !== null && !isNaN(originalValue)) {
                         label += 'Rp ' + new Intl.NumberFormat('id-ID').format(originalValue) + ' (raw)'; // Tandai jika dari data raw
                    } else {
                        label += 'N/A';
                    }
                }
                return label;
            };

            // --- Fungsi Helper Format Persen ---
            const formatPercent = (value) => {
                if (value === null || typeof value === 'undefined' || isNaN(value)) return '0%';
                return new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 1,
                    maximumFractionDigits: 1
                }).format(value) + '%';
            };

            // --- Fungsi Helper Format Tooltip Persen ---
            const formatTooltipPercent = (context) => {
                let label = context.dataset.label || '';
                if (label) {
                    label += ': ';
                }
                if (context.parsed.y !== null && !isNaN(context.parsed.y)) {
                    label += new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: 1,
                        maximumFractionDigits: 1
                    }).format(context.parsed.y) + '%';
                } else {
                    label += 'N/A';
                }
                return label;
            };
            // ------------------------------------

            // --- Opsi Chart ---
            const salesOptions = {
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
            const aidOptions = {
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
            const groupOptions = {
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
            const monitoringOptions = {
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

            // --- Fungsi untuk Membuat Chart ---
            const createChart = (ctx, instanceVar, chartType, chartData, chartOptions) => {
                if (!ctx) {
                    console.warn(`Canvas element not found for ${instanceVar}.`);
                    return;
                }
                // Beri data default jika chartData null atau tidak valid
                const dataToUse = (chartData && Array.isArray(chartData.labels) && Array.isArray(chartData.datasets)) ?
                    chartData : {
                        labels: [],
                        datasets: []
                    };

                // Hancurkan instance lama jika ada
                if (window[instanceVar]) {
                    console.log(`Destroying previous instance of ${instanceVar}`);
                    window[instanceVar].destroy();
                }
                // Buat instance baru
                console.log(`Creating new chart instance for ${instanceVar}`);
                window[instanceVar] = new Chart(ctx, {
                    type: chartType,
                    data: dataToUse,
                    options: chartOptions
                });
            };
            // ---------------------------------

            // --- Inisialisasi Chart saat DOM siap ---
            document.addEventListener('DOMContentLoaded', function() {
                console.log('DOM ready, initializing admin analytics charts...');
                // Ambil data dari variabel PHP yang di-JSON-kan
                // Tambahkan fallback data kosong jika variabel PHP tidak ada/null
                const salesData = @json($salesTrendData ?? ['labels' => [], 'datasets' => []]);
                const aidData = @json($aidCompositionData ?? ['labels' => [], 'datasets' => []]);
                const groupData = @json($groupPerformanceData ?? ['labels' => [], 'datasets' => []]);
                const monitoringData = @json($monitoringTrendData ?? ['labels' => [], 'datasets' => []]);

                // Panggil createChart untuk setiap chart
                createChart(document.getElementById('salesTrendChart'), 'adminSalesChart', 'line', salesData,
                    salesOptions);
                createChart(document.getElementById('aidCompositionChart'), 'adminAidChart', 'bar', aidData,
                    aidOptions);
                createChart(document.getElementById('groupPerformanceChart'), 'adminGroupChart', 'bar', groupData,
                    groupOptions);
                createChart(document.getElementById('monitoringTrendChart'), 'adminMonitoringChart', 'line',
                    monitoringData, monitoringOptions);

                // --- Event Listener untuk Tab Bootstrap ---
                const analyticsTabs = document.querySelectorAll('#analyticsTab button[data-bs-toggle="tab"]');
                analyticsTabs.forEach(tabEl => {
                    tabEl.addEventListener('shown.bs.tab', event => {
                        console.log(`Tab shown: ${event.target.id}`); // Debug tab yang aktif
                        // Panggil resize pada chart HANYA jika tab visualisasi yang aktif
                        if (event.target.id === 'visualisasi-tab') {
                            // Beri sedikit delay sebelum resize untuk memastikan canvas visible
                            setTimeout(() => {
                                console.log('Resizing charts in active tab...');
                                if (window.adminSalesChart) window.adminSalesChart.resize();
                                if (window.adminAidChart) window.adminAidChart.resize();
                                if (window.adminGroupChart) window.adminGroupChart.resize();
                                if (window.adminMonitoringChart) window.adminMonitoringChart
                                    .resize();
                            }, 50); // Delay 50ms
                        }
                    });
                });
                // -----------------------------------------
            });
            // --------------------------------------

        } // Akhir cek window.adminAnalyticsChartsInitialized

        // --- Script untuk Mengelola State Tab Aktif ---
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('#analyticsTab button[data-bs-toggle="tab"]');
            const currentUrl = new URL(window.location.href);

            // Listener untuk setiap tombol tab
            tabButtons.forEach(tabButton => {
                tabButton.addEventListener('click', function(event) {
                    const tabId = this.dataset.tabId; // Ambil ID tab dari data-*
                    if (tabId) {
                        // Update URL tanpa reload halaman
                        currentUrl.searchParams.set('tab', tabId);
                        // Hapus parameter page & page_petugas saat ganti tab
                        currentUrl.searchParams.delete('page');
                        currentUrl.searchParams.delete('page_petugas');
                        history.pushState({}, '', currentUrl);
                    }
                });

                // Panggil resize chart jika tab visualisasi ditampilkan (sudah ada di listener 'shown.bs.tab')
                // tabButton.addEventListener('shown.bs.tab', event => { ... });
            });

            // Baca parameter 'tab' saat load dan aktifkan tab yang sesuai (jika perlu, tapi class active dari PHP seharusnya cukup)
            // const activeTabId = currentUrl.searchParams.get('tab') || 'visualisasi'; // Default visualisasi
            // const tabToActivate = document.getElementById(activeTabId + '-tab');
            // if (tabToActivate) {
            //     const tab = new bootstrap.Tab(tabToActivate);
            //     tab.show();
            // }
        });
        // ------------------------------------------
    </script>
    {{-- Script untuk filter desa dinamis (jika diperlukan) --}}
    {{-- <script> ... fetch desa by kecamatan ... </script> --}}
@endpush
