<x-app-layout :title="'Subscription Plans'" :breadcrumbs="[['label' => __('web.nav.owner_dashboard'), 'url' => route('owner.dashboard')], ['label' => 'Subscription Plans']]">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-stone-900">Subscription plans</h2>
            <p class="text-sm text-stone-500">Manage pricing, billing cycles, and plan defaults.</p>
        </div>
        <a href="{{ route('owner.subscription-plans.create') }}" class="rounded-xl bg-stone-950 px-4 py-3 text-sm font-semibold text-white">Create plan</a>
    </div>

    @if ($plans->isEmpty())
        <x-empty-state />
    @else
        <x-data-table>
            <thead class="bg-stone-50">
                <tr>
                    <th class="px-4 py-3 text-start font-semibold text-stone-600">Plan</th>
                    <th class="px-4 py-3 text-start font-semibold text-stone-600">Billing</th>
                    <th class="px-4 py-3 text-start font-semibold text-stone-600">Price</th>
                    <th class="px-4 py-3 text-start font-semibold text-stone-600">{{ __('web.common.status') }}</th>
                    <th class="px-4 py-3 text-start font-semibold text-stone-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($plans as $plan)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-stone-900">{{ $plan->name }}</div>
                            <div class="text-xs text-stone-500">{{ $plan->slug }}</div>
                        </td>
                        <td class="px-4 py-3 text-stone-700">{{ str($plan->billing_cycle)->headline() }}</td>
                        <td class="px-4 py-3 text-stone-700">{{ number_format((float) $plan->price_amount, 2) }} {{ $plan->currency }}</td>
                        <td class="px-4 py-3"><x-status-badge :value="$plan->status" /></td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-3 text-sm">
                                <a href="{{ route('owner.subscription-plans.show', $plan) }}" class="font-medium text-stone-900">View</a>
                                <a href="{{ route('owner.subscription-plans.edit', $plan) }}" class="font-medium text-amber-700">Edit</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$plans" />
    @endif
</x-app-layout>
