<?php

namespace App\Notifications\V1;

use App\Models\V1\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class UserFollowNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public $tries = 3; // Número de reintentos

    public function __construct(public User $follower)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('¡Tienes un nuevo seguidor!')
            ->greeting('Hola ' . $notifiable->name)
            ->line($this->follower->name . ' te ha comenzado a seguir')
            ->action('Ver perfil', $this->getProfileUrl())
            ->line('Gracias por usar nuestra aplicación!');
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => $this->follower->name . ' te ha comenzado a seguir',
            'follower_id' => $this->follower->id,
            'url_avatar' => $this->follower->image->url ?? null,
            'follower_name' => $this->follower->name,
            'type' => 'new_follower'
        ];
    }

    protected function getProfileUrl(): string
    {
        return config('app.frontend_url') . '/profile/' . Crypt::encrypt($this->follower->id); ;
    }
}
