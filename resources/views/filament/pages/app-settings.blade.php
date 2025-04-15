<x-filament-panels::page>
    {{-- Form Pengaturan --}}
    <form wire:submit="save">
        {{ $this->form }}

        {{-- Tombol Aksi Form --}}
        <div class="mt-6">
            <x-filament-panels::form.actions :actions="$this->getFormActions()" />
            {{-- Atau jika getFormActions tidak ada di Page biasa: --}}
            {{-- <x-filament::button type="submit">
            Simpan Perubahan
        </x-filament::button> --}}
        </div>
    </form>

    {{-- Listener untuk refresh halaman jika diperlukan --}}
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('refresh-page', () => {
                window.location.reload();
            });
        });
    </script>
</x-filament-panels::page>
