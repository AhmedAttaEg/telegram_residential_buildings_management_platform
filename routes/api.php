<?php

use Illuminate\Support\Facades\Route;

Route::prefix(config('api.version', 'v1'))
    ->name('api.v1.')
    ->group(base_path('routes/api_v1.php'));

Route::name('api.legacy.')
    ->group(base_path('routes/api_v1.php'));
