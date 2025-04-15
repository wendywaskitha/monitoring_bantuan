@component('mail::message')
# Permintaan Kemitraan Baru

Halo Admin,

Anda menerima permintaan kemitraan baru untuk kelompok **{{ $kelompok }}** (Jenis: **{{ $jenisKelompok }}**).

**Detail Pengirim:**
* Nama: {{ $pengirim }}
* Email: [{{ $emailPengirim }}](mailto:{{ $emailPengirim }})
* Telepon: {{ $teleponPengirim }}

**Pesan:**
@if(!empty($pesan))
{{ $pesan }}
@else
Pengirim tidak menambahkan pesan.
@endif

Untuk tindak lanjut, silakan kunjungi halaman detail pendaftaran di admin panel.

Terima kasih,<br>
Sistem {{ setting('app_name', config('app.name')) }}
@endcomponent
