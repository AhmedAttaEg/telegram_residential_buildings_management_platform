<x-app-layout :title="$tenant->name" :breadcrumbs="[['label' => __('web.nav.owner_dashboard'), 'url' => route('owner.dashboard')], ['label' => 'Tenants', 'url' => route('owner.tenants.index')], ['label' => $tenant->name]]">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-stone-900">{{ $tenant->name }}</h2>
            <p class="text-sm text-stone-500">{{ $tenant->slug }}</p>
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('owner.tenants.edit', $tenant) }}" class="rounded-xl border border-stone-300 px-4 py-3 text-sm font-semibold text-stone-700">Edit</a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <section class="space-y-6">
            <div class="app-card">
                <div class="app-card-header">
                    <h3 class="text-lg font-semibold text-stone-900">Tenant summary</h3>
                </div>
                <div class="app-card-body grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-stone-500">Status</p>
                        <div class="mt-2"><x-status-badge :value="$tenant->status" /></div>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-stone-500">Subscription</p>
                        <p class="mt-2 text-sm font-medium text-stone-900">{{ $tenant->subscription_status }} / {{ $tenant->subscription_plan ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-stone-500">Brand</p>
                        <p class="mt-2 text-sm font-medium text-stone-900">{{ $tenant->brand_name ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-stone-500">Primary color</p>
                        <p class="mt-2 text-sm font-medium text-stone-900">{{ $tenant->primary_color ?: '—' }}</p>
                    </div>
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-header">
                    <h3 class="text-lg font-semibold text-stone-900">Feature flags</h3>
                </div>
                <div class="app-card-body grid gap-3 md:grid-cols-2">
                    @foreach ($features as $feature => $enabled)
                        <div class="flex items-center justify-between rounded-xl border border-stone-200 px-4 py-3">
                            <span class="font-medium text-stone-800">{{ str($feature)->replace('_', ' ')->headline() }}</span>
                            <x-status-badge :value="$enabled ? 'active' : 'inactive'" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="space-y-6">
            <div class="app-card">
                <div class="app-card-header">
                    <h3 class="text-lg font-semibold text-stone-900">Lifecycle actions</h3>
                </div>
                <div class="app-card-body space-y-4">
                    <form method="POST" action="{{ route('owner.tenants.status', $tenant) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="activate">
                        <x-form.submit-button class="w-full">Activate tenant</x-form.submit-button>
                    </form>

                    <form method="POST" action="{{ route('owner.tenants.status', $tenant) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="suspend">
                        <x-form.input :label="'Suspension reason'" name="reason" />
                        <button type="submit" class="w-full rounded-xl border border-rose-300 px-4 py-3 text-sm font-semibold text-rose-700" data-confirm="Suspend this tenant?">
                            Suspend tenant
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
