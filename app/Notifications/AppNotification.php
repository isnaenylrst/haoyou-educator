<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public ?string $url = null,
        public ?string $type = 'general',
        public ?string $icon = 'fa-bell',
        public ?string $actionLabel = 'Lihat Detail'
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'        => $this->title,
            'message'      => $this->message,
            'url'          => $this->url,
            'type'         => $this->type,
            'icon'         => $this->icon,
            'action_label' => $this->actionLabel,
        ];
    }
}

