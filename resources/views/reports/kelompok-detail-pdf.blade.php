<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kelompok - {{ $kelompok->nama_kelompok }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #333; } /* Gunakan font yg support UTF-8 */
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; vertical-align: top; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .header-table td { border: none; padding: 0; }
        .header-logo { width: 80px; height: auto; } /* Sesuaikan ukuran logo */
        .header-text { text-align: center; }
        .header-text h3, .header-text p { margin: 0; }
        .section-title { font-size: 12pt; font-weight: bold; margin-top: 15px; margin-bottom: 5px; border-bottom: 1px solid #eee; padding-bottom: 3px;}
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .text-success { color: #15803d; } /* Warna hijau */
        .text-warning { color: #b45309; } /* Warna kuning */
        .text-primary { color: #4338ca; } /* Warna primary */
        .footer { position: fixed; bottom: -30px; left: 0px; right: 0px; height: 50px; font-size: 8pt; text-align: center; color: #777; }
        .page-break { page-break-after: always; }
        ul.list-basic { list-style: none; padding-left: 0; margin: 0;}
        ul.list-basic li { margin-bottom: 3px; }
    </style>
</head>
<body>
    {{-- Footer Halaman --}}
    <div class="footer">
        Dicetak pada {{ $tanggalCetak }} - {{ config('app.name') }}
    </div>

    {{-- Header Laporan --}}
    <table class="header-table">
        <tr>
            <td style="width: 15%;">
                {{-- <img src="{{ public_path('images/logo-laporan.png') }}" alt="Logo" class="header-logo"> --}} {{-- Ganti path logo --}}
            </td>
            <td style="width: 70%;" class="header-text">
                <h3>LAPORAN DETAIL KELOMPOK</h3>
                <p>Program Monitoring Bantuan</p>
                {{-- <p>Dinas Terkait - Kabupaten Muna Barat</p> --}}
            </td>
            <td style="width: 15%;"></td>
        </tr>
    </table>
    <hr style="border-top: 2px solid black; margin-bottom: 20px;">

    {{-- Informasi Kelompok --}}
    <div class="section-title">Informasi Kelompok</div>
    <table>
        <tr>
            <th style="width: 30%;">Nama Kelompok</th>
            <td style="width: 70%;">{{ $kelompok->nama_kelompok }}</td>
        </tr>
        <tr>
            <th>Jenis Kelompok</th>
            <td>{{ $kelompok->jenis_kelompok }}</td>
        </tr>
         <tr>
            <th>Ketua Kelompok</th>
            <td>{{ $kelompok->ketua?->nama_anggota ?? 'Belum Ditentukan' }}</td>
        </tr>
        <tr>
            <th>Jumlah Anggota</th>
            <td>{{ $kelompok->anggotas->count() }} Orang</td>
        </tr>
        <tr>
            <th>Desa/Kelurahan</th>
            <td>{{ $kelompok->desa?->nama_desa ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Kecamatan</th>
            <td>{{ $kelompok->desa?->kecamatan?->nama_kecamatan ?? 'N/A' }}</td>
        </tr>
         <tr>
            <th>Alamat Sekretariat</th>
            <td>{{ $kelompok->alamat_sekretariat ?? '-' }}</td>
        </tr>
         <tr>
            <th>Tanggal Dibentuk</th>
            <td>{{ $kelompok->tanggal_dibentuk ? $kelompok->tanggal_dibentuk->format('d F Y') : '-' }}</td>
        </tr>
         <tr>
            <th>Petugas PJ</th>
            <td>{{ $kelompok->petugas?->name ?? 'Belum Ditugaskan' }}</td>
        </tr>
    </table>

    {{-- Ringkasan Kinerja --}}
    <div class="section-title">Ringkasan Kinerja</div>
    <table>
        <tr>
            <th style="width: 70%;">Total Estimasi Bantuan Diterima</th>
            <td style="width: 30%;" class="text-right text-bold text-primary">Rp {{ number_format($totalNilaiBantuan ?? 0, 0, ',', '.') }}</td>
        </tr>
         <tr>
            <th>Total Penjualan Dilaporkan</th>
            <td class="text-right text-bold text-success">Rp {{ number_format($totalPenjualan ?? 0, 0, ',', '.') }}</td>
        </tr>
         <tr>
            <th>Rata-rata Perkembangan Monitoring (%)</th>
            <td class="text-right text-bold text-warning">{{ $avgPerkembangan !== null ? number_format($avgPerkembangan, 1, ',', '.') . '%' : 'N/A' }}</td>
        </tr>
         {{-- Tampilkan Stok Terakhir/Estimasi --}}
         @if($kelompok->jenis_kelompok === 'Peternakan' && $estimasiTernak !== null)
            <tr><th>Estimasi Ternak Saat Ini</th><td class="text-right text-bold">{{ $estimasiTernak }} Ekor</td></tr>
         @elseif($kelompok->jenis_kelompok === 'Perikanan' && $estimasiIkan !== null)
             <tr><th>Estimasi Ikan Saat Ini</th><td class="text-right text-bold">{{ $estimasiIkan }} Ekor</td></tr>
         @elseif($kelompok->jenis_kelompok === 'Pertanian' && $stokBibit !== null)
             <tr><th>Stok Bibit Disimpan</th><td class="text-right text-bold">{{ $stokBibit }} {{ $satuanStokBibit ?? '' }}</td></tr>
         @endif
    </table>

    {{-- Riwayat Bantuan Diterima --}}
    <div class="section-title">Riwayat Bantuan Diterima (Maks 10 Terbaru)</div>
    <table>
        <thead>
            <tr>
                <th style="width: 20%;">Tanggal</th>
                <th style="width: 50%;">Nama Bantuan</th>
                <th style="width: 15%;" class="text-right">Jumlah</th>
                <th style="width: 15%;">Satuan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($kelompok->distribusiBantuans as $distribusi)
                <tr>
                    <td>{{ $distribusi->tanggal_distribusi->format('d/m/Y') }}</td>
                    <td>{{ $distribusi->bantuan->nama_bantuan }}</td>
                    <td class="text-right">{{ number_format($distribusi->jumlah, 0, ',', '.') }}</td>
                    <td>{{ $distribusi->satuan }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center"><i>Belum ada riwayat bantuan diterima.</i></td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Ringkasan Monitoring Terakhir --}}
    <div class="section-title">Ringkasan Monitoring Terakhir (Maks 3)</div>
    @forelse ($kelompok->allMonitoringPemanfaatans as $monitoring)
        <table>
            <tr><th style="width: 30%;">Tanggal Monitoring</th><td>{{ $monitoring->tanggal_monitoring->format('d F Y') }}</td></tr>
            <tr><th>Perkembangan Umum</th><td>{{ $monitoring->persentase_perkembangan ?? 'N/A' }}%</td></tr>
            <tr><th>Deskripsi Umum</th><td>{{ $monitoring->deskripsi_pemanfaatan ?? '-' }}</td></tr>
            {{-- Tampilkan detail spesifik jika ada --}}
            @if($monitoring->jumlah_ternak_saat_ini !== null) <tr><th>Jml Ternak Saat Itu</th><td>{{ $monitoring->jumlah_ternak_saat_ini }} Ekor</td></tr> @endif
            @if($monitoring->jumlah_ikan_hidup !== null) <tr><th>Estimasi Jml Ikan Saat Itu</th><td>{{ $monitoring->jumlah_ikan_hidup }} Ekor</td></tr> @endif
            {{-- ... tambahkan detail spesifik lain ... --}}
            <tr><th>Catatan Petugas</th><td>{{ $monitoring->catatan_monitoring ?? '-' }}</td></tr>
            <tr><th>Dicatat Oleh</th><td>{{ $monitoring->user?->name ?? 'N/A' }}</td></tr>
        </table>
    @empty
        <p style="font-style: italic; color: #777;">Belum ada data monitoring.</p>
    @endforelse

    {{-- Ringkasan Pelaporan Hasil Terakhir --}}
    <div class="section-title">Ringkasan Pelaporan Hasil Terakhir (Maks 3)</div>
     @forelse ($kelompok->allPelaporanHasils as $pelaporan)
        <table>
            <tr><th style="width: 30%;">Tanggal Pelaporan</th><td>{{ $pelaporan->tanggal_pelaporan->format('d F Y') }}</td></tr>
            {{-- Tampilkan detail spesifik hasil jika ada --}}
            @if($pelaporan->hasil_panen_kg !== null) <tr><th>Hasil Panen</th><td>{{ $pelaporan->hasil_panen_kg }} Kg</td></tr> @endif
            @if($pelaporan->jumlah_ternak_terjual !== null) <tr><th>Ternak Terjual</th><td>{{ $pelaporan->jumlah_ternak_terjual }} Ekor</td></tr> @endif
            @if($pelaporan->jumlah_tangkapan_kg !== null) <tr><th>Hasil Tangkapan/Panen</th><td>{{ $pelaporan->jumlah_tangkapan_kg }} Kg</td></tr> @endif
            {{-- Tampilkan data penjualan --}}
            <tr><th>Jumlah Dijual</th><td>{{ $pelaporan->jumlah_dijual ?? '-' }} {{ $pelaporan->satuan_dijual ?? '' }}</td></tr>
            <tr><th>Harga Jual/Satuan</th><td>Rp {{ number_format($pelaporan->harga_jual_per_satuan_dijual ?? 0, 0, ',', '.') }}</td></tr>
            <tr><th>Pendapatan Lain</th><td>Rp {{ number_format($pelaporan->pendapatan_lain ?? 0, 0, ',', '.') }}</td></tr>
            <tr><th>Total Penjualan</th><td class="text-bold">Rp {{ number_format($pelaporan->total_penjualan ?? 0, 0, ',', '.') }}</td></tr>
            <tr><th>Catatan Hasil</th><td>{{ $pelaporan->catatan_hasil ?? '-' }}</td></tr>
            <tr><th>Dilaporkan Oleh</th><td>{{ $pelaporan->user?->name ?? 'N/A' }}</td></tr>
        </table>
    @empty
        <p style="font-style: italic; color: #777;">Belum ada data pelaporan hasil.</p>
    @endforelse

</body>
</html>
