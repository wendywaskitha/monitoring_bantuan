{{-- resources/views/layouts/partials/navbar.blade.php --}}
{{-- Kurangi padding vertikal navbar (py-2) --}}
<nav id="mainNavbar" class="navbar navbar-expand-lg navbar-light bg-white sticky-top py-2">
    <div class="container px-4 px-lg-5">
        {{-- Brand (Logo & Nama) --}}
        <a class="navbar-brand d-flex align-items-center" href="{{ route('home') }}">
            {{-- Logo (Sesuaikan path & tinggi) --}}
            @if ($logoUrl = setting_asset('logo_path'))
                <img src="{{ $logoUrl }}" alt="Logo {{ setting('app_name') }}" style="max-height: 35px;" class="me-2"> {{-- Tinggi logo dikecilkan --}}
            @endif
            <div>
                {{-- Ukuran font brand dikecilkan (fs-6) --}}
                <span class="fw-bolder fs-6">{{ setting('app_name', config('app.name')) }}</span>
                {{-- Subtitle (opsional, bisa di-uncomment jika tetap ingin ditampilkan kecil) --}}
                @if ($appSubtitle = setting('app_subtitle'))
                    <small class="d-none d-lg-block text-muted" style="font-size: 0.7rem; margin-top: -6px; margin-left: 1px;">{{ $appSubtitle }}</small>
                @endif
            </div>
        </a>
        {{-- Toggler --}}
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
        {{-- Menu --}}
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                {{-- Kurangi padding horizontal (px-lg-2) & ukuran font (small) --}}
                <li class="nav-item"><a class="nav-link small px-lg-2 {{ request()->routeIs('home') || request()->routeIs('peta.potensi') ? 'active' : '' }}" href="{{ route('peta.potensi') }}">Peta Potensi</a></li>
                <li class="nav-item"><a class="nav-link small px-lg-2 {{ request()->routeIs('kelompok.list') ? 'active' : '' }}" href="{{ route('kelompok.list') }}">Daftar Kelompok</a></li>
                <li class="nav-item">
                    <a class="nav-link small px-lg-2 {{ request()->routeIs('pendaftaran.kelompok.create') ? 'active' : '' }}" href="{{ route('pendaftaran.kelompok.create') }}">
                       <i class="bi bi-person-plus-fill me-1"></i> <span class="d-none d-lg-inline">Daftar Baru</span><span class="d-lg-none">Daftar Kelompok Baru</span>
                    </a>
                </li>
                 @auth
                    @if(Auth::user()->hasRole('Admin'))
                        <li class="nav-item">
                            <a class="nav-link small px-lg-2 {{ request()->routeIs('analytics.admin') ? 'active' : '' }}" href="{{ route('analytics.admin') }}">
                                <i class="bi bi-graph-up-arrow me-1"></i> Analitik
                            </a>
                        </li>
                    @endif
                @endauth
                {{-- <li class="nav-item"><a class="nav-link small px-lg-2" href="#">Tentang</a></li> --}}
            </ul>
            {{-- Tombol Kanan (Kurangi margin kiri: ms-lg-2) --}}
            <div class="ms-lg-2 d-flex align-items-center">
                @auth
                    {{-- Sapaan bisa dihilangkan untuk lebih ringkas --}}
                    {{-- <span class="navbar-text me-2 small d-none d-lg-inline">Halo, {{ Str::words(Auth::user()->name, 1, '') }}!</span> --}}
                    <a href="{{ route('filament.admin.pages.dashboard') }}" class="btn btn-sm btn-outline-secondary me-2" title="Admin Panel">
                       <i class="bi bi-speedometer2"></i> <span class="d-none d-md-inline">Admin</span> {{-- Teks lebih singkat --}}
                    </a>
                    <form method="POST" action="{{ route('filament.admin.auth.logout') }}">
                         @csrf
                         <button type="submit" class="btn btn-sm btn-outline-danger" title="Logout">
                             <i class="bi bi-box-arrow-right"></i> <span class="d-none d-md-inline">Logout</span>
                         </button>
                     </form>
                @else
                    <a href="{{ route('filament.admin.auth.login') }}" class="btn btn-sm btn-primary">
                       <i class="bi bi-box-arrow-in-right me-1"></i> Login
                    </a>
                @endauth
            </div>
        </div>
    </div>
</nav>
