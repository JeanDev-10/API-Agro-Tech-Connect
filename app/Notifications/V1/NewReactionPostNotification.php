<?php

namespace App\Notifications\V1;

use App\Models\V1\Comment;
use App\Models\V1\Post;
use App\Models\V1\Reaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;


class NewReactionPostNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public $tries = 3; // Número de reintentos

    public $post;
    public $reaction;

    /**
     * Create a new notification instance.
     */
    public function __construct(Post $post, Reaction $reaction)
    {
        $this->post = $post;
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


        $url = config('app.frontend_url') . '/menu/mostrar-publicacion/' . Crypt::encrypt($this->post->id);
        return (new MailMessage)
            ->subject("Tu publicación recibió un $reactionType")
            ->line("Tu publicación " . Str::limit($this->post->title, 30) . " ha recibido un $reactionType.")
            ->action('Ver publicación', $url)
            ->line('¡Gracias por participar en nuestra comunidad!');
    }

    /**
     * Get the array representation for database storage.
     */
    public function toArray(object $notifiable): array
    {
        $url = config('app.frontend_url') . '/menu/mostrar-publicacion/' . Crypt::encrypt($this->post->id);
        $url_sender_profile = config('app.frontend_url') . '/menu/perfil/' . Crypt::encrypt($this->reaction->user->id);
        return [
            'title' => 'Alguien reaccionó a tu publicación',
            'type' => 'new_reaction',
            'post_id' => $this->post->id,
            'reaction_id' => $this->reaction->id,
            'reaction_type' => $this->reaction->type,
            'post_title' => Str::limit($this->post->title, 30),
            'link_post' => $url,
            'link_sender_profile' => $url_sender_profile,
            'sender_name' => $this->reaction->user->name,
            'sender_avatar' => $this->reaction->user->image->url ?? null,
            'sender_id' => $this->reaction->user->id,
            'message' => "Tu publicación recibió un {$this->reaction->type}"
        ];
    }
}
