<?php
namespace App\Interfaces\V1\Auth;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

interface AuthRepositoryInterface{
    public function login(Request $request);
    public function register(Request $request);
    public function userProfile();
    public function logout();
    public function sendVerificationEmail(Request $request);
    public function verifyEmail(EmailVerificationRequest $request);
    public function forgot_password(Request $request);
    public function reset_password(Request $request);

}
