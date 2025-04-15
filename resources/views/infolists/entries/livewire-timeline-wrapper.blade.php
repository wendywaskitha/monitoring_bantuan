{{-- resources/views/infolists/entries/livewire-timeline-wrapper.blade.php --}}
@php
    $record = $getRecord();
    $distribusiBantuanId = $record?->distribusi_bantuan_id;
    $currentMonitoringId = $record?->id;
    $livewireKey = 'timeline-'.$distribusiBantuanId; // Simpan key
@endphp

<div class="filament-infolists-component-ctn py-4">
    @if($distribusiBantuanId)
        {{-- Wrapper untuk loading state --}}
        <div wire:key="wrapper-{{ $livewireKey }}"> {{-- wire:init bisa dipakai jika load data perlu dipicu --}}

            {{-- Indikator Loading --}}
            <div wire:loading wire:target="loadTimelineData"> {{-- Target ke method load jika ada --}}
                 <div class="flex items-center justify-center space-x-2 rtl:space-x-reverse rounded-lg border border-gray-300 dark:border-gray-600 p-4 bg-gray-50 dark:bg-gray-800/50">
                    <x-filament::loading-indicator class="h-6 w-6 text-primary-500" />
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Memuat riwayat timeline...
                    </span>
                </div>
            </div>

            {{-- Komponen Livewire (muncul setelah loading selesai) --}}
            <div wire:loading.remove wire:target="loadTimelineData">
                 @livewire('monitoring-timeline', [
                    'distribusiBantuanId' => $distribusiBantuanId,
                    'currentMonitoringId' => $currentMonitoringId
                ], key($livewireKey))
            </div>
        </div>
    @else
        {{-- Placeholder jika ID tidak ada --}}
        <div class="flex items-center justify-center space-x-2 rtl:space-x-reverse rounded-lg border border-gray-300 dark:border-gray-600 p-4 bg-gray-50 dark:bg-gray-800/50">
            <x-heroicon-o-information-circle class="h-6 w-6 text-gray-400" />
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                Data distribusi bantuan tidak ditemukan untuk menampilkan riwayat.
            </span>
        </div>
    @endif
</div>
