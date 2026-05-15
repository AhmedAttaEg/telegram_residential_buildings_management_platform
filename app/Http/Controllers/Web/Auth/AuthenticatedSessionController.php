<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\LoginRequest;
use App\Support\WebDashboardResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly WebDashboardResolver $dashboardResolver,
    ) {
    }

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);

        if (! Auth::attempt($credentials, false)) {
            return back()
                ->withInput($request->safe()->only('email'))
                ->withErrors([
                    'email' => __('web.auth.invalid_credentials'),
                ]);
        }

        $request->session()->regenerate();

        /** @var \App\Models\User $user */
        $user = $request->user();

        if (! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->safe()->only('email'))
                ->withErrors([
                    'email' => __('web.auth.inactive_user'),
                ]);
        }

        if ($user->preferred_locale !== null) {
            $request->session()->put('locale', $user->preferred_locale);
        }

        return redirect()->intended($this->dashboardResolver->pathFor($user));
    }

    public function destroy(\Illuminate\Http\Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', __('web.auth.logged_out'));
    }
}
