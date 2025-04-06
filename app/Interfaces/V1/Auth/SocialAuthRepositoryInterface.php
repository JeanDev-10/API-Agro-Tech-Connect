<?php
namespace App\Interfaces\V1\Auth;

use Illuminate\Http\Request;

interface SocialAuthRepositoryInterface{
    public function loginWithSocialMedia(Request $request, $auth,String $socialMedia);
}
