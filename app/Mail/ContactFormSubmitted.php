<?php
namespace App\Mail;
use App\Models\PesanKontak; // Import model
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address; // Import Address

class ContactFormSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    // Properti publik untuk data pesan
    public PesanKontak $pesan;

    /**
     * Create a new message instance.
     */
    public function __construct(PesanKontak $pesan)
    {
        $this->pesan = $pesan;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            // Ambil email pengirim dari pesan, gunakan nama pengirim
            from: new Address($this->pesan->email, $this->pesan->nama_lengkap),
            subject: 'Pesan Baru dari Form Kontak: ' . $this->pesan->subjek,
            // Reply-To ke email pengirim agar Admin bisa balas langsung
            replyTo: [
                new Address($this->pesan->email, $this->pesan->nama_lengkap),
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            // Gunakan view Markdown
            markdown: 'emails.contact.submitted',
            // Kirim data $pesan ke view
            with: [
                'pesan' => $this->pesan,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
