{{-- resources/views/public/kelompok-list.blade.php --}}
@extends('layouts.app')

@section('title', 'Daftar Kelompok Binaan')

@section('content')
<div class="container py-5 px-4 px-lg-5">
    <h1 class="mb-4 fw-bold">Daftar Kelompok Binaan</h1>

    {{-- Form Filter --}}
    <form action="{{ route('kelompok.list') }}" method="GET" class="mb-4">
        <div class="row g-3 align-items-end bg-light p-3 rounded border">
            <div class="col-md-3">
                <label for="listFilterKecamatan" class="form-label form-label-sm">Kecamatan</label>
                <select class="form-select form-select-sm" id="listFilterKecamatan" name="kecamatan">
                    <option value="">Semua Kecamatan</option>
                    @foreach($kecamatans ?? [] as $id => $nama)
                        <option value="{{ $id }}" {{ request('kecamatan') == $id ? 'selected' : '' }}>{{ $nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="listFilterJenis" class="form-label form-label-sm">Jenis Kelompok</label>
                <select class="form-select form-select-sm" id="listFilterJenis" name="jenis">
                     <option value="">Semua Jenis</option>
                     <option value="Pertanian" {{ request('jenis') == 'Pertanian' ? 'selected' : '' }}>Pertanian</option>
                     <option value="Peternakan" {{ request('jenis') == 'Peternakan' ? 'selected' : '' }}>Peternakan</option>
                     <option value="Perikanan" {{ request('jenis') == 'Perikanan' ? 'selected' : '' }}>Perikanan</option>
                </select>
            </div>
             <div class="col-md-3">
                 <label for="listSearch" class="form-label form-label-sm">Cari Nama Kelompok</label>
                <input type="text" class="form-control form-control-sm" id="listSearch" name="search" value="{{ request('search') }}" placeholder="Masukkan nama...">
            </div>
            <div class="col-md-3 d-flex">
                <button type="submit" class="btn btn-primary btn-sm me-2 flex-grow-1">Filter/Cari</button>
                <a href="{{ route('kelompok.list') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </div>
    </form>

    {{-- Grid Kartu Kelompok --}}
    <div class="row gx-4 gx-lg-5 row-cols-1 row-cols-md-2 row-cols-xl-3 justify-content-start"> {{-- Ganti justify-content-center --}}
        @forelse ($kelompoks as $kelompok)
            <div class="col mb-5">
                <x-kelompok-card :kelompok="$kelompok" />
            </div>
        @empty
            <div class="col-12">
                <p class="text-center text-muted py-5">Tidak ada kelompok ditemukan sesuai filter.</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination Links --}}
    <div class="d-flex justify-content-center mt-4">
        {{-- Render pagination menggunakan view Bootstrap 5 --}}
        {{ $kelompoks->links('pagination::bootstrap-5') }}
    </div>

</div>
@endsection
