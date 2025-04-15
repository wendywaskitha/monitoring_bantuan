{{-- Di dalam resources/views/filament/widgets/follow-up-monitoring-table-widget.blade.php --}}
{{-- Cari bagian header widget --}}
<x-filament-widgets::widget>
    <x-filament::card>
        <div class="flex items-center justify-between gap-8">
            <x-filament::card.heading>
                {{ $this->getHeading() }}
            </x-filament::card.heading>

            {{-- Tambahkan Ikon dan Tooltip di sini --}}
            <div x-data="{ tooltipVisible: false }" class="relative">
                <span x-on:mouseover="tooltipVisible = true" x-on:mouseleave="tooltipVisible = false">
                    <x-heroicon-o-question-mark-circle class="h-5 w-5 text-gray-400 dark:text-gray-500 cursor-help"/>
                </span>
                <div x-show="tooltipVisible"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 translate-y-1"
                     class="absolute z-10 px-3 py-2 text-xs font-medium text-white bg-gray-900 rounded-lg shadow-sm dark:bg-gray-700 -translate-x-1/2 left-1/2 mt-1 w-max max-w-xs"
                     style="display: none;">
                     {{-- Ambil deskripsi dari method atau tulis langsung --}}
                     {{ $this->getHeadingDescription() ?? "Menampilkan bantuan diterima yang belum dimonitor atau monitoring terakhir > {$this->monitoringDueMonths} bulan." }}
                     <div class="tooltip-arrow" data-popper-arrow></div> {{-- Panah tooltip jika pakai Popper/Tippy --}}
                </div>
            </div>
            {{-- Akhir Ikon dan Tooltip --}}

            @if ($filters = $this->getFilterForm())
                <div class="hidden sm:block">
                    {{ $filters }}
                </div>
            @endif
        </div>

        {{-- ... sisa view widget (termasuk render tabel) ... --}}
         {{-- <x-filament::hr /> --}}

        {{-- ... --}}

    </x-filament::card>
</x-filament-widgets::widget>
