<?php

namespace App\Http\Controllers; // Sesuaikan namespace

use App\Models\Kelompok;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf; // Import facade PDF
use Illuminate\Support\Facades\App; // Untuk set locale

class ReportController extends Controller
{
    /**
     * Generate PDF Laporan Detail Kelompok.
     */
    public function generateKelompokPdf(Kelompok $kelompok)
    {
        // Set locale ke Indonesia untuk format tanggal/angka
        App::setLocale('id');

        // Eager load data yang dibutuhkan untuk laporan PDF
        $kelompok->load([
            'desa.kecamatan',
            'ketua',
            'petugas',
            'anggotas' => fn($q) => $q->limit(10)->orderByRaw("FIELD(id, {$kelompok->ketua_id}) DESC")->orderBy('nama_anggota'), // Batasi anggota
            'distribusiBantuans' => function ($q) {
                $q->with('bantuan')->where('status_pemberian', 'Diterima Kelompok')->latest('tanggal_distribusi')->limit(10); // Batasi riwayat bantuan
            },
            'allMonitoringPemanfaatans' => fn($q) => $q->latest('tanggal_monitoring')->limit(3), // Ambil 3 monitoring terakhir
            'allPelaporanHasils' => fn($q) => $q->latest('tanggal_pelaporan')->limit(3), // Ambil 3 pelaporan terakhir
        ]);

        // Ambil data agregat (gunakan accessor jika ada)
        $totalNilaiBantuan = $kelompok->total_nilai_bantuan_diterima ?? 0;
        $totalPenjualan = $kelompok->total_hasil_penjualan ?? 0;
        $avgPerkembangan = $kelompok->rata_rata_perkembangan_terakhir;
        $estimasiTernak = $kelompok->estimasi_jumlah_ternak_saat_ini ?? null;
        $estimasiIkan = $kelompok->estimasi_jumlah_ikan_saat_ini ?? null;
        $stokBibit = $kelompok->jumlah_stok_bibit_terakhir ?? null;
        $satuanStokBibit = $kelompok->satuan_stok_bibit_terakhir ?? null;

        // Siapkan data untuk dikirim ke view PDF
        $data = [
            'kelompok' => $kelompok,
            'totalNilaiBantuan' => $totalNilaiBantuan,
            'totalPenjualan' => $totalPenjualan,
            'avgPerkembangan' => $avgPerkembangan,
            'estimasiTernak' => $estimasiTernak,
            'estimasiIkan' => $estimasiIkan,
            'stokBibit' => $stokBibit,
            'satuanStokBibit' => $satuanStokBibit,
            'tanggalCetak' => now()->translatedFormat('d F Y'), // Tanggal cetak format Indonesia
        ];

        // Load view Blade untuk PDF dengan data
        $pdf = Pdf::loadView('reports.kelompok-detail-pdf', $data);

        // Atur orientasi (opsional)
        // $pdf->setPaper('a4', 'landscape');

        // Tampilkan PDF di browser (inline) atau download
        // return $pdf->stream('laporan-kelompok-' . $kelompok->id . '-' . date('Ymd') . '.pdf'); // Tampilkan di browser
        return $pdf->download('laporan-kelompok-' . Str::slug($kelompok->nama_kelompok) . '-' . date('Ymd') . '.pdf'); // Langsung download
    }
}
