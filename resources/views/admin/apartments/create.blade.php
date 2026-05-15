<x-app-layout :title="'Create Apartment'" :breadcrumbs="[['label' => __('web.nav.admin_dashboard'), 'url' => route('admin.dashboard')], ['label' => 'Apartments', 'url' => route('admin.apartments.index')], ['label' => 'Create']]">
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-stone-900">Create apartment</h2>
    </div>

    <x-alert-messages />

    @include('admin.apartments._form', [
        'apartment' => $apartment,
        'buildings' => $buildings,
        'action' => route('admin.apartments.store'),
        'method' => 'POST',
        'submit' => 'Create apartment',
    ])
</x-app-layout>
