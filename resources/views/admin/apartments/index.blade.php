<x-app-layout :title="'Apartments'" :breadcrumbs="[['label' => __('web.nav.admin_dashboard'), 'url' => route('admin.dashboard')], ['label' => 'Apartments']]">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-stone-900">Apartments</h2>
            <p class="text-sm text-stone-500">Manage tenant units and occupancy-ready apartment metadata.</p>
        </div>
        <a href="{{ route('admin.apartments.create') }}" class="rounded-xl bg-stone-950 px-4 py-3 text-sm font-semibold text-white">Create apartment</a>
    </div>

    @if ($apartments->isEmpty())
        <x-empty-state />
    @else
        <x-data-table>
            <thead class="bg-stone-50">
                <tr>
                    <th class="px-4 py-3 text-start font-semibold text-stone-600">Apartment</th>
                    <th class="px-4 py-3 text-start font-semibold text-stone-600">Building</th>
                    <th class="px-4 py-3 text-start font-semibold text-stone-600">Occupancy</th>
                    <th class="px-4 py-3 text-start font-semibold text-stone-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($apartments as $apartment)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-stone-900">Unit {{ $apartment->unit_number }}</div>
                            <div class="text-xs text-stone-500">{{ $apartment->unit_type ?: '—' }}</div>
                        </td>
                        <td class="px-4 py-3 text-stone-700">{{ $apartment->building?->name }}</td>
                        <td class="px-4 py-3"><x-status-badge :value="$apartment->occupancy_status" /></td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-3 text-sm">
                                <a href="{{ route('admin.apartments.show', $apartment) }}" class="font-medium text-stone-900">View</a>
                                <a href="{{ route('admin.apartments.edit', $apartment) }}" class="font-medium text-amber-700">Edit</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$apartments" />
    @endif
</x-app-layout>
