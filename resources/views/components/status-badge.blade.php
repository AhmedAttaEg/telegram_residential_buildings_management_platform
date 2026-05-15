@props(['value'])

@php
    $styles = match ($value) {
        'active', 'open', 'posted' => 'bg-emerald-100 text-emerald-800',
        'trial', 'draft', 'grace' => 'bg-amber-100 text-amber-800',
        'suspended', 'cancelled', 'closed', 'inactive' => 'bg-rose-100 text-rose-800',
        default => 'bg-stone-200 text-stone-700',
    };
@endphp

<span class="{{ $styles }} inline-flex rounded-full px-2.5 py-1 text-xs font-semibold">
    {{ str($value)->replace('_', ' ')->headline() }}
</span>
