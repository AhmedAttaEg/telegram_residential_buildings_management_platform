<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = config('app.supported_locales', ['ar', 'en']);

        $locale = $request->session()->get('locale');
        $userLocale = $request->user()?->preferred_locale;

        if (! is_string($locale) || ! in_array($locale, $supportedLocales, true)) {
            $locale = is_string($userLocale) && in_array($userLocale, $supportedLocales, true)
                ? $userLocale
                : config('app.locale');
        }

        app()->setLocale($locale);
        $request->session()->put('locale', $locale);

        return $next($request);
    }
}
