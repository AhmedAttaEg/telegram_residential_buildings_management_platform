<?php

namespace App\Http\Controllers\Web\Owner;

use App\Http\Controllers\Controller;
use App\Services\SystemHealthService;
use Illuminate\View\View;

class SystemHealthController extends Controller
{
    public function __construct(
        private readonly SystemHealthService $systemHealthService,
    ) {
    }

    public function __invoke(): View
    {
        return view('owner.system-health', [
            'health' => $this->systemHealthService->summary(),
        ]);
    }
}
