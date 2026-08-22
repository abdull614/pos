@props([
    'href' => null,
    'icon' => null,
    'label' => '',
    'type' => 'button',
    'variant' => 'primary', // primary | secondary | danger | icon-warning | icon-danger | icon-emerald
])

@php
$styles = match($variant) {
    'primary'      => 'flex items-center gap-2 text-sm font-semibold px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm',
    'secondary'    => 'flex items-center gap-2 text-sm font-semibold px-5 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-600',
    'danger'       => 'flex items-center gap-2 text-sm font-semibold px-5 py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white shadow-sm',
    'icon-warning' => 'w-8 h-8 flex items-center justify-center rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100',
    'icon-danger'  => 'w-8 h-8 flex items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-100',
    'icon-emerald' => 'w-8 h-8 flex items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100',
    default        => 'flex items-center gap-2 text-sm font-semibold px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm',
};
@endphp

@if($href)
    <a href="{{ $href }}" title="{{ $label }}"
       {{ $attributes->merge(['class' => 'transition ' . $styles]) }}>
        @if($icon) <i class="bi {{ $icon }}"></i> @endif
        @if(!str_starts_with($variant, 'icon')) {{ $label }} @endif
    </a>
@else
    <button type="{{ $type }}" title="{{ $label }}"
            {{ $attributes->merge(['class' => 'transition ' . $styles]) }}>
        @if($icon) <i class="bi {{ $icon }}"></i> @endif
        @if(!str_starts_with($variant, 'icon')) {{ $label }} @endif
    </button>
@endif