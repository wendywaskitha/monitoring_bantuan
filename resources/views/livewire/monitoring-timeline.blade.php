<div class="mt-6"> {{-- Margin atas --}}
    @if ($riwayatMonitoring->isEmpty())
        <p class="text-center text-gray-500 dark:text-gray-400 py-4">Tidak ada riwayat monitoring lain untuk item bantuan ini.</p>
    @else
        <ol class="relative border-s border-gray-200 dark:border-gray-700 ms-3.5"> {{-- Garis vertikal utama --}}
            @foreach ($riwayatMonitoring as $item)
                <li class="mb-10 ms-8"> {{-- Margin kiri untuk konten, margin bawah antar item --}}
                    {{-- Titik/Ikon pada Timeline --}}
                    <span @class([
                        'absolute flex items-center justify-center w-6 h-6 rounded-full -start-3.5 ring-8 ring-white dark:ring-gray-900',
                        'bg-blue-100 dark:bg-blue-900' => $item->id === $currentMonitoringId, // Warna berbeda jika item saat ini
                        'bg-gray-100 dark:bg-gray-700' => $item->id !== $currentMonitoringId,
                    ])>
                        {{-- Ikon berdasarkan status atau jenis? Contoh: Kalender --}}
                         <x-heroicon-s-calendar-days class="w-3 h-3 text-primary-600 dark:text-primary-400"/>
                    </span>

                    {{-- Konten Item Timeline --}}
                    <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                        {{-- Header: Tanggal & Petugas --}}
                        <div class="items-center justify-between mb-3 sm:flex">
                            <time class="mb-1 text-sm font-semibold text-gray-900 dark:text-white sm:mb-0">
                                {{ $item->tanggal_monitoring->format('d F Y') }}
                                @if($item->id === $currentMonitoringId)
                                    <span class="bg-primary-100 text-primary-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-primary-900 dark:text-primary-300 ms-3">Saat Ini</span>
                                @endif
                            </time>
                            <div class="text-sm font-normal text-gray-500 dark:text-gray-400">
                                Dicatat oleh: {{ $item->user->name ?? 'N/A' }}
                            </div>
                        </div>

                        {{-- Ringkasan/Deskripsi --}}
                        <div class="p-3 text-sm italic font-normal text-gray-500 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 mb-3 whitespace-normal break-words">
                           {{ Str::limit($item->deskripsi_pemanfaatan ?? 'Tidak ada deskripsi.', 150) }}
                        </div>

                        {{-- Detail Spesifik (Contoh: Persentase) --}}
                        @if($item->persentase_perkembangan !== null)
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Perkembangan Umum:
                            @php
                                $percent = $item->persentase_perkembangan;
                                $colorClass = 'bg-gray-50 text-gray-700 ring-gray-600/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20';
                                if ($percent >= 75) $colorClass = 'bg-success-50 text-success-700 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/20';
                                elseif ($percent >= 25) $colorClass = 'bg-warning-50 text-warning-700 ring-warning-600/10 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/20';
                                elseif ($percent >= 0) $colorClass = 'bg-danger-50 text-danger-700 ring-danger-600/10 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/20';
                            @endphp
                            <span class="inline-flex items-center justify-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $colorClass }}">
                                {{ $percent }}%
                            </span>
                        </div>
                        @endif
                        {{-- Anda bisa tambahkan detail spesifik lain di sini --}}


                        {{-- Link ke Detail Lengkap --}}
                        <a href="{{ $this->getDetailUrl($item) }}" target="_blank" class="inline-flex items-center text-sm font-medium text-primary-600 hover:text-primary-800 dark:text-primary-500 dark:hover:text-primary-300 hover:underline">
                            Lihat Detail Lengkap
                            <svg class="w-3 h-3 ms-2 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
                            </svg>
                        </a>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</div>
