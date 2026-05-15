<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:ar,en'],
        ]);

        $locale = $validated['locale'];

        $request->session()->put('locale', $locale);

        $user = $request->user();

        if ($user !== null && $user->preferred_locale !== $locale) {
            $user->forceFill([
                'preferred_locale' => $locale,
            ])->save();
        }

        return back();
    }
}
