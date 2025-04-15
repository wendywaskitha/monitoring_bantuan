<?php

namespace App\Http\Controllers\Frontend;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\PesanKontak; // Model pesan kontak
use Illuminate\Support\Facades\Notification as NotificationFacade;
use App\Models\DistribusiBantuan; // Import jika perlu cek distribusi
use App\Notifications\PesanKemitraanBaru; // Notifikasi email (opsional)

class KemitraanController extends Controller
{
    public function store(Request $request)
    {
        // Validasi input form
        $validator = Validator::make($request->all(), [
            'nama_kontak' => 'required|string|max:255',
            'email_kontak' => 'required|email|max:255',
            'telepon_kontak' => 'required|string|max:20|min:8',
            'pesan' => 'nullable|string|max:2000',
            'kelompok_id' => 'required|exists:kelompoks,id', // Pastikan kelompok ada
            // 'g-recaptcha-response' => 'required|captcha', // Jika pakai CAPTCHA
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Simpan data pesan kemitraan ke tabel pesan_kontaks
            $pesanKemitraan = PesanKontak::create([
                'nama_dokumen' => "Permintaan Kemitraan untuk {$request->input('kelompok_id')}",
                'deskripsi' => $request->input('pesan'),
                'tipe_file' => null, // Tidak ada dokumen pendukung
                'ukuran_file' => null,
                'status' => 'Baru',
                'catatan_verifikasi' => null,
                'verifikator_id' => null,
                'diverifikasi_at' => null,
                'user_id' => Auth::check() ? Auth::id() : null, // Jika user login, simpan user_id
                'dokumen_pendukung_path' => null,
                'dokumen_pendukung_nama_asli' => null,
                'kecamatan_id' => $request->kelompok?->desa?->kecamatan_id, // Ambil dari relasi kelompok
                'desa_id' => $request->kelompok?->desa_id, // Ambil dari relasi kelompok
                'nama_kelompok_diajukan' => $request->kelompok?->nama_kelompok, // Ambil nama kelompok
                'jenis_kelompok' => $request->kelompok?->jenis_kelompok, // Ambil jenis kelompok
                'alamat_singkat' => null, // Alamat tidak diperlukan di modal ini
                'perkiraan_jumlah_anggota' => null, // Jumlah anggota tidak diperlukan
                'kelompok_id' => $request->input('kelompok_id'), // ID kelompok terkait
                'jenis_kelompok' => optional($request->kelompok)->jenis_kelompok, // Jenis kelompok (opsional)
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Kirim notifikasi ke Admin (opsional)
            $admins = User::role('Admin')->get(); // Ambil semua admin
            if ($admins->isNotEmpty()) {
                NotificationFacade::send($admins, new PesanKemitraanBaru($pesanKemitraan));
                Log::info("Notifikasi kemitraan baru berhasil dikirim ke admin.");
            }

            // Redirect dengan pesan sukses
            return redirect()->back()->with('success', 'Permintaan kemitraan Anda telah berhasil terkirim.');
        } catch (\Exception $e) {
            Log::error("Gagal menyimpan permintaan kemitraan: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengirim permintaan kemitraan.');
        }
    }
}
