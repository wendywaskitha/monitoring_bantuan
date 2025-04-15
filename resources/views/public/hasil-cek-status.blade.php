@extends('layouts.app')

@section('title', 'Hasil Pengecekan Status Pendaftaran')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Hasil Pengecekan Status Pendaftaran</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <p class="mb-0">
                            @if($type === 'telepon')
                                Hasil pencarian pendaftaran untuk nomor telepon: <strong>{{ $identifier }}</strong>
                            @else
                                Hasil pencarian pendaftaran untuk email: <strong>{{ $identifier }}</strong>
                            @endif
                        </p>
                    </div>

                    @if($pendaftarans->isEmpty())
                        <div class="alert alert-warning">
                            <h5 class="alert-heading">Tidak ditemukan pendaftaran!</h5>
                            <p>Maaf, tidak ada pendaftaran kelompok yang ditemukan dengan informasi yang Anda berikan. Pastikan data yang dimasukkan sudah benar atau hubungi administrator jika Anda yakin telah mendaftar.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Kelompok</th>
                                        <th>Jenis</th>
                                        <th>Tanggal Pendaftaran</th>
                                        <th>Status</th>
                                        <th>Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendaftarans as $index => $pendaftaran)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $pendaftaran->nama_kelompok_diajukan }}</td>
                                            <td>{{ $pendaftaran->jenis_kelompok }}</td>
                                            <td>{{ $pendaftaran->created_at->format('d M Y') }}</td>
                                            <td>
                                                @if($pendaftaran->status === 'Baru')
                                                    <span class="badge bg-info">Baru</span>
                                                @elseif($pendaftaran->status === 'Diproses')
                                                    <span class="badge bg-warning">Diproses</span>
                                                @elseif($pendaftaran->status === 'Diterima')
                                                    <span class="badge bg-success">Diterima</span>
                                                @elseif($pendaftaran->status === 'Ditolak')
                                                    <span class="badge bg-danger">Ditolak</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $pendaftaran->status }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($pendaftaran->catatan_verifikasi)
                                                    {{ $pendaftaran->catatan_verifikasi }}
                                                @elseif($pendaftaran->status === 'Diterima' && $pendaftaran->kelompok_baru_id)
                                                    Kelompok telah terdaftar resmi.
                                                    @if($pendaftaran->kelompokBaru)
                                                        <a href="{{ route('kelompok.detail', $pendaftaran->kelompokBaru) }}" class="btn btn-sm btn-outline-primary mt-1">
                                                            Lihat Profil Kelompok
                                                        </a>
                                                    @endif
                                                @elseif($pendaftaran->status === 'Baru')
                                                    Pendaftaran sedang menunggu verifikasi.
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="mt-4">
                        <a href="{{ route('pendaftaran.kelompok.check-status') }}" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left"></i> Kembali ke Form Pencarian
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 