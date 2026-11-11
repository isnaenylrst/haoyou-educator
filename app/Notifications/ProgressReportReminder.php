<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;


class ProgressReportReminder extends Notification
{
    /**
     * @param array<int, array<string, mixed>> $items  tiap item minimal berisi 'key'
     */
    public function __construct(private array $items, private string $sentBy = 'Admin')
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $count = count($this->items);

        return [
            'title'   => 'Progress report perlu diisi',
            'message' => "{$count} progress report sudah jatuh tempo dan belum dikirim ke Kurikulum.",
            'keys'    => array_values(array_column($this->items, 'key')),
            'items'   => $this->items,
            'sent_by' => $this->sentBy,
        ];
    }
}