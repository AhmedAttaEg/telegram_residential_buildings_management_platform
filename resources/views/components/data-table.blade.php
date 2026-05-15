<div class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table {{ $attributes->class('min-w-full divide-y divide-stone-200 text-sm') }}>
            {{ $slot }}
        </table>
    </div>
</div>
