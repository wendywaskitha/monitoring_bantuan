{{-- resources/views/filament/resources/pendaftaran-kelompok-resource/widgets/pendaftaran-kelompok-stats-overview.blade.php --}}
<x-filament-widgets::widget>
    {{-- Panggil komponen stats overview bawaan --}}
    <x-filament::stats :stats="$this->getStats()" :columns="$this->getColumns()" />

    {{-- Tambahkan CSS Kustom di sini --}}
    <style>
        /* Target spesifik widget ini menggunakan ID atau class unik jika perlu */
        /* Atau target umum untuk semua stats overview card */
        .filament-stats-overview-widget-card {
            padding: 0.75rem 1rem; /* Kurangi padding (default mungkin p-6) */
        }

        .filament-stats-overview-widget-card .text-3xl { /* Target ukuran font default angka */
            font-size: 1.5rem; /* Kecilkan ukuran angka (misal: text-2xl) */
            line-height: 2rem;
            /* Anda bisa sesuaikan lebih lanjut */
        }

        .filament-stats-overview-widget-card .text-sm { /* Target ukuran font default label/deskripsi */
            font-size: 0.75rem; /* Kecilkan ukuran label/deskripsi (text-xs) */
            line-height: 1rem;
        }

         /* Kecilkan ikon jika perlu */
        .filament-stats-overview-widget-card .h-8.w-8 { /* Ukuran ikon default? Cek inspect element */
             height: 1.5rem; /* h-6 */
             width: 1.5rem; /* w-6 */
        }
         .filament-stats-overview-widget-card .p-3 { /* Padding background ikon */
              padding: 0.5rem; /* p-2 */
         }

    </style>
</x-filament-widgets::widget>
