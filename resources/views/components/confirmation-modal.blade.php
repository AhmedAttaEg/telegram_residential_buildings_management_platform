@props(['message'])

<span data-confirm="{{ $message }}" {{ $attributes }}>
    {{ $slot }}
</span>
