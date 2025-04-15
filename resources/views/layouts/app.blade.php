<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light"> {{-- Default light theme --}}

<head>
    @include('layouts.partials.head')
</head>

<body>

    {{-- Sertakan Navbar --}}
    @include('layouts.partials.navbar')

    {{-- Konten Utama Halaman --}}
    <main>
        @yield('content')
    </main>

    {{-- Sertakan Footer --}}
    @include('layouts.partials.footer')


    {{-- Sertakan Scripts --}}
    @include('layouts.partials.scripts')

</body>

</html>
