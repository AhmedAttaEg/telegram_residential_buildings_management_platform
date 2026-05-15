@props([
    'label',
    'name',
    'value' => null,
])

<div class="space-y-2">
    <label for="{{ $name }}" class="block text-sm font-medium text-stone-700">{{ $label }}</label>
    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="4"
        {{ $attributes->class('block w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-900 shadow-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200') }}
    >{{ old($name, $value) }}</textarea>
    @error($name)
        <p class="text-sm text-rose-700">{{ $message }}</p>
    @enderror
</div>
