<?php

namespace App\Notifications\V1;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserChangePasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public $tries = 3; // Número de reintentos

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = config('app.frontend_url') . '/password/reset';
        return (new MailMessage)
            ->subject('Tu contraseña ha sido cambiada - Agro Tech Connect')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Acabamos de completar el cambio de contraseña en tu cuenta.')
            ->line('**¿Fuiste tú?**')
            ->line('Si sí, no necesitas hacer nada más.')
            ->line('')
            ->line('**¿No lo hiciste tú?**')
            ->action('Recupera tu contraseña ahora', url($frontendUrl))
            ->line('Por favor recupera tu contraseña inmediatamente usando el enlace superior.')
            ->line('');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
