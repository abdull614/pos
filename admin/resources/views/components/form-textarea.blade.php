@props([
    'label' => '',
    'name' => '',
    'placeholder' => '',
    'rows' => 3,
    'required' => false,
    'optional' => false,
    'value' => '',  // ← tambah ini
])

<div>
    <label for="{{ $name }}" class="block text-xs font-semibold text-gray-600 mb-1.5">
        {{ $label }}
        @if($required) <span class="text-red-400">*</span> @endif
        @if($optional) <span class="text-gray-300">(opsional)</span> @endif
    </label>

    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => 'w-full px-3.5 py-2.5 text-sm border border-gray-200 bg-gray-50 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition resize-none']) }}
    >{{ old($name, $value) }}</textarea>

    @error($name)
        <p class="mt-1 text-xs text-red-500 flex items-center gap-1">
            <i class="bi bi-exclamation-circle"></i> {{ $message }}
        </p>
    @enderror
</div>