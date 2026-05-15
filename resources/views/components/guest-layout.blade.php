<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? __('web.app_name') }}</title>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body>
        <div class="min-h-screen bg-[radial-gradient(circle_at_top,_#fef3c7,_#f5f5f4_55%)]">
            <div class="mx-auto flex min-h-screen max-w-6xl flex-col px-6 py-8">
                <div class="mb-8 flex items-center justify-between gap-4">
                    <div>
                        <a href="{{ route('home') }}" class="text-lg font-semibold text-stone-900">{{ __('web.app_name') }}</a>
                    </div>

                    <x-locale-switcher />
                </div>

                <main class="flex flex-1 items-center justify-center">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
