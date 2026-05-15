<x-app-layout :title="$building->name" :breadcrumbs="[['label' => __('web.nav.admin_dashboard'), 'url' => route('admin.dashboard')], ['label' => 'Buildings', 'url' => route('admin.buildings.index')], ['label' => $building->name]]">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-stone-900">{{ $building->name }}</h2>
            <p class="text-sm text-stone-500">{{ $building->full_address ?: 'No address recorded.' }}</p>
        </div>

        <a href="{{ route('admin.buildings.edit', $building) }}" class="rounded-xl border border-stone-300 px-4 py-3 text-sm font-semibold text-stone-700">Edit</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="app-card">
            <div class="app-card-body">
                <p class="text-sm text-stone-500">Status</p>
                <div class="mt-3"><x-status-badge :value="$building->status" /></div>
            </div>
        </div>
        <div class="app-card">
            <div class="app-card-body">
                <p class="text-sm text-stone-500">Code</p>
                <p class="mt-3 text-2xl font-semibold text-stone-900">{{ $building->code ?: '—' }}</p>
            </div>
        </div>
        <div class="app-card">
            <div class="app-card-body">
                <p class="text-sm text-stone-500">Apartments</p>
                <p class="mt-3 text-2xl font-semibold text-stone-900">{{ $building->apartments_count }}</p>
            </div>
        </div>
    </div>
</x-app-layout>
