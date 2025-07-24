<?php

namespace App\Notifications\V1;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class PostDeletedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public $tries = 3; // Número de reintentos


    public function __construct(
        public string $postTitle,
        public string $adminName
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu publicación ha sido eliminada')
            ->line("Tu publicación '{$this->postTitle}' ha sido eliminada por el administrador {$this->adminName} por infringir las normas de nuestra plataforma.")
            ->line('Motivo: Contenido inapropiado')
            ->line('Gracias por usar nuestra plataforma.');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Publicación eliminada',
            'message' => "Tu publicación '{$this->postTitle}' fue eliminada por infringir las normas",
            'type' => 'deleted_post',
        ];
    }
}
