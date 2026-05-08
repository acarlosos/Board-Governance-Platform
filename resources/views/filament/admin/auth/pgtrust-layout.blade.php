@php
    $livewire ??= null;
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <div class="bgp-login-root">
        {{ $slot }}
    </div>
</x-filament-panels::layout.base>
