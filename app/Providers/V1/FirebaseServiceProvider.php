<?php

 namespace App\Providers\V1;

 use Illuminate\Support\ServiceProvider;
 use Kreait\Firebase\Factory;

 class FirebaseServiceProvider extends ServiceProvider
 {
     public function register()
     {
         $this->app->singleton('firebase.auth', function () {
             return (new Factory)
                 ->withServiceAccount(config("firebase.credentials"))
                 ->createAuth();
         });
     }
 }
