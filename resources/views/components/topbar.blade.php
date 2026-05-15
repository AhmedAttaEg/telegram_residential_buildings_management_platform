@props(['title'])

<header class="border-b border-stone-200 bg-white/90 backdrop-blur">
    <div class="flex items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
        <div>
            <h1 class="text-xl font-semibold text-stone-900">{{ $title }}</h1>
            @if (auth()->user()?->tenant !== null)
                <p class="text-sm text-stone-500">{{ auth()->user()->tenant->name }}</p>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <x-locale-switcher />

            <div class="hidden text-end sm:block">
                <p class="text-sm font-medium text-stone-900">{{ auth()->user()?->name }}</p>
                <p class="text-xs text-stone-500">{{ auth()->user()?->email }}</p>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:border-stone-400 hover:text-stone-950">
                    {{ __('web.nav.logout') }}
                </button>
            </form>
        </div>
    </div>
</header>
