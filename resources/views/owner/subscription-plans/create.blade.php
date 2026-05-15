<x-app-layout :title="'Create Subscription Plan'" :breadcrumbs="[['label' => __('web.nav.owner_dashboard'), 'url' => route('owner.dashboard')], ['label' => 'Subscription Plans', 'url' => route('owner.subscription-plans.index')], ['label' => 'Create']]">
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-stone-900">Create subscription plan</h2>
    </div>

    <x-alert-messages />

    @include('owner.subscription-plans._form', [
        'plan' => $plan,
        'action' => route('owner.subscription-plans.store'),
        'submit' => 'Create plan',
        'method' => 'POST',
    ])
</x-app-layout>
