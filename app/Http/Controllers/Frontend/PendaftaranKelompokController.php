<?php
namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Kecamatan;
use App\Models\Desa;
use App\Models\PendaftaranKelompok;
use App\Models\User; // Untuk notifikasi
use App\Notifications\PendaftaranKelompokBaru; // Buat notifikasi ini
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification as NotificationFacade; // Facade Notifikasi
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PendaftaranKelompokController extends Controller
{
    // Menampilkan form pendaftaran
    public function create()
    {
        $kecamatans = Kecamatan::orderBy('nama_kecamatan')->pluck('nama_kecamatan', 'id');
        // Desa akan diambil via AJAX/Fetch API
        return view('public.pendaftaran-kelompok', compact('kecamatans'));
    }

    // Menyimpan data pendaftaran
    public function store(Request $request)
    {
        // Validasi (sesuaikan aturan)
        $validator = Validator::make($request->all(), [
            'nama_kelompok_diajukan' => 'required|string|max:255',
            'jenis_kelompok' => 'required|in:Pertanian,Peternakan,Perikanan',
            'kecamatan_id' => 'required|exists:kecamatans,id',
            'desa_id' => 'required|exists:desas,id,kecamatan_id,' . $request->input('kecamatan_id'), // Validasi desa ada di kecamatan terpilih
            'alamat_singkat' => 'nullable|string|max:500',
            'perkiraan_jumlah_anggota' => 'nullable|integer|min:1',
            'deskripsi_kegiatan' => 'nullable|string|max:2000',
            'nama_kontak' => 'required|string|max:255',
            'telepon_kontak' => 'required|string|max:20',
            'email_kontak' => 'nullable|email|max:255',
            'dokumen_pendukung' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120', // Max 5MB
            // 'g-recaptcha-response' => 'required|captcha', // Jika pakai captcha
        ]);

        if ($validator->fails()) {
            return redirect()->route('pendaftaran.kelompok.create') // Kembali ke form
                        ->withErrors($validator)
                        ->withInput();
        }

        $validatedData = $validator->validated();
        $dokumenPath = null;
        $dokumenNamaAsli = null;

        // Handle file upload
        if ($request->hasFile('dokumen_pendukung') && $request->file('dokumen_pendukung')->isValid()) {
            $file = $request->file('dokumen_pendukung');
            $filename = uniqid('doc_pendukung_') . '.' . $file->getClientOriginalExtension();
            $dokumenPath = $file->storeAs('dokumen-pendaftaran', $filename, 'public'); // Simpan ke storage/app/public/dokumen-pendaftaran
            $dokumenNamaAsli = $file->getClientOriginalName();
            if (!$dokumenPath) {
                    return redirect()->route('pendaftaran.kelompok.create')->with('error', 'Gagal mengupload dokumen.')->withInput();
            }
        }

        try {
            // Simpan ke database
            PendaftaranKelompok::create([
                'nama_kelompok_diajukan' => $validatedData['nama_kelompok_diajukan'],
                'jenis_kelompok' => $validatedData['jenis_kelompok'],
                'kecamatan_id' => $validatedData['kecamatan_id'],
                'desa_id' => $validatedData['desa_id'],
                'alamat_singkat' => $validatedData['alamat_singkat'] ?? null,
                'perkiraan_jumlah_anggota' => $validatedData['perkiraan_jumlah_anggota'] ?? null,
                'deskripsi_kegiatan' => $validatedData['deskripsi_kegiatan'] ?? null,
                'nama_kontak' => $validatedData['nama_kontak'],
                'telepon_kontak' => $validatedData['telepon_kontak'],
                'email_kontak' => $validatedData['email_kontak'] ?? null,
                'dokumen_pendukung_path' => $dokumenPath,
                'dokumen_pendukung_nama_asli' => $dokumenNamaAsli,
                'status' => 'Baru', // Status awal
            ]);

            // Kirim Notifikasi ke Admin
            $admins = User::role('Admin')->get(); // Ambil semua admin
            if ($admins->isNotEmpty()) {
                // Buat Notifikasi: php artisan make:notification PendaftaranKelompokBaru
                // Implementasikan notifikasi (via 'mail', 'database')
                // NotificationFacade::send($admins, new PendaftaranKelompokBaru($pendaftaranBaru));
                Log::info('Notifikasi pendaftaran baru seharusnya dikirim ke admin.');
            }

            // Redirect ke halaman sukses atau kembali dengan pesan sukses
            return redirect()->route('pendaftaran.kelompok.create')->with('success', 'Pendaftaran kelompok Anda telah berhasil dikirim dan akan segera kami proses. Terima kasih!');

        } catch (\Exception $e) {
            Log::error('Gagal menyimpan pendaftaran kelompok: ' . $e->getMessage());
            // Hapus file yg terlanjur diupload jika DB gagal
            if ($dokumenPath && Storage::disk('public')->exists($dokumenPath)) {
                Storage::disk('public')->delete($dokumenPath);
            }
            return redirect()->route('pendaftaran.kelompok.create')->with('error', 'Terjadi kesalahan saat mengirim pendaftaran. Silakan coba lagi.')->withInput();
        }
    }

    // // Method untuk mengambil desa berdasarkan kecamatan (untuk AJAX/Fetch)
    // public function getDesaByKecamatan(Request $request, Kecamatan $kecamatan)
    // {
    //     if (!$request->ajax()) {
    //         abort(404);
    //     }
    //     $desas = $kecamatan->desas()->orderBy('nama_desa')->pluck('nama_desa', 'id');
    //     return response()->json($desas);
    // }

    /**
     * Mengambil daftar Desa berdasarkan ID Kecamatan untuk API.
     *
     * @param Kecamatan $kecamatan Instance Kecamatan dari Route Model Binding (atau bisa juga ID biasa)
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDesaByKecamatan(Kecamatan $kecamatan) // Gunakan Route Model Binding
    {
        // Ambil desa yang berelasi dengan kecamatan ini
        // Urutkan berdasarkan nama dan ambil hanya ID dan Nama Desa
        $desas = $kecamatan->desas() // Akses relasi 'desas' di model Kecamatan
                           ->orderBy('nama_desa', 'asc')
                           ->pluck('nama_desa', 'id'); // Hasil: [desa_id => nama_desa, ...]

        // Kembalikan sebagai JSON
        return response()->json($desas);
    }

    // Alternatif jika tidak menggunakan Route Model Binding:
    /*
    public function getDesaByKecamatan(Request $request, $kecamatanId)
    {
        // Validasi $kecamatanId jika perlu
        $kecamatan = Kecamatan::find($kecamatanId);
        if (!$kecamatan) {
            return response()->json([], 404); // Kirim array kosong jika kecamatan tidak ditemukan
        }
        $desas = $kecamatan->desas()->orderBy('nama_desa')->pluck('nama_desa', 'id');
        return response()->json($desas);
    }
    */

    /**
     * Menampilkan halaman untuk pengecekan status pendaftaran kelompok
     *
     * @return \Illuminate\View\View
     */
    public function checkStatus()
    {
        return view('public.cek-status-pendaftaran');
    }

    /**
     * Mencari dan menampilkan status pendaftaran kelompok berdasarkan email atau telepon
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function showStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string',
            'type' => 'required|in:telepon,email',
        ]);

        if ($validator->fails()) {
            return redirect()->route('pendaftaran.kelompok.check-status')
                ->withErrors($validator)
                ->withInput();
        }

        $type = $request->input('type');
        $identifier = $request->input('identifier');

        // Cari pendaftaran berdasarkan telepon atau email
        $query = PendaftaranKelompok::query();
        
        if ($type === 'telepon') {
            $query->where('telepon_kontak', $identifier);
        } else {
            $query->where('email_kontak', $identifier);
        }

        $pendaftarans = $query->latest()->get();

        return view('public.hasil-cek-status', compact('pendaftarans', 'type', 'identifier'));
    }
}
