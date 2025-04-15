@extends('layouts.app') {{-- Menggunakan layout induk --}}

@section('title', 'Peta Potensi Kelompok Binaan') {{-- Judul spesifik halaman --}}

@push('styles')
    {{-- CSS Leaflet & MarkerCluster --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    {{-- Bootstrap Icons (jika belum ada di layout utama) --}}
    {{-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> --}}

    {{-- CSS Kustom --}}
    <style>
        /* Styling Hero Section */
        .hero-map-section {
            position: relative;
            /* Penting untuk positioning absolut children */
            height: 80vh;
            /* Tinggi bisa disesuaikan */
            min-height: 550px;
            width: 100%;
            overflow: hidden;
        }

        #heroMap {
            height: 100%;
            width: 100%;
            position: absolute;
            /* Peta sebagai background absolut */
            top: 0;
            left: 0;
            z-index: 1;
        }

        .hero-map-content-overlay {
            /* Overlay hanya untuk konten teks */
            position: relative;
            /* Di atas peta tapi tidak full */
            z-index: 3;
            /* Di atas peta dan filter */
            padding-top: 10vh;
            /* Jarak dari atas */
            padding-bottom: 5vh;
            text-align: center;
            color: white;
            /* Background gradient hanya di belakang teks (opsional) */
            /* background: linear-gradient(to bottom, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.7) 100%); */
            /* padding: 2rem; */
            /* Jika pakai background */
            text-shadow: 1px 1px 5px rgba(0, 0, 0, 0.7);
        }

        .hero-map-content-overlay h1 {
            font-size: calc(1.5rem + 2vw);
            font-weight: 700;
            margin-bottom: 0.75rem;
            line-height: 1.2;
        }

        .hero-map-content-overlay .lead {
            font-size: calc(1rem + 0.3vw);
            font-weight: 300;
            max-width: 700px;
            margin-bottom: 1.5rem;
            opacity: 0.95;
        }

        .hero-map-content-overlay .btn {
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.4);
        }

        /* Styling Filter Mengambang */
        .floating-filter {
            position: absolute;
            bottom: 20px;
            /* Jarak dari bawah peta */
            left: 50%;
            transform: translateX(-50%);
            /* Pusatkan horizontal */
            z-index: 2;
            /* Di atas peta, di bawah overlay teks jika overlay full */
            width: 90%;
            /* Lebar filter */
            max-width: 800px;
            /* Lebar maksimum filter */
        }

        .floating-filter .card-body {
            padding: 0.8rem 1rem;
            /* Padding lebih kecil */
            background-color: rgba(255, 255, 255, 0.9);
            /* Background agak transparan */
            backdrop-filter: blur(4px);
            /* Efek blur */
            border-radius: 0.5rem;
            /* Sudut bulat */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .dark .floating-filter .card-body {
            /* Styling dark mode untuk filter */
            background-color: rgba(33, 37, 41, 0.9);
            /* Dark agak transparan */
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .floating-filter .form-label {
            display: none;
        }

        /* Sembunyikan label */
        .floating-filter .form-select-sm,
        .floating-filter .btn-sm {
            font-size: 0.8rem;
        }

        /* Ukuran font lebih kecil */


        /* Styling untuk kartu kelompok */
        .kelompok-card .card-img-top {
            height: 180px;
            object-fit: cover;
        }

        .kelompok-card .badge {
            font-size: 0.75em;
        }

        /* Styling ikon marker kustom */
        .custom-leaflet-marker div {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
            transition: transform 0.1s ease-in-out;
        }

        .custom-leaflet-marker:hover div {
            transform: scale(1.2);
        }

        /* Styling popup Leaflet */
        .leaflet-popup-pane {
            z-index: 700 !important;
            /* Coba nilai z-index tinggi */
        }

        /* Styling Overview Container */
        #overview-container {
            padding: 1rem;
            /* Padding default */
        }

        /* Styling Overview untuk Desktop (lg ke atas) - Floating Kanan */
        @media (min-width: 992px) {

            /* Breakpoint lg Bootstrap */
            #overview-container {
                position: absolute;
                top: 90px;
                /* Jarak dari atas (sesuaikan dengan tinggi navbar) */
                right: 20px;
                /* Jarak dari kanan */
                width: 280px;
                /* Lebar tetap untuk kolom kanan */
                max-height: calc(100% - 110px);
                /* Tinggi maksimum agar tidak overlap footer */
                overflow-y: auto;
                /* Scroll jika konten lebih tinggi */
                z-index: 2;
                /* Di atas peta, di bawah overlay jika overlay full */
                background-color: rgba(255, 255, 255, 0.85);
                /* Background putih transparan */
                backdrop-filter: blur(5px);
                border-radius: 0.75rem;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                padding: 1rem;
                /* Padding di dalam container */
            }

            .dark #overview-container {
                /* Dark mode */
                background-color: rgba(33, 37, 41, 0.85);
                border: 1px solid rgba(255, 255, 255, 0.1);
            }

            /* Kartu di dalam overview saat desktop */
            #overview-container .overview-card {
                margin-bottom: 1rem;
                /* Jarak antar kartu vertikal */
                background-color: transparent !important;
                /* Hapus background kartu individu */
                border: none !important;
                /* Hapus border kartu individu */
                box-shadow: none !important;
                /* Hapus shadow kartu individu */
                padding: 0 !important;
                /* Hapus padding kartu individu */
            }

            #overview-container .overview-card .card-body {
                padding: 0;
            }

            #overview-container .overview-card .icon-bg {
                /* Ikon lebih kecil */
                width: 3rem;
                height: 3rem;
                font-size: 1.5rem;
            }

            #overview-container .overview-card .stat-value {
                font-size: 1.75rem;
            }

            #overview-container .overview-card .stat-label {
                font-size: 0.8rem;
            }

            #overview-container .overview-card .card-footer-link {
                display: none;
            }

            /* Sembunyikan link footer di float */
        }

        /* Styling Kartu Overview (digunakan di semua ukuran) */
        .overview-card {
            border-radius: 0.75rem;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
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

        /* Warna spesifik (hanya untuk ikon dan nilai di mode non-float) */
        .overview-card.card-kelompok .icon-bg,
        .overview-card.card-kelompok .stat-value {
            color: var(--bs-primary);
        }

        .overview-card.card-bantuan .icon-bg,
        .overview-card.card-bantuan .stat-value {
            color: var(--bs-warning);
        }

        .overview-card.card-wilayah .icon-bg,
        .overview-card.card-wilayah .stat-value {
            color: var(--bs-info);
        }

        .overview-card.card-anggota .icon-bg,
        .overview-card.card-anggota .stat-value {
            color: var(--bs-success);
        }

        /* Link footer di dalam card */
        .card-footer-link {
            display: block;
            /* Tampil sebagai block */
            padding: 0.5rem 1.25rem;
            background-color: rgba(0, 0, 0, 0.03);
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            text-align: center;
            font-size: 0.8rem;
            font-weight: 500;
            color: #555;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }

        .card-footer-link:hover {
            background-color: rgba(0, 0, 0, 0.06);
            color: #333;
        }

        .dark .card-footer-link {
            background-color: rgba(255, 255, 255, 0.05);
            border-top-color: rgba(255, 255, 255, 0.1);
            color: #adb5bd;
        }

        .dark .card-footer-link:hover {
            background-color: rgba(255, 255, 255, 0.08);
            color: #f8f9fa;
        }
    </style>
@endpush

@section('content')

    {{-- 1. Hero Section dengan Peta --}}
    <section class="hero-map-section">
        {{-- Div untuk Peta Leaflet --}}
        <div id="heroMap">
            {{-- Placeholder Loading --}}
            <div class="d-flex justify-content-center align-items-center h-100 bg-light">
                <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span>
                </div>
                <span class="ms-2 text-muted">Memuat peta...</span>
            </div>
        </div>

        {{-- Konten Teks & Tombol (Tidak full overlay)
        <div class="hero-map-content-overlay">
            <div class="container px-4 px-lg-5">
                <h1 class="mb-3">Mendorong Pertumbuhan Melalui Bantuan Tepat Sasaran</h1>
                <p class="lead mb-4">Jelajahi data persebaran, pemanfaatan, dan dampak program bantuan di Kabupaten Muna Barat secara interaktif.</p>
                <div>
                    <a href="#kelompokUnggulanSection" class="btn btn-primary btn-lg px-4 me-sm-3 mb-2 mb-sm-0">
                       <i class="bi bi-star-fill me-1"></i> Lihat Kelompok Unggulan
                    </a>
                    <a href="{{ route('kelompok.list') }}" class="btn btn-outline-light btn-lg px-4 mb-2 mb-sm-0">
                        <i class="bi bi-list-ul me-1"></i> Lihat Semua Kelompok
                    </a>
                </div>
            </div>
        </div> --}}

        {{-- Filter Mengambang di Atas Peta --}}
        <div class="floating-filter">
            <div class="card border-0"> {{-- Card tanpa border luar --}}
                <div class="card-body">
                    <form action="{{ route('peta.potensi') }}" method="GET">
                        <div class="row g-2 align-items-center"> {{-- g-2 untuk jarak lebih kecil --}}
                            <div class="col-lg col-md-6">
                                <select class="form-select form-select-sm" id="filterKecamatan" name="kecamatan"
                                    title="Filter Kecamatan">
                                    <option value="">Semua Kecamatan</option>
                                    @foreach ($kecamatans ?? [] as $id => $nama)
                                        <option value="{{ $id }}"
                                            {{ request('kecamatan') == $id ? 'selected' : '' }}>{{ $nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg col-md-6">
                                <select class="form-select form-select-sm" id="filterJenis" name="jenis"
                                    title="Filter Jenis Kelompok">
                                    <option value="">Semua Jenis</option>
                                    <option value="Pertanian" {{ request('jenis') == 'Pertanian' ? 'selected' : '' }}>
                                        Pertanian</option>
                                    <option value="Peternakan" {{ request('jenis') == 'Peternakan' ? 'selected' : '' }}>
                                        Peternakan</option>
                                    <option value="Perikanan" {{ request('jenis') == 'Perikanan' ? 'selected' : '' }}>
                                        Perikanan</option>
                                </select>
                            </div>
                            <div class="col-lg-auto col-md-6">
                                <button type="submit" class="btn btn-secondary btn-sm w-100">Filter</button>
                            </div>
                            <div class="col-lg-auto col-md-6">
                                <a href="{{ route('peta.potensi') }}"
                                    class="btn btn-outline-secondary btn-sm w-100">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Overview Stats Mengambang (Hanya Desktop) --}}
        <div id="overview-container" class="d-none d-lg-block"> {{-- d-none d-lg-block: Sembunyi di mobile/tablet, tampil di large --}}
            <h5 class="fw-semibold mb-3 text-gray-700 dark:text-gray-300">Ringkasan Program</h5>
            <div class="vstack gap-3"> {{-- Susun vertikal dengan gap --}}
                {{-- Card Total Kelompok --}}
                <div class="card overview-card card-kelompok shadow-sm">
                    <div class="card-body">
                        <i class="bi bi-people-fill icon-bg"></i>
                        <div class="stat-value">{{ number_format($totalKelompok ?? 0, 0, ',', '.') }}</div>
                        <p class="stat-label">Total Kelompok Binaan</p>
                    </div>
                    {{-- Link tidak ditampilkan di mode float --}}
                </div>
                {{-- Card Total Nilai Bantuan --}}
                <div class="card overview-card card-bantuan shadow-sm">
                    <div class="card-body">
                        <i class="bi bi-gift-fill icon-bg"></i>
                        <div class="stat-value">Rp {{ number_format($totalNilaiBantuan ?? 0, 0, ',', '.') }}</div>
                        <p class="stat-label">Total Estimasi Bantuan</p>
                    </div>
                </div>
                {{-- Card Jumlah Kecamatan --}}
                <div class="card overview-card card-wilayah shadow-sm">
                    <div class="card-body">
                        <i class="bi bi-map-fill icon-bg"></i>
                        <div class="stat-value">{{ number_format($totalKecamatan ?? 0, 0, ',', '.') }}</div>
                        <p class="stat-label">Kecamatan Tercakup</p>
                    </div>
                </div>
                {{-- Card Total Anggota --}}
                <div class="card overview-card card-anggota shadow-sm">
                    <div class="card-body">
                        <i class="bi bi-person-check-fill icon-bg"></i>
                        <div class="stat-value">{{ number_format($totalAnggota ?? 0, 0, ',', '.') }}</div>
                        <p class="stat-label">Total Anggota Terdaftar</p>
                    </div>
                </div>
            </div>
        </div>

    </section> {{-- Akhir Hero Section --}}

    {{-- Section Overview Stats (Hanya Mobile/Tablet) --}}
    <section class="py-5 d-lg-none bg-light"> {{-- d-lg-none: Tampil di bawah lg, sembunyi di lg ke atas --}}
        <div class="container px-4 px-lg-5">
            <h3 class="text-center fw-bold mb-4">Ringkasan Program</h3>
            <div class="row g-4 justify-content-center">
                {{-- Card Total Kelompok --}}
                <div class="col-md-6 col-sm-6">
                    <div class="card overview-card card-kelompok h-100 shadow-sm border">
                        <div class="card-body text-center">
                            <i class="bi bi-people-fill icon-bg"></i>
                            <div class="stat-value">{{ number_format($totalKelompok ?? 0, 0, ',', '.') }}</div>
                            <p class="stat-label">Total Kelompok Binaan</p>
                        </div>
                        <a href="{{ route('kelompok.list') }}" class="card-footer-link"
                            aria-label="Lihat Daftar Kelompok">Lihat Detail <i class="bi bi-arrow-right-short"></i></a>
                    </div>
                </div>
                {{-- Card Total Nilai Bantuan --}}
                <div class="col-md-6 col-sm-6">
                    <div class="card overview-card card-bantuan h-100 shadow-sm border">
                        <div class="card-body text-center">
                            <i class="bi bi-gift-fill icon-bg"></i>
                            <div class="stat-value">Rp {{ number_format($totalNilaiBantuan ?? 0, 0, ',', '.') }}</div>
                            <p class="stat-label">Total Estimasi Bantuan</p>
                        </div>
                        <a href="#" class="card-footer-link" aria-label="Lihat Distribusi Bantuan">Lihat Detail <i
                                class="bi bi-arrow-right-short"></i></a>
                    </div>
                </div>
                {{-- Card Jumlah Kecamatan --}}
                <div class="col-md-6 col-sm-6">
                    <div class="card overview-card card-wilayah h-100 shadow-sm border">
                        <div class="card-body text-center">
                            <i class="bi bi-map-fill icon-bg"></i>
                            <div class="stat-value">{{ number_format($totalKecamatan ?? 0, 0, ',', '.') }}</div>
                            <p class="stat-label">Kecamatan Tercakup</p>
                        </div>
                        <a href="{{ route('peta.potensi') }}" class="card-footer-link"
                            aria-label="Lihat Peta Persebaran">Lihat Peta <i class="bi bi-arrow-right-short"></i></a>
                    </div>
                </div>
                {{-- Card Total Anggota --}}
                <div class="col-md-6 col-sm-6">
                    <div class="card overview-card card-anggota h-100 shadow-sm border">
                        <div class="card-body text-center">
                            <i class="bi bi-person-check-fill icon-bg"></i>
                            <div class="stat-value">{{ number_format($totalAnggota ?? 0, 0, ',', '.') }}</div>
                            <p class="stat-label">Total Anggota Terdaftar</p>
                        </div>
                        <a href="#" class="card-footer-link" aria-label="Lihat Anggota">Lihat Detail <i
                                class="bi bi-arrow-right-short"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Section Jenis Bantuan --}}
    <section class="py-5">
        <div class="container px-4 px-lg-5">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="fw-bold mb-4">Lingkup Program Bantuan</h2>
                    <p class="lead text-muted mb-5">Kami fokus pada pemberdayaan masyarakat melalui bantuan yang terarah
                        pada sektor-sektor kunci penopang ekonomi lokal.</p>
                </div>
            </div>
            <div class="row gx-lg-5 text-center">
                {{-- Kolom Pertanian --}}
                <div class="col-lg-4 mb-5 mb-lg-0">
                    <div class="feature-icon bg-success bg-gradient text-white mb-3"> {{-- Gunakan class feature-icon dari style sebelumnya --}}
                        <i class="bi bi-tree-fill"></i> {{-- Ganti ikon jika perlu --}}
                    </div>
                    <h3 class="h5 fw-bold">Pertanian</h3>
                    <p class="mb-0 text-muted px-lg-3">Bantuan bibit unggul, pupuk berkualitas, peralatan modern untuk
                        meningkatkan produktivitas pertanian dan ketahanan pangan.</p>
                </div>
                {{-- Kolom Peternakan --}}
                <div class="col-lg-4 mb-5 mb-lg-0">
                    <div class="feature-icon bg-warning bg-gradient text-white mb-3">
                        <i class="bi bi-clipboard-heart-fill"></i> {{-- Contoh ikon lain --}}
                    </div>
                    <h3 class="h5 fw-bold">Peternakan</h3>
                    <p class="mb-0 text-muted px-lg-3">Penyediaan bibit ternak sehat, pakan bergizi, obat-obatan, serta
                        pendampingan manajemen untuk meningkatkan populasi dan pendapatan peternak.</p>
                </div>
                {{-- Kolom Perikanan --}}
                <div class="col-lg-4">
                    <div class="feature-icon bg-info bg-gradient text-white mb-3">
                        <i class="bi bi-water"></i> {{-- Contoh ikon lain --}}
                    </div>
                    <h3 class="h5 fw-bold">Perikanan</h3>
                    <p class="mb-0 text-muted px-lg-3">Dukungan bibit ikan berkualitas, sarana prasarana (jaring, kolam,
                        perahu), dan pakan untuk meningkatkan hasil tangkapan dan budidaya perikanan.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- 3. Section Kelompok Unggulan --}}
    <section id="kelompokUnggulanSection" class="py-5"> {{-- Beri ID --}}
        <div class="container px-4 px-lg-5 mt-4"> {{-- Tambah margin atas --}}
            <h2 class="text-center mb-5 fw-bold">Kelompok Binaan Unggulan</h2>
            <div class="row gx-4 gx-lg-5 row-cols-1 row-cols-md-2 row-cols-xl-3 justify-content-center">
                @forelse ($kelompokUnggulan as $kelompok)
                    <div class="col mb-5">
                        <x-kelompok-card :kelompok="$kelompok" />
                    </div>
                @empty
                    <div class="col-12">
                        <p class="text-center text-muted">Belum ada kelompok unggulan untuk ditampilkan berdasarkan filter.
                        </p>
                    </div>
                @endforelse
            </div>
            <div class="text-center mt-4">
                <a href="{{ route('kelompok.list') }}" class="btn btn-outline-primary">Lihat Semua Kelompok</a>
            </div>
        </div>
    </section>

    {{-- Section Informasi Kemitraan / CTA --}}
    <section class="py-5 cta-section"> {{-- Bisa pakai class .cta-section dari layout atau style baru --}}
        <div class="container px-4 px-lg-5">
            <div class="row gx-lg-5 align-items-center">
                {{-- Kolom Teks --}}
                <div class="col-lg-7 mb-4 mb-lg-0">
                    <h2 class="display-6 fw-bold lh-1 mb-3">Berkontribusi untuk Pertumbuhan Bersama</h2>
                    <p class="lead text-muted mb-4">
                        Kami membuka peluang kemitraan bagi perusahaan, institusi, maupun individu yang ingin turut serta
                        dalam pemberdayaan kelompok masyarakat di Muna Barat. Dukungan Anda dapat berupa investasi modal,
                        pendampingan teknis, akses pasar, atau program CSR yang berkelanjutan.
                    </p>
                    <p class="text-muted mb-4">
                        Mari bersama kita wujudkan kemandirian ekonomi dan peningkatan kesejahteraan masyarakat melalui
                        program bantuan yang efektif dan tepat sasaran.
                    </p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                        <a href="#kontak" class="btn btn-primary btn-lg px-4 me-md-2">Hubungi Kami Sekarang</a>
                        <a href="{{ route('kelompok.list') }}" class="btn btn-outline-secondary btn-lg px-4">Lihat Semua
                            Kelompok</a>
                    </div>
                </div>
                {{-- Kolom Gambar/Ilustrasi (Opsional) --}}
                <div class="col-lg-5 text-center">
                    {{-- Ganti dengan gambar yang relevan --}}
                    <img src="https://idfos.or.id/wp-content/uploads/2021/09/Cerita-Instagram-Bingkai-PolaroidFilm-Tumpukan-Foto3-1030x814.png"
                        class="img-fluid rounded-3 shadow" alt="Ilustrasi Kemitraan">
                </div>
            </div>
        </div>
    </section>

    {{-- Section Kontak --}}
    <section id="kontak" class="py-5 bg-light"> {{-- Beri background terang --}}
        <div class="container px-4 px-lg-5">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="fw-bold mb-3">Hubungi Kami</h2>
                    <p class="text-muted mb-4">Ada pertanyaan, saran, atau tawaran kemitraan? Jangan ragu untuk menghubungi
                        kami melalui form di bawah ini atau detail kontak yang tersedia.</p>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-7">

                    {{-- Tampilkan Pesan Sukses/Error --}}
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                        </div>
                    @endif

                    {{-- Form Kontak --}}
                    <form action="{{ route('contact.store') }}" method="POST">
                        @csrf {{-- CSRF Token --}}

                        {{-- Nama Lengkap --}}
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control @error('nama_lengkap') is-invalid @enderror"
                                id="nama_lengkap" name="nama_lengkap" placeholder="Nama Lengkap Anda"
                                value="{{ old('nama_lengkap') }}" required>
                            <label for="nama_lengkap">Nama Lengkap</label>
                            @error('nama_lengkap')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                id="email" name="email" placeholder="name@example.com"
                                value="{{ old('email') }}" required>
                            <label for="email">Alamat Email</label>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Nomor Telepon --}}
                        <div class="form-floating mb-3">
                            <input type="tel" class="form-control @error('nomor_telepon') is-invalid @enderror"
                                id="nomor_telepon" name="nomor_telepon" placeholder="Nomor Telepon (Opsional)"
                                value="{{ old('nomor_telepon') }}">
                            <label for="nomor_telepon">Nomor Telepon (Opsional)</label>
                            @error('nomor_telepon')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Nama Instansi --}}
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control @error('nama_instansi') is-invalid @enderror"
                                id="nama_instansi" name="nama_instansi" placeholder="Nama Instansi/Perusahaan (Opsional)"
                                value="{{ old('nama_instansi') }}">
                            <label for="nama_instansi">Nama Instansi/Perusahaan (Opsional)</label>
                            @error('nama_instansi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Subjek --}}
                        <div class="form-floating mb-3">
                            <select class="form-select @error('subjek') is-invalid @enderror" id="subjek"
                                name="subjek" required>
                                <option value="" selected disabled>-- Pilih Subjek --</option>
                                <option value="Pertanyaan Umum"
                                    {{ old('subjek') == 'Pertanyaan Umum' ? 'selected' : '' }}>Pertanyaan Umum</option>
                                <option value="Tawaran Kemitraan"
                                    {{ old('subjek') == 'Tawaran Kemitraan' ? 'selected' : '' }}>Tawaran Kemitraan</option>
                                <option value="Permintaan Informasi"
                                    {{ old('subjek') == 'Permintaan Informasi' ? 'selected' : '' }}>Permintaan Informasi
                                </option>
                                <option value="Lainnya" {{ old('subjek') == 'Lainnya' ? 'selected' : '' }}>Lainnya
                                </option>
                            </select>
                            <label for="subjek">Subjek Pesan</label>
                            @error('subjek')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Pesan --}}
                        <div class="form-floating mb-3">
                            <textarea class="form-control @error('pesan') is-invalid @enderror" placeholder="Tuliskan pesan Anda di sini..."
                                id="pesan" name="pesan" style="height: 10rem" required>{{ old('pesan') }}</textarea>
                            <label for="pesan">Pesan</label>
                            @error('pesan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- CAPTCHA (Jika pakai Google reCAPTCHA v2) --}}
                        {{-- <div class="mb-3">
                                {!! NoCaptcha::display() !!}
                                @error('g-recaptcha-response') <div class="text-danger text-sm mt-1">{{ $message }}</div> @enderror
                           </div> --}}

                        {{-- Tombol Submit --}}
                        <div class="d-grid">
                            <button class="btn btn-primary btn-lg" type="submit">Kirim Pesan</button>
                        </div>
                    </form>
                </div>
            </div>
            {{-- Info Kontak Lain --}}
            <div class="row justify-content-center mt-5">
                <div class="col-lg-8 text-center">
                    <p class="fs-5 mb-2">
                        <i class="bi bi-envelope-fill me-2 text-primary"></i> <a
                            href="mailto:{{ config('mail.from.address', 'info@example.com') }}"
                            class="text-decoration-none">{{ config('mail.from.address', 'info@example.com') }}</a>
                    </p>
                    <p class="fs-5 mb-0">
                        <i class="bi bi-telephone-fill me-2 text-primary"></i> +62 123 4567 890 {{-- Ganti nomor telepon --}}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Tambahkan Section Kontak jika belum ada di Footer --}}
    <section id="kontak" class="py-5">
        <div class="container px-4 px-lg-5 text-center">
            <h3 class="fw-bold mb-3">Informasi Kontak</h3>
            <p class="text-muted mb-4">Untuk informasi lebih lanjut mengenai program atau peluang kemitraan, silakan
                hubungi kami:</p>
            <p class="fs-5">
                <i class="bi bi-envelope-fill me-2 text-primary"></i> <a href="mailto:info@domainanda.com"
                    class="text-decoration-none">info@domainanda.com</a>
            </p>
            <p class="fs-5 mb-0">
                <i class="bi bi-telephone-fill me-2 text-primary"></i> +62 123 4567 890
            </p>
            {{-- Tambahkan alamat jika perlu --}}
        </div>
    </section>

