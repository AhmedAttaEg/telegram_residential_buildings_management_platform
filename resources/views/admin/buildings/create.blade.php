<x-app-layout :title="'Create Building'" :breadcrumbs="[['label' => __('web.nav.admin_dashboard'), 'url' => route('admin.dashboard')], ['label' => 'Buildings', 'url' => route('admin.buildings.index')], ['label' => 'Create']]">
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-stone-900">Create building</h2>
    </div>

    <x-alert-messages />

    @include('admin.buildings._form', [
        'building' => $building,
        'action' => route('admin.buildings.store'),
        'method' => 'POST',
        'submit' => 'Create building',
    ])
</x-app-layout>
