<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Notifikasi sederhana (disimpan di tabel notifications).
 * Dipakai untuk siswa maupun admin.
 */
class StudentNotice extends Notification
{
    public function __construct(
        private string $title,
        private string $message,
        private ?string $url = null,
        private ?string $key = null,   // kunci unik agar pengingat tidak terkirim dobel
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => $this->title,
            'message' => $this->message,
            'url'     => $this->url,
            'key'     => $this->key,
        ];
    }
}
