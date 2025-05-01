<?php

namespace App\Notifications\V1;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class UserCreateReplayCommentComplaintNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public $tries = 3; // Número de reintentos
    /**
     * Create a new notification instance.
     */
    public function __construct(public  $complaint, public  $comment, public  $reporter) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = config('app.frontend_url') . '/replaycomment/' . Crypt::encrypt($this->comment->id);


        return (new MailMessage)
            ->subject('Nueva denuncia de respuesta a comentario')
            ->line('Se ha reportado una respuesta a comentario como inapropiado.')
            ->line('Respuesta a comentario: ' . $this->comment->comment)
            ->line('Usuario denunciante: ' . $this->reporter->name)
            ->line('Motivo: ' . $this->complaint->description)
            ->action('Revisar denuncia', $url)
            ->line('Gracias por usar nuestra aplicación!');
    }
    public function toArray($notifiable): array
    {
        $url = config('app.frontend_url') . '/replaycomment/' . Crypt::encrypt($this->comment->id);

        return [
            'title' => 'Nueva denuncia de respuesta a comentario',
            'message' => 'La respuesta a comentario: "' . Str::limit($this->comment->comment, 30) . '" ha sido denunciada.',
            'link' => $url,
            'complaint_id' => $this->complaint->id,
            'replaycomment_id' => $this->comment->id,
            'user_id' => $this->reporter->id,
            'type' => 'new_complaint_replay_comment'
        ];
    }
}
