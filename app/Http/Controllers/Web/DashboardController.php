<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\WebDashboardResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly WebDashboardResolver $dashboardResolver,
    ) {
    }

    public function __invoke(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return redirect()->to($this->dashboardResolver->pathFor($user));
    }
}
