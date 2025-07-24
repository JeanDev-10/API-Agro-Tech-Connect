<?php

namespace App\Notifications\V1;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class UserCreatePostComplaintNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public $tries = 3; // Número de reintentos
    /**
     * Create a new notification instance.
     */
    public function __construct(public $complaint, public $post, public $reporter)
    {
    }

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
        $url = config('app.frontend_url') . '/menu/mostrar-publicacion/' . Crypt::encrypt($this->post->id);


        return (new MailMessage)
            ->subject('Nueva denuncia de publicación')
            ->line('Se ha reportado una publicación como inapropiada.')
            ->line('Publicación: ' . $this->post->title)
            ->line('Usuario denunciante: ' . $this->reporter->name)
            ->line('Motivo: ' . $this->complaint->description)
            ->action('Revisar denuncia', $url)
            ->line('Gracias por usar nuestra aplicación!');
    }
    public function toArray($notifiable): array
    {
        $url = config('app.frontend_url') . '/menu/mostrar-publicacion/' . Crypt::encrypt($this->post->id);
        $url_sender_profile = config('app.frontend_url') . '/menu/perfil/' . Crypt::encrypt($this->reporter->id);
        return [
            'title' => 'Nueva denuncia de publicación',
            'message' => 'La publicación "' . Str::limit($this->post->title, 30) . '" ha sido denunciada.',
            'link_post' => $url,
            'complaint_id' => $this->complaint->id,
            'complaint_description' => $this->complaint->description,
            'post_id' => $this->post->id,
            'link_sender_profile' => $url_sender_profile,
            'sender_name' => $this->reporter->name,
            'sender_avatar' => $this->reporter->image->url ?? null,
            'sender_id' => Crypt::encrypt($this->reporter->id),
            'type' => 'new_complaint_post'
        ];
    }
}
