<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Pengingat dari admin ke laoshi untuk mengisi jurnal mengajar.
 * Disimpan di tabel `notifications` (channel database) dan tampil di
 * notifikasi sisi laoshi lewat $user->notifications / unreadNotifications.
 */
class JurnalMengajarReminder extends Notification
{
    /**
     * @param array<int, array{key:string,class_name:string,date:string,date_label:string,time_label:string}> $sessions
     */
    public function __construct(
        protected array $sessions,
        protected ?string $sentBy = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $count = count($this->sessions);
        $first = $this->sessions[0];

        $message = $count === 1
            ? "Jurnal mengajar kelas {$first['class_name']} ({$first['date_label']}, {$first['time_label']}) belum diisi. Mohon segera diisi."
            : "Ada {$count} jurnal mengajar yang belum diisi. Mohon segera dilengkapi.";

        return [
            'type'     => 'jurnal_reminder',
            'title'    => 'Pengingat Jurnal Mengajar',
            'message'  => $message,
            'keys'     => array_column($this->sessions, 'key'),
            'sessions' => $this->sessions,
            'sent_by'  => $this->sentBy,
        ];
    }
}
