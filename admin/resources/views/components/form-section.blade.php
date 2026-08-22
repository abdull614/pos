@props([
    'title' => '',
])

<div class="border-t border-gray-100 pt-1">
    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">
        {{ $title }}
    </p>
    {{ $slot }}
</div>