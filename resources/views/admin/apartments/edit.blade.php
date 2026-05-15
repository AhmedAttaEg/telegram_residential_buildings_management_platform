<x-app-layout :title="'Edit Apartment'" :breadcrumbs="[['label' => __('web.nav.admin_dashboard'), 'url' => route('admin.dashboard')], ['label' => 'Apartments', 'url' => route('admin.apartments.index')], ['label' => 'Unit '.$apartment->unit_number]]">
    <div class="mb-6 flex items-center justify-between gap-4">
        <h2 class="text-xl font-semibold text-stone-900">Edit apartment</h2>

        <form method="POST" action="{{ route('admin.apartments.destroy', $apartment) }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-xl border border-rose-300 px-4 py-3 text-sm font-semibold text-rose-700" data-confirm="Delete this apartment?">
                Delete apartment
            </button>
        </form>
    </div>

    <x-alert-messages />

    @include('admin.apartments._form', [
        'apartment' => $apartment,
        'buildings' => $buildings,
        'action' => route('admin.apartments.update', $apartment),
        'method' => 'PUT',
        'submit' => 'Save changes',
    ])
</x-app-layout>
