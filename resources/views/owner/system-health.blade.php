<x-app-layout :title="'System Health'" :breadcrumbs="[['label' => __('web.nav.owner_dashboard'), 'url' => route('owner.dashboard')], ['label' => 'System Health']]">
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-stone-900">System health</h2>
        <p class="text-sm text-stone-500">Operational summary for the current environment.</p>
    </div>

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($health as $section => $details)
            <div class="app-card">
                <div class="app-card-header">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold text-stone-900">{{ str($section)->replace('_', ' ')->headline() }}</h3>
                        <x-status-badge :value="$details['status']" />
                    </div>
                </div>
                <div class="app-card-body space-y-3 text-sm text-stone-600">
                    @foreach ($details as $key => $value)
                        @continue($key === 'status')
                        <div>
                            <p class="text-xs uppercase tracking-wide text-stone-500">{{ str($key)->replace('_', ' ')->headline() }}</p>
                            <p class="mt-1 text-sm font-medium text-stone-900">{{ is_scalar($value) ? $value : json_encode($value) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>
