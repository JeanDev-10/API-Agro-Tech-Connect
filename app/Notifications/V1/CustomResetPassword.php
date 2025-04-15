<?php

namespace App\Notifications\V1;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomResetPassword extends Notification implements ShouldQueue
{
    use Queueable;
    public $tries = 3; // Número de reintentos
    public function __construct(protected $token)
    {
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        // Genera una URL personalizada para el frontend
        $frontendUrl = config('app.frontend_url') . '/menu/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Restablecer Contraseña')
            ->line('Ha recibido este mensaje porque se solicitó un restablecimiento de contraseña para su cuenta.')
            ->action('Restablecer Contraseña', $frontendUrl)
            ->line('Este enlace de restablecimiento de contraseña expirará en 60 minutos.')
            ->line('Si no ha solicitado el restablecimiento de contraseña, omita este mensaje de correo electrónico.');

    }
}
