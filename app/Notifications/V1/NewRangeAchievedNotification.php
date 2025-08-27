<?php

namespace App\Notifications\V1;

use App\Models\V1\Range;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;

class NewRangeAchievedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public $tries = 3; // Número de reintentos

    public $range;

    /**
     * Create a new notification instance.
     */
    public function __construct(Range $range)
    {
        $this->range = $range;
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
        $url = config('app.frontend_url') . '/menu/perfil';
        return (new MailMessage)
            ->subject("¡Felicidades! Has alcanzado el rango {$this->range->name}")
            ->line("Has alcanzado el rango {$this->range->name} en nuestra comunidad.")
            ->line($this->range->description)
            ->action('Ver tu perfil', $url)
            ->line('¡Sigue participando para alcanzar nuevos rangos!');
    }

    /**
     * Get the array representation for database storage.
     */
    public function toArray(object $notifiable): array
    {
        $url = config('app.frontend_url') . '/menu/perfil';

        return [
            'title' => 'Has alcanzado un nuevo rango',
            'type' => 'new_range',
            'range_id' => $this->range->id,
            'range_name' => $this->range->name,
            'range_description' => $this->range->description,
            'achieved_at' => now()->format('d/m/Y H:i'),
            'link' => $url,
            'message' => "¡Felicidades! Has alcanzado el rango {$this->range->name}"
        ];
    }
}
