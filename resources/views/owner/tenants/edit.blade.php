<x-app-layout :title="'Edit Tenant'" :breadcrumbs="[['label' => __('web.nav.owner_dashboard'), 'url' => route('owner.dashboard')], ['label' => 'Tenants', 'url' => route('owner.tenants.index')], ['label' => $tenant->name]]">
    <div class="mb-6 flex items-center justify-between gap-4">
        <h2 class="text-xl font-semibold text-stone-900">Edit tenant</h2>

        <form method="POST" action="{{ route('owner.tenants.destroy', $tenant) }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-xl border border-rose-300 px-4 py-3 text-sm font-semibold text-rose-700" data-confirm="Delete this tenant?">
                Delete tenant
            </button>
        </form>
    </div>

    <x-alert-messages />

    @include('owner.tenants._form', [
        'tenant' => $tenant,
        'featureDefaults' => $featureDefaults,
        'action' => route('owner.tenants.update', $tenant),
        'method' => 'PUT',
        'submit' => 'Save changes',
    ])
</x-app-layout>