@endsection

@push('scripts')
    {{-- JS Leaflet & MarkerCluster --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script>
        // Pastikan script hanya jalan sekali
        if (typeof window.heroMapInitialized === 'undefined') {
            window.heroMapInitialized = true;

            document.addEventListener('DOMContentLoaded', function() {
                const mapElement = document.getElementById('heroMap'); // Target peta hero
                const kelompokData = @json($kelompokPeta); // Ambil SEMUA data peta

                if (!mapElement || typeof L === 'undefined' || typeof L.markerClusterGroup === 'undefined') {
                    console.error('Hero map element or Leaflet/MarkerCluster not found/loaded.');
                    if (mapElement) mapElement.innerHTML =
                        '<p class="text-center text-danger p-5">Gagal memuat peta.</p>';
                    return;
                }
                if (!kelompokData || kelompokData.length === 0) {
                    console.warn('No kelompok data for hero map.');
                    mapElement.innerHTML = ''; // Kosongkan saja jika tidak ada data
                    return;
                }

                try {
                    mapElement.innerHTML = ''; // Hapus loading

                    const firstRecordWithCoords = kelompokData.find(k => k.latitude && k.longitude);
                    const centerLat = firstRecordWithCoords ? firstRecordWithCoords.latitude : -4.84;
                    const centerLng = firstRecordWithCoords ? firstRecordWithCoords.longitude : 122.49;
                    const initialZoom = 9; // Zoom lebih jauh untuk hero agar Muna Barat terlihat

                    const map = L.map(mapElement, {
                        center: [centerLat, centerLng],
                        zoom: initialZoom,
                        zoomControl: true, // Ubah ke true agar user bisa zoom
                        scrollWheelZoom: true,
                        dragging: true,
                        touchZoom: true, // Aktifkan untuk responsif di mobile
                        doubleClickZoom: true, // Aktifkan untuk UX yang lebih baik
                        boxZoom: false,
                        keyboard: false,
                        tap: true,
                        attributionControl: true // Tampilkan atribusi OpenStreetMap
                    });

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 18,
                        minZoom: 8
                    }).addTo(map);

                    const markers = L.markerClusterGroup({
                        maxClusterRadius: 45, // Sesuaikan radius cluster
                        disableClusteringAtZoom: 12 // Cluster nonaktif di zoom 12+
                    });

                    // Fungsi untuk membuat ikon kustom berdasarkan jenis kelompok
                    function createCustomIcon(jenis) {
                        let iconHtml, color;

                        // Tentukan warna dan bentuk berdasarkan jenis
                        switch (jenis) {
                            case 'Pertanian':
                                color = '#198754'; // hijau
                                iconHtml =
                                    `<div style="background-color: ${color}; width: 12px; height: 12px; border-radius: 0; transform: rotate(45deg); border: 1px solid rgba(255,255,255,0.7);"></div>`; // bentuk diamond
                                break;
                            case 'Peternakan':
                                color = '#ffc107'; // kuning
                                iconHtml =
                                    `<div style="background-color: ${color}; width: 12px; height: 12px; border-radius: 3px; border: 1px solid rgba(255,255,255,0.7);"></div>`; // bentuk persegi
                                break;
                            case 'Perikanan':
                                color = '#0d6efd'; // biru
                                iconHtml =
                                    `<div style="background-color: ${color}; width: 12px; height: 12px; border-radius: 50%; border: 1px solid rgba(255,255,255,0.7);"></div>`; // bentuk lingkaran
                                break;
                            default:
                                color = '#6c757d'; // abu-abu
                                iconHtml =
                                    `<div style="background-color: ${color}; width: 10px; height: 10px; border-radius: 50%; border: 1px solid rgba(255,255,255,0.7);"></div>`;
                        }

                        return L.divIcon({
                            className: 'custom-leaflet-marker',
                            html: iconHtml,
                            iconSize: [14, 14],
                            iconAnchor: [7, 7]
                        });
                    }

                    kelompokData.forEach(kelompok => {
                        const lat = parseFloat(kelompok.latitude);
                        const lng = parseFloat(kelompok.longitude);
                        if (!isNaN(lat) && !isNaN(lng)) {
                            const icon = createCustomIcon(kelompok.jenis);

                            // --- KONTEN POPUP ---
                            // Ambil nilai dengan fallback
                            const namaKelompok = kelompok.nama || 'Nama Tidak Tersedia';
                            const jenisKelompok = kelompok.jenis || 'N/A';
                            const desaKelompok = kelompok.desa || 'N/A';
                            const petugasKelompok = kelompok.petugas || 'N/A';
                            const detailUrl = kelompok.detail_url || '#';

                            // Bangun string HTML popup
                            let popupContent =
                                '<div style="min-width: 180px; font-size: 0.8rem; line-height: 1.4;">';
                            popupContent +=
                                '<div style="font-size: 1rem; font-weight: 600; margin-bottom: 5px;">' +
                                namaKelompok + '</div>';
                            popupContent += '<div><strong>Jenis:</strong> ' + jenisKelompok + '</div>';
                            popupContent += '<div><strong>Desa:</strong> ' + desaKelompok + '</div>';
                            popupContent += '<div><strong>Petugas:</strong> ' + petugasKelompok + '</div>';
                            popupContent += '<div style="margin-top: 8px;">';
                            popupContent += '<a href="' + detailUrl +
                                '" target="_blank" style="color: #0d6efd; text-decoration: none; font-weight: 500;">';
                            popupContent += 'Lihat Detail &rarr;';
                            popupContent += '</a>';
                            popupContent += '</div>';
                            popupContent += '</div>';

                            const marker = L.marker([lat, lng], {
                                    icon: icon,
                                    title: kelompok.nama
                                })
                                .bindPopup(popupContent);

                            marker.on('click', function(e) {
                                console.log('Marker clicked!', kelompok.nama, e);
                            });
                            markers.addLayer(marker);
                        }
                    });

                    map.addLayer(markers);

                    // Tambahkan legenda ke peta
                    const legend = L.control({
                        position: 'bottomright'
                    });
                    legend.onAdd = function(map) {
                        const div = L.DomUtil.create('div', 'info legend');
                        div.style.backgroundColor = 'rgba(255,255,255,0.8)';
                        div.style.padding = '10px';
                        div.style.borderRadius = '5px';
                        div.style.fontSize = '12px';
                        div.style.boxShadow = '0 0 5px rgba(0,0,0,0.2)';

                        div.innerHTML += '<h6 style="margin:0 0 5px 0;font-weight:bold;">Jenis Kelompok</h6>';
                        div.innerHTML +=
                            '<div style="display:flex;align-items:center;margin-bottom:5px;"><div style="background-color:#198754;width:12px;height:12px;border-radius:0;transform:rotate(45deg);margin-right:8px;border:1px solid rgba(0,0,0,0.2);"></div>Pertanian</div>';
                        div.innerHTML +=
                            '<div style="display:flex;align-items:center;margin-bottom:5px;"><div style="background-color:#ffc107;width:12px;height:12px;border-radius:3px;margin-right:8px;border:1px solid rgba(0,0,0,0.2);"></div>Peternakan</div>';
                        div.innerHTML +=
                            '<div style="display:flex;align-items:center;"><div style="background-color:#0d6efd;width:12px;height:12px;border-radius:50%;margin-right:8px;border:1px solid rgba(0,0,0,0.2);"></div>Perikanan</div>';

                        return div;
                    };
                    legend.addTo(map);

                    // Fix ukuran peta
                    setTimeout(() => {
                        map.invalidateSize();
                    }, 100);

                } catch (error) {
                    console.error("Error initializing hero map:", error);
                    mapElement.innerHTML =
                        '<p class="text-center text-danger p-5">Terjadi kesalahan saat memuat peta.</p>';
                }
            });

        }
    </script>
@endpush
