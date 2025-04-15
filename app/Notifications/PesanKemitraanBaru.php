<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

class PesanKemitraanBaru extends Notification implements ShouldQueue
{
    use Queueable;

    protected $pesan;

    public function __construct($pesan)
    {
        $this->pesan = $pesan;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new Mailable())
            ->subject('Permintaan Kemitraan Baru')
            ->markdown('emails.kemitraan.new-request', [
                'pengirim' => $this->pesan->nama_kontak,
                'emailPengirim' => $this->pesan->email_kontak,
                'teleponPengirim' => $this->pesan->telepon_kontak,
                'kelompok' => $this->pesan->nama_kelompok_diajukan,
                'jenisKelompok' => $this->pesan->jenis_kelompok,
                'pesan' => $this->pesan->deskripsi ?? 'Tidak ada pesan tambahan.',
            ]);
    }
}
