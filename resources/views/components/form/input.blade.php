@props([
    'label',
    'name',
    'type' => 'text',
    'value' => null,
])

<div class="space-y-2">
    <label for="{{ $name }}" class="block text-sm font-medium text-stone-700">{{ $label }}</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        {{ $attributes->class('block w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-900 shadow-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200') }}
    >
    @error($name)
        <p class="text-sm text-rose-700">{{ $message }}</p>
    @enderror
</div>
