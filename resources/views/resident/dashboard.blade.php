<x-app-layout :title="__('web.nav.resident_dashboard')" :breadcrumbs="[['label' => __('web.nav.resident_dashboard')]]">
    <section class="mb-6 rounded-[2rem] bg-white px-6 py-8 shadow-sm ring-1 ring-stone-200">
        <h2 class="text-2xl font-semibold text-stone-950">{{ __('web.dashboard.welcome', ['name' => $resident->full_name]) }}</h2>
        <p class="mt-2 text-sm text-stone-500">{{ __('web.dashboard.resident_intro') }}</p>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $key => $value)
            <div class="app-card">
                <div class="app-card-body">
                    <p class="text-sm text-stone-500">{{ __("web.stats.{$key}") }}</p>
                    <p class="mt-3 text-3xl font-semibold text-stone-950">
                        @if (str_contains($key, 'balance'))
                            {{ number_format($value, 2) }} {{ __('web.common.currency') }}
                        @else
                            {{ number_format($value) }}
                        @endif
                    </p>
                </div>
            </div>
        @endforeach
    </section>

    <section class="mt-6 app-card">
        <div class="app-card-header">
            <h2 class="text-lg font-semibold text-stone-900">{{ __('web.dashboard.current_apartment') }}</h2>
        </div>

        <div class="app-card-body">
            @if ($apartment === null)
                <x-empty-state :message="__('web.dashboard.no_apartment')" />
            @else
                <dl class="grid gap-4 md:grid-cols-3">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-stone-500">{{ __('web.common.tenant') }}</dt>
                        <dd class="mt-1 text-sm font-medium text-stone-900">{{ $apartment->building?->tenant?->name ?? auth()->user()->tenant?->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-stone-500">Building</dt>
                        <dd class="mt-1 text-sm font-medium text-stone-900">{{ $apartment->building?->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-stone-500">Unit</dt>
                        <dd class="mt-1 text-sm font-medium text-stone-900">{{ $apartment->unit_number }}</dd>
                    </div>
                </dl>
            @endif
        </div>
    </section>
</x-app-layout>
