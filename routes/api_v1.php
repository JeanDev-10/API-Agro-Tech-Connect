<?php

use App\Http\Controllers\V1\AuthController;
use App\Http\Middleware\V1\EmailVerification;
use App\Http\Middleware\V1\ThrottleVerificationEmails;
use Illuminate\Support\Facades\Route;

Route::group( ['middleware' => ["auth:sanctum"]], function(){

    Route::controller(AuthController::class)->group(function () {
        Route::get('user/profile',  'userProfile')->middleware(EmailVerification::class);
        Route::post('auth/logout',  'logout');
        Route::post('/email/verify/send', 'sendVerificationEmail')->middleware(ThrottleVerificationEmails::class);

        Route::get('/email/verify/{id}/{hash}', 'verifyEmail')
        ->middleware(['signed'])
        ->name('verification.verify');
    });
});

Route::controller(AuthController::class)->group(function () {
    Route::post('/auth/register', 'register');
    Route::post('/auth/login', 'login');
});
