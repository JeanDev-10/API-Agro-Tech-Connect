<?php

namespace App\Notifications\V1;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class ReplayCommentDeletedNotification extends Notification implements ShouldQueue
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
            ->subject('Tu respuesta ha sido eliminada')
            ->line("Tu respuesta '{$this->comment}' ha sido eliminada por el administrador {$this->admin} por infringir las normas de nuestra plataforma.")
            ->line('Motivo: Contenido inapropiado')
            ->line('Gracias por usar nuestra plataforma.');
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => "Tu respuesta '{$this->comment}' fue eliminada por infringir las normas",
            'type' => 'deleted_replay_comment',
        ];
    }
}
