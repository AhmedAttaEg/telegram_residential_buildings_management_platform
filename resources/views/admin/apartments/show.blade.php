<x-app-layout :title="'Unit '.$apartment->unit_number" :breadcrumbs="[['label' => __('web.nav.admin_dashboard'), 'url' => route('admin.dashboard')], ['label' => 'Apartments', 'url' => route('admin.apartments.index')], ['label' => 'Unit '.$apartment->unit_number]]">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-stone-900">Unit {{ $apartment->unit_number }}</h2>
            <p class="text-sm text-stone-500">{{ $apartment->building?->name }}</p>
        </div>

        <a href="{{ route('admin.apartments.edit', $apartment) }}" class="rounded-xl border border-stone-300 px-4 py-3 text-sm font-semibold text-stone-700">Edit</a>
    </div>

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-5">
        <div class="app-card"><div class="app-card-body"><p class="text-sm text-stone-500">Status</p><div class="mt-3"><x-status-badge :value="$apartment->status" /></div></div></div>
        <div class="app-card"><div class="app-card-body"><p class="text-sm text-stone-500">Occupancy</p><div class="mt-3"><x-status-badge :value="$apartment->occupancy_status" /></div></div></div>
        <div class="app-card"><div class="app-card-body"><p class="text-sm text-stone-500">Bedrooms</p><p class="mt-3 text-2xl font-semibold text-stone-900">{{ $apartment->bedrooms ?? '—' }}</p></div></div>
        <div class="app-card"><div class="app-card-body"><p class="text-sm text-stone-500">Bathrooms</p><p class="mt-3 text-2xl font-semibold text-stone-900">{{ $apartment->bathrooms ?? '—' }}</p></div></div>
        <div class="app-card"><div class="app-card-body"><p class="text-sm text-stone-500">Area</p><p class="mt-3 text-2xl font-semibold text-stone-900">{{ $apartment->area_value ? number_format((float) $apartment->area_value, 2).' '.$apartment->area_unit : '—' }}</p></div></div>
    </div>
</x-app-layout>
