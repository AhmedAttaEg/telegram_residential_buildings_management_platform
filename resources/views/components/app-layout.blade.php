@props([
    'title' => __('web.app_name'),
    'breadcrumbs' => [],
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title }}</title>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body>
        <div class="app-shell">
            <div class="flex min-h-screen">
                <x-sidebar />

                <div class="flex min-h-screen flex-1 flex-col">
                    <x-topbar :title="$title" />

                    <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                        @if ($breadcrumbs !== [])
                            <x-breadcrumbs :items="$breadcrumbs" />
                        @endif

                        <x-alert-messages />

                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>
    </body>
</html>
