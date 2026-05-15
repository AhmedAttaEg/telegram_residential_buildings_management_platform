<x-app-layout :title="'Edit Subscription Plan'" :breadcrumbs="[['label' => __('web.nav.owner_dashboard'), 'url' => route('owner.dashboard')], ['label' => 'Subscription Plans', 'url' => route('owner.subscription-plans.index')], ['label' => $plan->name]]">
    <div class="mb-6 flex items-center justify-between gap-4">
        <h2 class="text-xl font-semibold text-stone-900">Edit subscription plan</h2>

        <form method="POST" action="{{ route('owner.subscription-plans.destroy', $plan) }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-xl border border-rose-300 px-4 py-3 text-sm font-semibold text-rose-700" data-confirm="Delete this plan?">
                Delete plan
            </button>
        </form>
    </div>

    <x-alert-messages />

    @include('owner.subscription-plans._form', [
        'plan' => $plan,
        'action' => route('owner.subscription-plans.update', $plan),
        'method' => 'PUT',
        'submit' => 'Save changes',
    ])
</x-app-layout>
