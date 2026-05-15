<x-app-layout :title="'Tenants'" :breadcrumbs="[['label' => __('web.nav.owner_dashboard'), 'url' => route('owner.dashboard')], ['label' => 'Tenants']]">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-stone-900">Tenants</h2>
            <p class="text-sm text-stone-500">Manage tenant lifecycle, branding, and enabled features.</p>
        </div>
        <a href="{{ route('owner.tenants.create') }}" class="rounded-xl bg-stone-950 px-4 py-3 text-sm font-semibold text-white">Create tenant</a>
    </div>

    <div class="mb-6 app-card">
        <div class="app-card-body">
            <form method="GET" action="{{ route('owner.tenants.index') }}" class="grid gap-4 md:grid-cols-5">
                <x-form.input :label="'Search'" name="search" :value="$filters['search'] ?? null" />
                <x-form.select :label="'Status'" name="status" :value="$filters['status'] ?? ''" :options="['' => 'Any', 'active' => 'Active', 'suspended' => 'Suspended']" />
                <x-form.select :label="'Subscription'" name="subscription_status" :value="$filters['subscription_status'] ?? ''" :options="['' => 'Any', 'trial' => 'Trial', 'active' => 'Active', 'grace' => 'Grace', 'expired' => 'Expired', 'suspended' => 'Suspended']" />
                <x-form.input :label="'Plan'" name="subscription_plan" :value="$filters['subscription_plan'] ?? null" />
                <div class="flex items-end">
                    <x-form.submit-button class="w-full">Filter</x-form.submit-button>
                </div>
            </form>
        </div>
    </div>

    @if ($tenants->isEmpty())
        <x-empty-state />
    @else
        <x-data-table>
            <thead class="bg-stone-50">
                <tr>
                    <th class="px-4 py-3 text-start font-semibold text-stone-600">Tenant</th>
                    <th class="px-4 py-3 text-start font-semibold text-stone-600">Subscription</th>
                    <th class="px-4 py-3 text-start font-semibold text-stone-600">{{ __('web.common.status') }}</th>
                    <th class="px-4 py-3 text-start font-semibold text-stone-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($tenants as $tenant)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-stone-900">{{ $tenant->name }}</div>
                            <div class="text-xs text-stone-500">{{ $tenant->slug }}</div>
                        </td>
                        <td class="px-4 py-3 text-stone-700">{{ $tenant->subscription_status }} / {{ $tenant->subscription_plan ?: '—' }}</td>
                        <td class="px-4 py-3"><x-status-badge :value="$tenant->status" /></td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-3 text-sm">
                                <a href="{{ route('owner.tenants.show', $tenant) }}" class="font-medium text-stone-900">View</a>
                                <a href="{{ route('owner.tenants.edit', $tenant) }}" class="font-medium text-amber-700">Edit</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$tenants" />
    @endif
</x-app-layout>
