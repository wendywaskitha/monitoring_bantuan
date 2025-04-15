{{-- resources/views/components/kelompok-card.blade.php --}}
@props(['kelompok']) {{-- Menerima variabel $kelompok --}}

<div class="card h-100 shadow-sm border-light-subtle"> {{-- h-100 agar tinggi kartu sama --}}
    {{-- Gambar Opsional --}}
    {{-- <img src="{{ $kelompok->foto_terbaru ?? asset('placeholder.jpg') }}" class="card-img-top kelompok-card-img" alt="Foto {{ $kelompok->nama_kelompok }}"> --}}
    {{-- Tambahkan style untuk gambar jika dipakai:
    <style> .kelompok-card-img { height: 180px; object-fit: cover; } </style>
    --}}

    <div class="card-body d-flex flex-column">
        {{-- Jenis Kelompok (Badge) --}}
        <div class="mb-2">
            @php
                $badgeColor = match ($kelompok->jenis_kelompok) {
                    'Pertanian' => 'success',
                    'Peternakan' => 'warning',
                    'Perikanan' => 'info',
                    default => 'secondary',
                };
            @endphp
            <span class="badge bg-{{ $badgeColor }} bg-opacity-75">{{ $kelompok->jenis_kelompok }}</span>
        </div>

        {{-- Nama Kelompok (Link ke Detail) --}}
        <h5 class="card-title fs-6 fw-semibold mb-1">
            {{-- Pastikan route 'kelompok.detail' sudah ada --}}
            @if(Route::has('kelompok.detail'))
            <a href="{{ route('kelompok.detail', $kelompok->id) }}" class="text-decoration-none text-dark stretched-link">
                {{ $kelompok->nama_kelompok }}
            </a>
            @else
             {{ $kelompok->nama_kelompok }} {{-- Tampilkan teks biasa jika route tidak ada --}}
            @endif
        </h5>

        {{-- Lokasi --}}
        <p class="card-text text-muted small mb-2">
            <i class="bi bi-geo-alt-fill me-1"></i>
            {{ $kelompok->desa?->nama_desa ?? 'N/A' }}, Kec. {{ $kelompok->desa?->kecamatan?->nama_kecamatan ?? 'N/A' }}
        </p>

        {{-- Indikator Potensi (Contoh: Total Penjualan) --}}
        <div class="mt-auto pt-2 border-top border-light"> {{-- mt-auto mendorong ke bawah --}}
             <p class="card-text small mb-0">
                <span class="fw-medium">Total Penjualan Dilaporkan:</span><br>
                {{-- Gunakan accessor atau data yang dikirim Controller --}}
                {{-- Pastikan $kelompok memiliki properti/accessor total_hasil_penjualan --}}
                <span class="fs-6 fw-bold text-success">Rp {{ number_format($kelompok->total_hasil_penjualan ?? ($kelompok->total_penjualan_agg ?? 0), 0, ',', '.') }}</span>
             </p>
             {{-- Tambahkan indikator lain jika perlu --}}
        </div>
    </div>
</div>
