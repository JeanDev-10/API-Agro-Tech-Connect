<?php

namespace App\Notifications\V1;

use App\Models\V1\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Crypt;

class NewPostNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public $tries = 3; // Número de reintentos

    public function __construct(public Post $post)
    {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = config('app.frontend_url') . '/menu/mostrar-publicacion/' . Crypt::encrypt($this->post->id);
        return (new MailMessage)
            ->subject('Nueva publicación de ' . $this->post->user->name)
            ->line($this->post->user->name . ' ha publicado algo nuevo:')
            ->line($this->post->title)
            ->line(substr($this->post->description, 0, 100) . '...')
            ->action('Ver publicación', $url)
            ->line('¡Gracias por usar nuestra aplicación!');
    }

    public function toArray($notifiable): array
    {
        $url = config('app.frontend_url') . '/menu/mostrar-publicacion/' . Crypt::encrypt($this->post->id);
        $url_sender_profile = config('app.frontend_url') . '/menu/perfil/' . Crypt::encrypt($this->post->user->id);
        return [
            'message' => $this->post->user->name . ' ha publicado algo nuevo',
            'post_id' => $this->post->id,
            'sender_name'=>$this->post->user->name,
            'sender_avatar'=>$this->post->user->image->url ?? null,
            'sender_id' => Crypt::encrypt($this->post->user->id),
            'link_post'=>$url,
            'link_sender_profile'=>$url_sender_profile,
            'type' => 'new_post',
        ];
    }
}
