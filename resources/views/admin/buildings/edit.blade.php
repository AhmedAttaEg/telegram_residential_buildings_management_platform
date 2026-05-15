<x-app-layout :title="'Edit Building'" :breadcrumbs="[['label' => __('web.nav.admin_dashboard'), 'url' => route('admin.dashboard')], ['label' => 'Buildings', 'url' => route('admin.buildings.index')], ['label' => $building->name]]">
    <div class="mb-6 flex items-center justify-between gap-4">
        <h2 class="text-xl font-semibold text-stone-900">Edit building</h2>

        <form method="POST" action="{{ route('admin.buildings.destroy', $building) }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-xl border border-rose-300 px-4 py-3 text-sm font-semibold text-rose-700" data-confirm="Delete this building?">
                Delete building
            </button>
        </form>
    </div>

    <x-alert-messages />

    @include('admin.buildings._form', [
        'building' => $building,
        'action' => route('admin.buildings.update', $building),
        'method' => 'PUT',
        'submit' => 'Save changes',
    ])
</x-app-layout>
