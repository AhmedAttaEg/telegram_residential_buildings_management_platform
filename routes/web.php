<?php

use App\Http\Controllers\Web\AdminDashboardController;
use App\Http\Controllers\Web\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\LocaleController;
use App\Http\Controllers\Web\OwnerDashboardController;
use App\Http\Controllers\Web\ResidentDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('welcome');
})->name('home');

Route::post('/locale', LocaleController::class)->name('locale.update');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('owner')->name('owner.')->middleware('owner')->group(function (): void {
        Route::get('/dashboard', OwnerDashboardController::class)->name('dashboard');
    });

    Route::prefix('admin')->name('admin.')->middleware('tenant.admin')->group(function (): void {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
    });

    Route::prefix('resident')->name('resident.')->middleware('resident.portal')->group(function (): void {
        Route::get('/dashboard', ResidentDashboardController::class)->name('dashboard');
    });
});
