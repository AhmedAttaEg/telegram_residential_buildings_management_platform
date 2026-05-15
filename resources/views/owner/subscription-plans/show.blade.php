<x-app-layout :title="$plan->name" :breadcrumbs="[['label' => __('web.nav.owner_dashboard'), 'url' => route('owner.dashboard')], ['label' => 'Subscription Plans', 'url' => route('owner.subscription-plans.index')], ['label' => $plan->name]]">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-stone-900">{{ $plan->name }}</h2>
            <p class="text-sm text-stone-500">{{ $plan->slug }}</p>
        </div>

        <a href="{{ route('owner.subscription-plans.edit', $plan) }}" class="rounded-xl border border-stone-300 px-4 py-3 text-sm font-semibold text-stone-700">Edit</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="app-card">
            <div class="app-card-header">
                <h3 class="text-lg font-semibold text-stone-900">Plan summary</h3>
            </div>
            <div class="app-card-body grid gap-4 md:grid-cols-2">
                <div>
                    <p class="text-xs uppercase tracking-wide text-stone-500">Status</p>
                    <div class="mt-2"><x-status-badge :value="$plan->status" /></div>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-stone-500">Billing cycle</p>
                    <p class="mt-2 text-sm font-medium text-stone-900">{{ str($plan->billing_cycle)->headline() }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-stone-500">Price</p>
                    <p class="mt-2 text-sm font-medium text-stone-900">{{ number_format((float) $plan->price_amount, 2) }} {{ $plan->currency }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-stone-500">Trial days</p>
                    <p class="mt-2 text-sm font-medium text-stone-900">{{ $plan->trial_days ?? '—' }}</p>
                </div>
            </div>
        </div>

        <div class="app-card">
            <div class="app-card-header">
                <h3 class="text-lg font-semibold text-stone-900">Feature limits</h3>
            </div>
            <div class="app-card-body">
                @if ($plan->feature_limits === null)
                    <x-empty-state :message="'No feature limits defined for this plan.'" />
                @else
                    <pre class="overflow-x-auto rounded-xl bg-stone-950 p-4 text-xs text-stone-100">{{ json_encode($plan->feature_limits, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
