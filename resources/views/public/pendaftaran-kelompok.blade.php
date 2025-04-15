{{-- resources/views/public/pendaftaran-kelompok.blade.php --}}
@extends('layouts.app') {{-- Gunakan layout utama frontend --}}

@section('title', 'Pendaftaran Kelompok Baru')

@push('styles')
    {{-- Tambahkan style khusus jika perlu --}}
    <style>
        .form-section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #dee2e6;
        }

        .form-control-sm,
        .form-select-sm {
            font-size: 0.9rem;
            /* Sedikit lebih besar dari default sm */
        }

        /* Styling untuk pesan error/sukses */
        .alert {
            font-size: 0.9rem;
        }
    </style>
@endpush

@section('content')
    <div class="container py-5 px-4 px-lg-5">
        <div class="row justify-content-center">
            <div class="col-lg-9 col-xl-8"> {{-- Lebar form --}}

                <h1 class="h2 fw-bold text-center mb-4">Formulir Pendaftaran Kelompok Binaan Baru</h1>
                <p class="text-center text-muted mb-5">Isi formulir di bawah ini untuk mendaftarkan kelompok Anda. Tim kami
                    akan segera melakukan verifikasi.</p>

                {{-- Tampilkan Pesan Sukses/Error --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i> Anda dapat memeriksa status pendaftaran kelompok Anda kapan saja melalui <a href="{{ route('pendaftaran.kelompok.check-status') }}">halaman cek status</a> dengan menggunakan nomor telepon atau email yang terdaftar.
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                {{-- Tampilkan Error Validasi --}}
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <h6 class="alert-heading fw-bold">Oops! Ada kesalahan:</h6>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li><small>{{ $error }}</small></li>
                            @endforeach
                        </ul>
                    </div>
                @endif


                <form action="{{ route('pendaftaran.kelompok.store') }}" method="POST" enctype="multipart/form-data"
                    class="needs-validation" novalidate>
                    @csrf

                    {{-- Informasi Kelompok --}}
                    <h2 class="form-section-title">Informasi Kelompok</h2>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="nama_kelompok_diajukan" class="form-label">Nama Kelompok Diajukan <span
                                    class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control form-control-sm @error('nama_kelompok_diajukan') is-invalid @enderror"
                                id="nama_kelompok_diajukan" name="nama_kelompok_diajukan"
                                value="{{ old('nama_kelompok_diajukan') }}" required>
                            @error('nama_kelompok_diajukan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="jenis_kelompok" class="form-label">Jenis Kelompok <span
                                    class="text-danger">*</span></label>
                            <select class="form-select form-select-sm @error('jenis_kelompok') is-invalid @enderror"
                                id="jenis_kelompok" name="jenis_kelompok" required>
                                <option value="" selected disabled>-- Pilih Jenis --</option>
                                <option value="Pertanian" {{ old('jenis_kelompok') == 'Pertanian' ? 'selected' : '' }}>
                                    Pertanian</option>
                                <option value="Peternakan" {{ old('jenis_kelompok') == 'Peternakan' ? 'selected' : '' }}>
                                    Peternakan</option>
                                <option value="Perikanan" {{ old('jenis_kelompok') == 'Perikanan' ? 'selected' : '' }}>
                                    Perikanan</option>
                            </select>
                            @error('jenis_kelompok')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="kecamatan_id" class="form-label">Kecamatan <span
                                    class="text-danger">*</span></label>
                            <select class="form-select form-select-sm @error('kecamatan_id') is-invalid @enderror"
                                id="kecamatan_id" name="kecamatan_id" required>
                                <option value="" selected disabled>-- Pilih Kecamatan --</option>
                                @foreach ($kecamatans ?? [] as $id => $nama)
                                    <option value="{{ $id }}"
                                        {{ old('kecamatan_id') == $id ? 'selected' : '' }}>{{ $nama }}</option>
                                @endforeach
                            </select>
                            @error('kecamatan_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="desa_id" class="form-label">Desa/Kelurahan <span
                                    class="text-danger">*</span></label>
                            <select class="form-select form-select-sm @error('desa_id') is-invalid @enderror" id="desa_id"
                                name="desa_id" required disabled> {{-- Awalnya disabled --}}
                                <option value="" selected disabled>-- Pilih Kecamatan Dulu --</option>
                                {{-- Opsi desa akan diisi oleh JavaScript --}}
                                {{-- Jika ada old input desa, tampilkan sebagai opsi terpilih sementara --}}
                                @if (old('desa_id'))
                                    <option value="{{ old('desa_id') }}" selected>
                                        {{ old('nama_desa_lama', 'Desa Terpilih Sebelumnya') }}</option>
                                @endif
                            </select>
                            @error('desa_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            {{-- Input hidden untuk menyimpan nama desa lama (jika perlu) --}}
                            <input type="hidden" name="nama_desa_lama" id="nama_desa_lama"
                                value="{{ old('nama_desa_lama') }}">
                        </div>
                        <div class="col-12">
                            <label for="alamat_singkat" class="form-label">Alamat Singkat / Dusun</label>
                            <input type="text"
                                class="form-control form-control-sm @error('alamat_singkat') is-invalid @enderror"
                                id="alamat_singkat" name="alamat_singkat" value="{{ old('alamat_singkat') }}"
                                placeholder="Contoh: Dusun Mekar Jaya RT 01/RW 02">
                            @error('alamat_singkat')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="perkiraan_jumlah_anggota" class="form-label">Perkiraan Jumlah Anggota</label>
                            <input type="number"
                                class="form-control form-control-sm @error('perkiraan_jumlah_anggota') is-invalid @enderror"
                                id="perkiraan_jumlah_anggota" name="perkiraan_jumlah_anggota"
                                value="{{ old('perkiraan_jumlah_anggota') }}" min="1">
                            @error('perkiraan_jumlah_anggota')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label for="deskripsi_kegiatan" class="form-label">Deskripsi Singkat Kegiatan/Tujuan</label>
                            <textarea class="form-control form-control-sm @error('deskripsi_kegiatan') is-invalid @enderror" id="deskripsi_kegiatan"
                                name="deskripsi_kegiatan" rows="3"
                                placeholder="Jelaskan secara singkat kegiatan utama atau tujuan pembentukan kelompok...">{{ old('deskripsi_kegiatan') }}</textarea>
                            @error('deskripsi_kegiatan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Informasi Kontak --}}
                    <h2 class="form-section-title mt-5">Informasi Kontak Penanggung Jawab</h2>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="nama_kontak" class="form-label">Nama Lengkap Kontak <span
                                    class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control form-control-sm @error('nama_kontak') is-invalid @enderror"
                                id="nama_kontak" name="nama_kontak" value="{{ old('nama_kontak') }}" required>
                            @error('nama_kontak')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="telepon_kontak" class="form-label">Nomor Telepon Aktif <span
                                    class="text-danger">*</span></label>
                            <input type="tel"
                                class="form-control form-control-sm @error('telepon_kontak') is-invalid @enderror"
                                id="telepon_kontak" name="telepon_kontak" value="{{ old('telepon_kontak') }}" required>
                            @error('telepon_kontak')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="email_kontak" class="form-label">Alamat Email (Opsional)</label>
                            <input type="email"
                                class="form-control form-control-sm @error('email_kontak') is-invalid @enderror"
                                id="email_kontak" name="email_kontak" value="{{ old('email_kontak') }}">
                            @error('email_kontak')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Upload Dokumen (Opsional) --}}
                    <h2 class="form-section-title mt-5">Dokumen Pendukung (Opsional)</h2>
                    <div class="mb-4">
                        <label for="dokumen_pendukung" class="form-label">Upload Dokumen (PDF, Word, JPG, PNG - Maks
                            5MB)</label>
                        <input class="form-control form-control-sm @error('dokumen_pendukung') is-invalid @enderror"
                            type="file" id="dokumen_pendukung" name="dokumen_pendukung">
                        <div class="form-text">Contoh: Daftar Anggota Awal, Draf AD/ART, dll.</div>
                        @error('dokumen_pendukung')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- CAPTCHA (Jika pakai Anthropic reCAPTCHA v2) --}}
                    {{-- <div class="mb-4">
                      {!! NoCaptcha::display(['data-theme' => 'light']) !!} {{-- Sesuaikan tema --}}
                    {{-- @error('g-recaptcha-response') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                 </div> --}}

                    {{-- Tombol Submit --}}
                    <div class="d-grid">
                        <button class="btn btn-primary btn-lg" type="submit">Kirim Pendaftaran</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Script untuk mengisi dropdown desa berdasarkan kecamatan
        document.addEventListener('DOMContentLoaded', function() {
            const kecamatanSelect = document.getElementById('kecamatan_id');
            const desaSelect = document.getElementById('desa_id');
            const namaDesaLamaInput = document.getElementById(
            'nama_desa_lama'); // Untuk menyimpan nama desa saat validasi gagal

            if (kecamatanSelect && desaSelect) {
                // Fungsi untuk mengambil dan mengisi desa
                const fetchDesa = (kecamatanId, selectedDesaId = null) => {
                    // Kosongkan dan disable desa select
                    desaSelect.innerHTML = '<option value="" selected disabled>Memuat desa...</option>';
                    desaSelect.disabled = true;
                    namaDesaLamaInput.value = ''; // Reset nama desa lama

                    if (!kecamatanId) {
                        desaSelect.innerHTML =
                            '<option value="" selected disabled>-- Pilih Kecamatan Dulu --</option>';
                        return; // Keluar jika tidak ada kecamatan dipilih
                    }

                    // Ganti URL ini dengan route API Anda
                    const apiUrl = `/api/desa-by-kecamatan/${kecamatanId}`;

                    fetch(apiUrl)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            desaSelect.innerHTML =
                                '<option value="" selected disabled>-- Pilih Desa/Kelurahan --</option>'; // Reset opsi
                            let foundSelected = false;
                            for (const id in data) {
                                if (data.hasOwnProperty(id)) {
                                    const option = new Option(data[id], id);
                                    // Jika ada old input atau ID yang cocok, tandai sebagai selected
                                    if (selectedDesaId && id == selectedDesaId) {
                                        option.selected = true;
                                        foundSelected = true;
                                        namaDesaLamaInput.value = data[id]; // Simpan nama desa lama
                                    }
                                    desaSelect.add(option);
                                }
                            }
                            // Jika ada old input tapi ID nya tidak ditemukan di opsi baru (misal karena ganti kecamatan), tambahkan opsi lama
                            if (selectedDesaId && !foundSelected && namaDesaLamaInput.value) {
                                const oldOption = new Option(namaDesaLamaInput.value + ' (Kec. Berbeda?)',
                                    selectedDesaId);
                                oldOption.selected = true;
                                desaSelect.add(oldOption, 1); // Tambahkan di posisi kedua
                            }

                            desaSelect.disabled = false; // Aktifkan kembali
                        })
                        .catch(error => {
                            console.error('Error fetching desa:', error);
                            desaSelect.innerHTML =
                                '<option value="" selected disabled>Gagal memuat desa</option>';
                            // desaSelect.disabled = true; // Biarkan disabled jika error
                        });
                };

                // Panggil fetchDesa saat kecamatan berubah
                kecamatanSelect.addEventListener('change', function() {
                    fetchDesa(this.value);
                });

                // Panggil fetchDesa saat halaman load jika ada kecamatan yang sudah terpilih (misal karena validasi gagal)
                const initialKecamatanId = kecamatanSelect.value;
                const initialDesaId = "{{ old('desa_id') }}"; // Ambil old input desa ID
                if (initialKecamatanId) {
                    fetchDesa(initialKecamatanId, initialDesaId);
                } else {
                    desaSelect.innerHTML = '<option value="" selected disabled>-- Pilih Kecamatan Dulu --</option>';
                    desaSelect.disabled = true;
                }
            }
        });

        // Script untuk validasi Bootstrap (opsional, bisa dihapus jika tidak perlu)
        // (function () {
        //   'use strict'
        //   var forms = document.querySelectorAll('.needs-validation')
        //   Array.prototype.slice.call(forms)
        //     .forEach(function (form) {
        //       form.addEventListener('submit', function (event) {
        //         if (!form.checkValidity()) {
        //           event.preventDefault()
        //           event.stopPropagation()
        //         }
        //         form.classList.add('was-validated')
        //       }, false)
        //     })
        // })()
    </script>
@endpush
