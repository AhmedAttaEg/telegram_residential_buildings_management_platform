<x-app-layout :title="__('web.nav.owner_dashboard')" :breadcrumbs="[['label' => __('web.nav.owner_dashboard')]]">
    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        @foreach ($stats as $key => $value)
            <div class="app-card">
                <div class="app-card-body">
                    <p class="text-sm text-stone-500">{{ __("web.stats.{$key}") }}</p>
                    <p class="mt-3 text-3xl font-semibold text-stone-950">{{ number_format($value) }}</p>
                </div>
            </div>
        @endforeach
    </section>

    <section class="mt-6 app-card">
        <div class="app-card-header">
            <h2 class="text-lg font-semibold text-stone-900">{{ __('web.dashboard.recent_tenants') }}</h2>
        </div>

        <div class="app-card-body">
            @if ($recentTenants->isEmpty())
                <x-empty-state />
            @else
                <x-data-table>
                    <thead class="bg-stone-50">
                        <tr>
                            <th class="px-4 py-3 text-start font-semibold text-stone-600">{{ __('web.common.tenant') }}</th>
                            <th class="px-4 py-3 text-start font-semibold text-stone-600">{{ __('web.common.status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($recentTenants as $tenant)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-stone-900">{{ $tenant->name }}</div>
                                    <div class="text-xs text-stone-500">{{ $tenant->slug }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <x-status-badge :value="$tenant->status" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-data-table>
            @endif
        </div>
    </section>
</x-app-layout>
