<x-mail::message>
{{-- Header Email --}}
# Pesan Kontak Baru: {{ $pesan->subjek }}

Halo Admin {{ setting('app_name', config('app.name', 'Monitoring Bantuan')) }},

Anda telah menerima pesan baru melalui form kontak website pada tanggal {{ $pesan->created_at->translatedFormat('l, d F Y H:i') }}.

{{-- Detail Pengirim dalam Panel --}}
<x-mail::panel>
**Detail Pengirim:**<br>
*   **Nama:** {{ $pesan->nama_lengkap }}
*   **Email:** [{{ $pesan->email }}](mailto:{{ $pesan->email }}) {{-- Buat email bisa diklik --}}
@if($pesan->nomor_telepon)
*   **Telepon:** {{ $pesan->nomor_telepon }}
@endif
@if($pesan->nama_instansi)
*   **Instansi:** {{ $pesan->nama_instansi }}
@endif
</x-mail::panel>

{{-- Isi Pesan --}}
**Isi Pesan:**
<x-mail::panel>
{{-- Gunakan nl2br untuk format paragraf --}}
{!! nl2br(e($pesan->pesan)) !!}
</x-mail::panel>

{{-- Tombol Aksi (Opsional) --}}
<x-mail::button :url="route('filament.admin.resources.pesan-kontaks.view', $pesan)" color="primary"> {{-- Ganti route jika resource beda --}}
Lihat Pesan di Admin Panel
</x-mail::button>

Anda dapat membalas email ini secara langsung untuk merespon pengirim.

Terima kasih,<br>
Sistem {{ setting('app_name', config('app.name', 'Monitoring Bantuan')) }}

{{-- Subcopy (Opsional) --}}
<x-slot:subcopy>
Jika Anda kesulitan melihat tombol di atas, salin dan tempel URL berikut ke browser Anda:
<span class="break-all">[{{ route('filament.admin.resources.pesan-kontaks.view', $pesan) }}]({{ route('filament.admin.resources.pesan-kontaks.view', $pesan) }})</span>
</x-slot>
</x-mail::message>
