@props([
    'title' => __('web.common.empty'),
    'message' => null,
])

<div class="rounded-2xl border border-dashed border-stone-300 bg-white px-6 py-12 text-center">
    <h3 class="text-base font-semibold text-stone-900">{{ $title }}</h3>
    @if ($message !== null)
        <p class="mt-2 text-sm text-stone-500">{{ $message }}</p>
    @endif
</div>
