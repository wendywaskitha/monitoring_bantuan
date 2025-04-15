{{-- resources/views/filament/tables/columns/bulleted-list.blade.php --}}
@php
    // $getState() akan mengembalikan collection nama kelompok dari state() di ViewColumn
    $items = $getState();
@endphp

@if ($items && $items->isNotEmpty())
    <ul class="list-disc list-inside text-xs space-y-1">
        @foreach ($items as $item)
            <li>{{ $item }}</li> {{-- Helper e() tidak perlu di sini karena state sudah di-escape jika perlu --}}
        @endforeach
    </ul>
@else
    <span class="text-xs text-gray-500 dark:text-gray-400">-</span>
@endif
