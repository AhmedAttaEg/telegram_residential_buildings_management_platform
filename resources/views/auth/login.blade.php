<x-guest-layout :title="__('web.auth.title')">
    <div class="w-full max-w-md rounded-[2rem] border border-stone-200 bg-white p-8 shadow-xl shadow-stone-300/20">
        <div class="mb-8">
            <h1 class="text-2xl font-semibold text-stone-950">{{ __('web.auth.title') }}</h1>
            <p class="mt-2 text-sm text-stone-500">{{ __('web.auth.subtitle') }}</p>
        </div>

        <x-alert-messages />

        <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
            @csrf

            <x-form.input
                :label="__('web.auth.email')"
                name="email"
                type="email"
                autocomplete="email"
                required
            />

            <x-form.input
                :label="__('web.auth.password')"
                name="password"
                type="password"
                autocomplete="current-password"
                required
            />

            <x-form.submit-button class="w-full">
                {{ __('web.auth.submit') }}
            </x-form.submit-button>
        </form>
    </div>
</x-guest-layout>
