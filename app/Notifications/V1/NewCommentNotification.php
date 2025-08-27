<?php

namespace App\Notifications\V1;

use App\Models\V1\Comment;
use App\Models\V1\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;

class NewCommentNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public $tries = 3; // Número de reintentos



    public function __construct(public Post $post, public Comment $comment, public $user)
    {
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $url = config('app.frontend_url') . '/menu/mostrar-comentario/' . Crypt::encrypt($this->comment->id);
        return (new MailMessage)
            ->subject('Nuevo comentario en tu publicación')
            ->line('Tu publicación "' . substr($this->post->title, 0, 100) . '...'  . '" tiene un nuevo comentario de ' . $this->user->name . '.')
            ->line('Comentario: "' . substr($this->comment->comment, 0, 50) . '...')
            ->action('Ver comentario', $url)
            ->line('Gracias por usar nuestra aplicación!');
    }

    public function toArray($notifiable)
    {
        $url_comment = config('app.frontend_url') . '/menu/mostrar-comentario/' . Crypt::encrypt($this->comment->id);
        $url_post = config('app.frontend_url') . '/menu/mostrar-publicacion/' . Crypt::encrypt($this->post->id);
        $url_profile_sender=config('app.frontend_url') . '/menu/perfil/' . Crypt::encrypt($this->user->id);
        return [
            'title' => 'Nuevo comentario en tu publicación',
            'post_id' => $this->post->id,
            'post_title' => $this->post->title,
            'comment_id' => $this->comment->id,
            'comment_content' => substr($this->comment->comment, 0, 50) . '...',
            'message' => 'Nuevo comentario en tu publicación: "' . substr($this->post->title, 0, 100) . '...' . ' por ' . $this->user->name,
            'link_comment'=>$url_comment,
            'link_post'=>$url_post,
            'link_sender_profile'=>$url_profile_sender,
            'sender_name'=>$this->user->name,
            'sender_avatar'=>$this->user->image->url ?? null,
            'sender_id' => $this->user->id,
            'type' => 'new_comment',
        ];
    }
}
