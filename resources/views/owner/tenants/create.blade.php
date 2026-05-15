<x-app-layout :title="'Create Tenant'" :breadcrumbs="[['label' => __('web.nav.owner_dashboard'), 'url' => route('owner.dashboard')], ['label' => 'Tenants', 'url' => route('owner.tenants.index')], ['label' => 'Create']]">
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-stone-900">Create tenant</h2>
    </div>

    <x-alert-messages />

    @include('owner.tenants._form', [
        'tenant' => $tenant,
        'featureDefaults' => $featureDefaults,
        'action' => route('owner.tenants.store'),
        'submit' => 'Create tenant',
        'method' => 'POST',
    ])
</x-app-layout>
