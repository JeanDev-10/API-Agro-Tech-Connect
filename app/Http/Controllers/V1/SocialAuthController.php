<?php

 namespace App\Http\Controllers\V1;

 use Illuminate\Http\Request;
 use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\SocialAuthRequest;
use App\Http\Responses\V1\ApiResponse;
use App\Models\V1\User;
use App\Repository\V1\Auth\SocialAuthRepository;
use Illuminate\Support\Facades\DB;

 class SocialAuthController extends Controller
 {
     protected $auth;

     public function __construct(protected
     SocialAuthRepository $socialAuthRepository,)
     {
        $this->auth = app('firebase.auth');
     }

     public function loginWithGoogle(SocialAuthRequest $request)
     {
         try {
             return $this->socialAuthRepository->loginWithSocialMedia($request,$this->auth,'google');
         } catch (\Exception $e) {
             DB::rollBack();
             return ApiResponse::error(
                 'Error en autenticación con Google',
                 $e->getMessage(),
                 401
             );

         }
     }
     public function loginWithFacebook(SocialAuthRequest $request)
     {
         try {
            return $this->socialAuthRepository->loginWithSocialMedia($request,$this->auth,'facebook');
         } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error(
                'Error en autenticación con Facebook',
                $e->getMessage(),
                401
            );
         }
     }
 }
