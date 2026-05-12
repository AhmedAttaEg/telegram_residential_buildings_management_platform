<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\MobileLoginRequest;
use App\Http\Requests\Mobile\MobileRefreshRequest;
use App\Services\MobileAuthService;
use Illuminate\Http\Request;

class MobileAuthController extends Controller
{
    public function __construct(
        private readonly MobileAuthService $mobileAuthService,
    ) {
    }

    public function login(MobileLoginRequest $request)
    {
        return $this->apiSuccess(
            $this->mobileAuthService->login($request->validated(), $request->ip()),
            'Authenticated successfully.',
        );
    }

    public function refresh(MobileRefreshRequest $request)
    {
        return $this->apiSuccess(
            $this->mobileAuthService->refresh($request->user(), $request->user()->currentAccessToken(), $request->validated(), $request->ip()),
            'Token refreshed successfully.',
        );
    }

    public function logout(Request $request)
    {
        $this->mobileAuthService->logoutCurrentDevice($request->user()->currentAccessToken());

        return $this->apiSuccess(null, 'Logged out successfully.');
    }

    public function logoutAll(Request $request)
    {
        $this->mobileAuthService->logoutAllDevices($request->user());

        return $this->apiSuccess(null, 'Logged out from all devices successfully.');
    }
}
