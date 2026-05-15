<x-app-layout :title="'Buildings'" :breadcrumbs="[['label' => __('web.nav.admin_dashboard'), 'url' => route('admin.dashboard')], ['label' => 'Buildings']]">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-stone-900">Buildings</h2>
            <p class="text-sm text-stone-500">Manage tenant building inventory within the current tenant scope.</p>
        </div>
        <a href="{{ route('admin.buildings.create') }}" class="rounded-xl bg-stone-950 px-4 py-3 text-sm font-semibold text-white">Create building</a>
    </div>

    @if ($buildings->isEmpty())
        <x-empty-state />
    @else
        <x-data-table>
            <thead class="bg-stone-50">
                <tr>
                    <th class="px-4 py-3 text-start font-semibold text-stone-600">Building</th>
                    <th class="px-4 py-3 text-start font-semibold text-stone-600">City</th>
                    <th class="px-4 py-3 text-start font-semibold text-stone-600">{{ __('web.common.status') }}</th>
                    <th class="px-4 py-3 text-start font-semibold text-stone-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($buildings as $building)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-stone-900">{{ $building->name }}</div>
                            <div class="text-xs text-stone-500">{{ $building->slug }}</div>
                        </td>
                        <td class="px-4 py-3 text-stone-700">{{ $building->city ?: '—' }}</td>
                        <td class="px-4 py-3"><x-status-badge :value="$building->status" /></td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-3 text-sm">
                                <a href="{{ route('admin.buildings.show', $building) }}" class="font-medium text-stone-900">View</a>
                                <a href="{{ route('admin.buildings.edit', $building) }}" class="font-medium text-amber-700">Edit</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$buildings" />
    @endif
</x-app-layout>
