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
        $url = config('app.frontend_url') . '/post/' . Crypt::encrypt($this->post->id);
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
        $url = config('app.frontend_url') . '/post/' . Crypt::encrypt($this->post->id);
        return [
            'message' => $this->post->user->name . ' ha publicado algo nuevo',
            'post_id' => $this->post->id,
            'user_id' => $this->post->user->id,
            'link'=>$url,
            'type' => 'new_post',
        ];
    }
}
