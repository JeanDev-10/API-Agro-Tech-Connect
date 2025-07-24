<?php

namespace App\Notifications\V1;

use App\Models\V1\Comment;
use App\Models\V1\Post;
use App\Models\V1\ReplayComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;

class NewReplayCommentNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public $tries = 3; // Número de reintentos



    public function __construct(public ReplayComment $replayComment, public Comment $comment, public $user)
    {
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $url = config('app.frontend_url') . '/menu/mostrar-respuesta-comentario/' . Crypt::encrypt($this->replayComment->id);
        return (new MailMessage)
            ->subject('Nueva respuesta en tu comentario')
            ->line('Tu comentario "' . substr($this->comment->comment, 0, 100) . '...'  . '" tiene una nueva respuesta de ' . $this->user->name . '.')
            ->line('Respuesta: "' . substr($this->replayComment->comment, 0, 50) . '...')
            ->action('Ver comentario', $url)
            ->line('Gracias por usar nuestra aplicación!');
    }

    public function toArray($notifiable)
    {
        $url = config('app.frontend_url') . '/menu/mostrar-respuesta-comentario/' . Crypt::encrypt($this->replayComment->id);
        $url_sender_profile = config('app.frontend_url') . '/menu/perfil/' . Crypt::encrypt($this->user->id);
        return [
            'replaycomment_id' => $this->replayComment->id,
            'comment_content' => $this->comment->comment,
            'comment_id' => $this->comment->id,
            'replaycomment_content' => substr($this->replayComment->comment, 0, 50) . '...',
            'message' => 'Nueva respuesta en tu comentario: "' . substr($this->comment->comment, 0, 100) . '...' . ' por ' . $this->user->name,
            'link_replaycomment'=>$url,
            'link_sender_profile' => $url_sender_profile,
            'sender_name'=>$this->user->name,
            'sender_avatar'=>$this->user->image->url ?? null,
            'sender_id' => Crypt::encrypt($this->user->id),
            'type' => 'new_replay_comment',
        ];
    }
}
