<x-app-layout :title="__('web.nav.admin_dashboard')" :breadcrumbs="[['label' => __('web.nav.admin_dashboard')]]">
    <section class="mb-6 rounded-[2rem] bg-stone-950 px-6 py-8 text-white">
        <h2 class="text-2xl font-semibold">{{ __('web.dashboard.welcome', ['name' => auth()->user()->name]) }}</h2>
        <p class="mt-2 text-sm text-stone-300">{{ __('web.dashboard.admin_intro') }}</p>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
        @foreach ($stats as $key => $value)
            <div class="app-card">
                <div class="app-card-body">
                    <p class="text-sm text-stone-500">{{ __("web.stats.{$key}") }}</p>
                    <p class="mt-3 text-3xl font-semibold text-stone-950">{{ number_format($value) }}</p>
                </div>
            </div>
        @endforeach
    </section>

    <section class="mt-6 app-card">
        <div class="app-card-header">
            <h2 class="text-lg font-semibold text-stone-900">{{ __('web.dashboard.recent_residents') }}</h2>
        </div>

        <div class="app-card-body">
            @if ($recentResidents->isEmpty())
                <x-empty-state />
            @else
                <x-data-table>
                    <thead class="bg-stone-50">
                        <tr>
                            <th class="px-4 py-3 text-start font-semibold text-stone-600">Resident</th>
                            <th class="px-4 py-3 text-start font-semibold text-stone-600">{{ __('web.common.email') }}</th>
                            <th class="px-4 py-3 text-start font-semibold text-stone-600">{{ __('web.common.status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($recentResidents as $resident)
                            <tr>
                                <td class="px-4 py-3 font-medium text-stone-900">{{ $resident->full_name }}</td>
                                <td class="px-4 py-3 text-stone-600">{{ $resident->email ?: '—' }}</td>
                                <td class="px-4 py-3"><x-status-badge :value="$resident->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-data-table>
            @endif
        </div>
    </section>
</x-app-layout>
