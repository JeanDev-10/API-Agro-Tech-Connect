<?php

use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\SocialAuthController;
use App\Http\Controllers\V1\UserController;
use App\Http\Middleware\V1\EmailVerification;
use App\Http\Middleware\V1\ThrottleRecoveryPasswords;
use App\Http\Middleware\V1\ThrottleVerificationEmails;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ["auth:sanctum"]], function () {

    Route::controller(AuthController::class)->group(function () {
        Route::post('auth/logout',  'logout');
        Route::post('/email/verify/send', 'sendVerificationEmail')->middleware(ThrottleVerificationEmails::class);

        Route::get('/email/verify/{id}/{hash}', 'verifyEmail')
            ->middleware(['signed'])
            ->name('verification.verify');
    });

    // middleware for email verification
    Route::group(['middleware' => [EmailVerification::class]], function () {
        Route::get('user/profile',  [AuthController::class,'userProfile']);
        Route::put('me/password',  [UserController::class,'changePassword'])->middleware('permission:user.change-password');;

    });
});

Route::controller(AuthController::class)->group(function () {
    Route::post('/auth/register', 'register');
    Route::post('/auth/login', 'login');
    Route::post('password/forgot', 'forgot_password')->middleware(ThrottleRecoveryPasswords::class);
    Route::post('password/reset',  'reset_password');
});
Route::controller(SocialAuthController::class)->group(function () {
    Route::post('/auth/login/google', 'loginWithGoogle');
    Route::post('/auth/login/facebook', 'loginWithFacebook');
});



