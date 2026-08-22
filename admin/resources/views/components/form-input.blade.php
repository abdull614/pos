@props([
    'label' => '',
    'name' => '',
    'type' => 'text',
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'prefix' => null,
    'readonly' => false,  // ← tambah ini
])

<div>
    <label for="{{ $name }}" class="block text-xs font-semibold text-gray-600 mb-1.5">
        {{ $label }}
        @if($required) <span class="text-red-400">*</span> @endif
    </label>

    <div class="relative">
        @if($prefix)
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-gray-400">
                {{ $prefix }}
            </span>
        @endif

        <input
            type="{{ $type }}"
            id="{{ $name }}"
            name="{{ $name }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            {{ $readonly ? 'readonly' : '' }}
            {{ $attributes->merge(['class' => 'w-full py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition '
                . ($prefix ? 'pl-9 pr-3.5' : 'px-3.5')
                . ($readonly ? ' bg-gray-100 text-gray-500 cursor-not-allowed' : ($errors->has($name) ? ' border-red-400 bg-red-50' : ' border-gray-200 bg-gray-50'))
            ]) }}
        >
    </div>

    @error($name)
        <p class="mt-1 text-xs text-red-500 flex items-center gap-1">
            <i class="bi bi-exclamation-circle"></i> {{ $message }}
        </p>
    @enderror
</div>