@extends('layouts.app')

@section('title', 'Cek Status Pendaftaran Kelompok')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Cek Status Pendaftaran Kelompok</h4>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <p class="mb-4">Silakan masukkan nomor telepon atau email yang digunakan saat pendaftaran untuk memeriksa status pendaftaran kelompok Anda.</p>

                    <form action="{{ route('pendaftaran.kelompok.show-status') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Pilih Metode Pencarian:</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="telepon" value="telepon" checked>
                                <label class="form-check-label" for="telepon">
                                    Nomor Telepon
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="email" value="email">
                                <label class="form-check-label" for="email">
                                    Email
                                </label>
                            </div>
                            @error('type')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="identifier" class="form-label">Nomor Telepon / Email:</label>
                            <input type="text" class="form-control @error('identifier') is-invalid @enderror" id="identifier" name="identifier" value="{{ old('identifier') }}" required>
                            @error('identifier')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text" id="identifierHelp">Masukkan informasi sesuai dengan yang digunakan saat pendaftaran.</div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Cek Status</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-muted">
                    <a href="{{ route('home') }}" class="text-decoration-none">
                        <i class="bi bi-arrow-left"></i> Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 