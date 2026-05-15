@props([
    'label',
    'name',
    'options' => [],
    'value' => null,
])

<div class="space-y-2">
    <label for="{{ $name }}" class="block text-sm font-medium text-stone-700">{{ $label }}</label>
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        {{ $attributes->class('block w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-900 shadow-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200') }}
    >
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($name)
        <p class="text-sm text-rose-700">{{ $message }}</p>
    @enderror
</div>
