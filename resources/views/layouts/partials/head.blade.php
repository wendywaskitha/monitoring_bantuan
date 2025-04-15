<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Gunakan helper setting() untuk title default --}}
    <title>@yield('title', setting('app_name', config('app.name')))</title>
    {{-- Gunakan helper setting_asset() untuk favicon --}}
    <link rel="icon" href="{{ setting_asset('favicon_path', asset('favicon.ico')) }}">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.undefinedapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.undefinedapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Bootstrap 5 CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- Favicon (Ganti dengan path favicon Anda) --}}
    {{-- <link rel="icon" href="{{ asset('favicon.ico') }}"> --}}

    {{-- Stack untuk CSS spesifik halaman --}}
    @stack('styles')

    {{-- CSS Kustom & Animasi --}}
    <style>
        :root {
            /* Definisikan variabel warna utama (Contoh: Indigo) */
            --bs-primary-rgb: 79, 70, 229;
            --bs-primary-darker-rgb: 67, 56, 202;
            --bs-secondary-rgb: 107, 114, 128;
            --bs-success-rgb: 16, 185, 129;
            --bs-info-rgb: 59, 130, 246;
            --bs-warning-rgb: 245, 158, 11;
            --bs-danger-rgb: 239, 68, 68;
            /* Gradient Warna */
            --gradient-start: rgba(var(--bs-primary-rgb), 0.03); /* Warna awal sangat terang */
            --gradient-end: #ffffff; /* Putih */
            --footer-gradient-start: #2a2f34;
            --footer-gradient-end: #212529;
            /* Font */
            --bs-body-font-family: 'Poppins', sans-serif;
            --bs-body-color: #333; /* Warna teks body sedikit lebih gelap */
        }

        /* Kustomisasi Bootstrap Buttons dengan Transisi */
        .btn {
            transition: all 0.2s ease-in-out;
            border-radius: 0.375rem; /* rounded-md */
            font-weight: 500;
            padding: 0.5rem 1rem; /* Padding tombol sedikit lebih besar */
        }
        .btn-sm {
             padding: 0.35rem 0.8rem;
             font-size: 0.875rem;
        }
        .btn-primary {
            --bs-btn-bg: rgb(var(--bs-primary-rgb));
            --bs-btn-border-color: rgb(var(--bs-primary-rgb));
            --bs-btn-hover-bg: rgb(var(--bs-primary-darker-rgb));
            --bs-btn-hover-border-color: rgb(var(--bs-primary-darker-rgb));
            --bs-btn-active-bg: rgb(var(--bs-primary-darker-rgb), 0.9);
            --bs-btn-active-border-color: rgb(var(--bs-primary-darker-rgb), 0.9);
            --bs-btn-focus-shadow-rgb: var(--bs-primary-rgb);
        }
         .btn-primary:hover {
             transform: translateY(-2px);
             box-shadow: 0 4px 10px rgba(var(--bs-primary-rgb), 0.3);
         }
         .btn-outline-primary {
            --bs-btn-color: rgb(var(--bs-primary-rgb));
            --bs-btn-border-color: rgb(var(--bs-primary-rgb));
            --bs-btn-hover-bg: rgb(var(--bs-primary-rgb));
            --bs-btn-hover-color: #fff;
            --bs-btn-active-bg: rgb(var(--bs-primary-darker-rgb));
            --bs-btn-active-border-color: rgb(var(--bs-primary-darker-rgb));
            --bs-btn-active-color: #fff;
             --bs-btn-focus-shadow-rgb: var(--bs-primary-rgb);
        }
         .btn-outline-primary:hover {
             transform: translateY(-2px);
         }

        /* Styling Navbar dengan Transisi Background saat Scroll */
        .navbar {
            transition: background-color 0.4s ease, box-shadow 0.4s ease;
            background-color: rgba(255, 255, 255, 0.9); /* Default agak transparan */
            backdrop-filter: blur(5px);
            box-shadow: none;
            border-bottom: 1px solid transparent; /* Border awal transparan */
        }
        .navbar-scrolled {
            background-color: #ffffff; /* Solid saat scroll */
            box-shadow: 0 2px 10px rgba(0,0,0,.07);
            border-bottom: 1px solid #dee2e6; /* Border muncul saat scroll */
        }
        .navbar-brand img { max-height: 40px; }
        .nav-link { font-weight: 500; transition: color 0.2s ease; }
        .nav-link.active { color: rgb(var(--bs-primary-rgb)) !important; font-weight: 600; }
        .nav-link:hover { color: rgba(var(--bs-primary-rgb), 0.8) !important; }

        /* Styling Footer dengan Gradient */
        .footer {
            background: linear-gradient(to top, var(--footer-gradient-end), var(--footer-gradient-start));
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9em;
        }
        .footer a { color: rgba(255, 255, 255, 0.8); text-decoration: none; transition: color 0.2s ease; }
        .footer a:hover { color: #fff; }

        /* Layout Sticky Footer & Body Gradient */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: linear-gradient(to bottom, var(--gradient-start), var(--gradient-end) 400px); /* Gradient body lebih panjang */
            color: var(--bs-body-color);
        }
        main { flex: 1 0 auto; }
        footer { flex-shrink: 0; }

        /* Styling Peta */
        .leaflet-container { background: #f8f9fa; border-radius: 0.5rem; }

        /* Animasi Fade In Sederhana untuk Konten */
        @keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
        main > section, main > header, main > .container > .row { /* Target elemen utama di konten */
            animation: fadeIn 0.7s ease-out forwards;
            opacity: 0;
        }
        /* Delay berbeda untuk elemen berbeda */
         main > header { animation-delay: 0.1s; } /* Hero section */
         main > section:nth-of-type(1) { animation-delay: 0.2s; } /* Section pertama (filter/peta) */
         main > section:nth-of-type(2) { animation-delay: 0.35s; } /* Section kedua (kartu unggulan) */
         main > .container > .row:not(:first-child) { animation-delay: 0.3s; } /* Baris di detail kelompok */

    </style>
