<?php

use App\Http\Controllers\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::group( ['middleware' => ["auth:sanctum"]], function(){
    Route::post('auth/logout', [AuthController::class, 'logout']);
});

Route::controller(AuthController::class)->group(function () {
    Route::post('/auth/register', 'register');
    Route::post('/auth/login', 'login');
});
