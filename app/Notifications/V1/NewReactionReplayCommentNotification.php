<?php

namespace App\Notifications\V1;

use App\Models\V1\Reaction;
use App\Models\V1\ReplayComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;


class NewReactionReplayCommentNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public $tries = 3; // Número de reintentos

    public $replayComment;
    public $reaction;

    /**
     * Create a new notification instance.
     */
    public function __construct(ReplayComment $replayComment, Reaction $reaction)
    {
        $this->replayComment = $replayComment;
        $this->reaction = $reaction;
    }

    /**
     * Get the notification's delivery channels.
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
        $reactionType = $this->reaction->type === 'positivo' ? 'positivo' : 'negativo';
        $url = config('app.frontend_url') . '/menu/mostrar-respuesta-comentario/' . Crypt::encrypt($this->replayComment->id);

        return (new MailMessage)
            ->subject("Tu respuesta recibió un $reactionType")
            ->line("Tu respuesta " . Str::limit($this->replayComment->comment, 30) . " ha recibido un $reactionType.")
            ->action('Ver respuesta a comentario', $url)
            ->line('¡Gracias por participar en nuestra comunidad!');
    }

    /**
     * Get the array representation for database storage.
     */
    public function toArray(object $notifiable): array
    {
        $url = config('app.frontend_url') . '/menu/mostrar-respuesta-comentario/' . Crypt::encrypt($this->replayComment->id);
        $url_sender_profile = config('app.frontend_url') . '/menu/perfil/' . Crypt::encrypt($this->reaction->user->id);
        return [
            'type' => 'new_reaction',
            'replaycomment_id' => $this->replayComment->id,
            'reaction_id' => $this->reaction->id,
            'reaction_type' => $this->reaction->type,
            'replaycomment_content' => Str::limit($this->replayComment->comment, 30),
            'link_replaycomment' => $url,
            'link_sender_profile' => $url_sender_profile,
            'sender_name'=>$this->reaction->user->name,
            'sender_avatar'=>$this->reaction->user->image->url ?? null,
            'sender_id' => Crypt::encrypt($this->reaction->user->id),
            'message' => "Tu respuesta recibió un {$this->reaction->type}"
        ];
    }
}
