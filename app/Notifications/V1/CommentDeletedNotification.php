<?php

namespace App\Notifications\V1;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class CommentDeletedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public $tries = 3; // Número de reintentos


    public function __construct(
        public string $comment,
        public string $admin
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu comentario ha sido eliminado')
            ->line("Tu comentario '{$this->comment}' ha sido eliminado por el administrador {$this->admin} por infringir las normas de nuestra plataforma.")
            ->line('Motivo: Contenido inapropiado')
            ->line('Gracias por usar nuestra plataforma.');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Comentario eliminado',
            'message' => "Tu comentario '{$this->comment}' fue eliminado por infringir las normas",
            'type' => 'deleted_comment',
        ];
    }
}
