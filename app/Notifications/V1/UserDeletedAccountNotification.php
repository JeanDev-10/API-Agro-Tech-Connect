<?php

namespace App\Notifications\V1;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserDeletedAccountNotification extends Notification
{
    use Queueable;
    public $tries = 3; // Número de reintentos
    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
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
        return (new MailMessage)
            ->subject('Tu cuenta ha sido eliminada - ' . config('app.name'))
            ->greeting('Hola ' . $notifiable->name)
            ->line('Acabamos de completar la eliminación de tu cuenta.')
            ->line('**Detalles importantes:**')
            ->line('Todos tus datos personales han sido eliminados de nuestros sistemas')
            ->line('') // Espacio en blanco
            ->line('Si deseas volver a unirte en el futuro, estarás siempre bienvenido.')
            ->line('') // Espacio en blanco
            ->line('Gracias por habernos permitido ser parte de tu experiencia.');
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
