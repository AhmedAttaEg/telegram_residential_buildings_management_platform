<x-guest-layout :title="__('web.app_name')">
    <div class="grid w-full max-w-5xl gap-8 lg:grid-cols-[1.2fr_0.8fr]">
        <section class="rounded-[2rem] border border-stone-200 bg-white/90 p-8 shadow-xl shadow-stone-300/20 sm:p-10">
            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-amber-600">Laravel + Blade</p>
            <h1 class="mt-4 text-4xl font-semibold tracking-tight text-stone-950 sm:text-5xl">
                {{ __('web.app_name') }}
            </h1>
            <p class="mt-6 max-w-2xl text-base leading-8 text-stone-600">
                Multi-tenant residential building management for platform owners, tenant staff, accountants, maintenance teams, and residents.
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('login') }}" class="rounded-xl bg-stone-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-stone-800">
                    {{ __('web.nav.login') }}
                </a>
                <a href="/api/v1/health" class="rounded-xl border border-stone-300 px-5 py-3 text-sm font-semibold text-stone-700 transition hover:border-stone-400 hover:text-stone-950">
                    API Health
                </a>
            </div>
        </section>

        <section class="app-card overflow-hidden">
            <div class="app-card-header">
                <h2 class="text-lg font-semibold text-stone-900">{{ __('web.nav.dashboard') }}</h2>
            </div>
            <div class="app-card-body space-y-4 text-sm text-stone-600">
                <p>{{ __('web.dashboard.owner_intro') }}</p>
                <p>{{ __('web.dashboard.admin_intro') }}</p>
                <p>{{ __('web.dashboard.resident_intro') }}</p>
            </div>
        </section>
    </div>
</x-guest-layout>
