@props([
    'label' => '',
    'name' => '',
    'options' => [],
    'placeholder' => '-- Pilih --',
    'required' => false,
    'optionValue' => 'id',
    'optionLabel' => 'name',
    'value' => null,  // ← tambah ini
])

<div>
    <label for="{{ $name }}" class="block text-xs font-semibold text-gray-600 mb-1.5">
        {{ $label }}
        @if($required) <span class="text-red-400">*</span> @endif
    </label>

    <select
        id="{{ $name }}"
        name="{{ $name }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => 'w-full px-3.5 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition ' . ($errors->has($name) ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50')]) }}
    >
        <option value="">{{ $placeholder }}</option>
        @foreach ($options as $option)
            <option value="{{ $option[$optionValue] }}"
                @selected(old($name, $value) == $option[$optionValue])>
                {{-- ↑ tambah $value sebagai fallback --}}
                {{ $option[$optionLabel] }}
            </option>
        @endforeach
    </select>

    @error($name)
        <p class="mt-1 text-xs text-red-500 flex items-center gap-1">
            <i class="bi bi-exclamation-circle"></i> {{ $message }}
        </p>
    @enderror
</div>