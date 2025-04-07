<?php

namespace App\Providers;

use App\Providers\V1\RepositoryServiceProvider;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer')
                );
            }); //


        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            $parsedUrl = parse_url($url);
            $pathSegments = explode('/', trim($parsedUrl['path'], '/'));
            if (count($pathSegments) < 3) {
                throw new \Exception('Estructura invalida, se esperaba 3 argumentos.');
            }
            parse_str($parsedUrl['query'], $queryParams);
            if (!isset($queryParams['signature'])) {
                throw new \Exception('No existe la firma en la URL.');
            }

            $frontendUrl = config('app.frontend_url') . config('app.frontend_url_path_email_confirmation') . http_build_query([
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
                'expires' => $queryParams['expires'],
                'signature' => $queryParams['signature'],
            ]);
            return (new MailMessage)
                ->subject('Verifica tu dirección de correo')
                ->line('Por favor haz clic en el botón para verificar tu email.')
                ->action('Verificar Email', $frontendUrl)
                ->line('Si no creaste una cuenta, ignora este mensaje.');
        });
    }
}
