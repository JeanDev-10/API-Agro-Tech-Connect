<?php

use App\Http\Controllers\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('/auth/register', 'register');
});
