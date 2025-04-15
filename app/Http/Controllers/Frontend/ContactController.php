<?php
namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\PesanKontak;
use App\Mail\ContactFormSubmitted; // Import Mailable
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail; // Import Mail facade
use Illuminate\Support\Facades\Log; // Import Log
use Illuminate\Support\Facades\Validator; // Import Validator

class ContactController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'nama_lengkap' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'nomor_telepon' => 'nullable|string|max:20',
            'nama_instansi' => 'nullable|string|max:255',
            'subjek' => 'required|string|max:255',
            'pesan' => 'required|string|max:5000',
            // Tambahkan validasi reCAPTCHA jika pakai
            // 'g-recaptcha-response' => 'required|captcha',
        ]);

        if ($validator->fails()) {
            return redirect()->back() // Kembali ke halaman sebelumnya
                     ->withErrors($validator) // Kirim error validasi
                     ->withInput(); // Kirim input lama
        }

        try {
            // 2. Simpan Pesan ke Database
            $pesanKontak = PesanKontak::create($validator->validated());
            Log::info('Pesan kontak baru disimpan: ID ' . $pesanKontak->id);

            // 3. Kirim Email Notifikasi ke Admin
            $adminEmail = config('mail.admin_address', 'admin@example.com'); // Ambil email admin dari config atau set default
            if ($adminEmail) {
                Mail::to($adminEmail)->send(new ContactFormSubmitted($pesanKontak));
                Log::info('Email notifikasi kontak dikirim ke: ' . $adminEmail);
            } else {
                Log::warning('Alamat email admin tidak dikonfigurasi, notifikasi tidak dikirim.');
            }

            // 4. Redirect dengan Pesan Sukses
            return redirect()->back()->with('success', 'Pesan Anda telah berhasil terkirim. Terima kasih!');

        } catch (\Exception $e) {
            Log::error('Gagal menyimpan atau mengirim pesan kontak: ' . $e->getMessage());
            // Redirect dengan Pesan Error Umum
            return redirect()->back()
                     ->with('error', 'Terjadi kesalahan saat mengirim pesan. Silakan coba lagi.')
                     ->withInput();
        }
    }
}
