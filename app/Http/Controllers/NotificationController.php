<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Mark a specific notification as read and redirect to target URL.
     */
    public function read(string $id)
    {
        $user = Auth::user();
        abort_unless($user, 401);

        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        $targetUrl = $notification->data['url'] ?? null;

        if ($targetUrl && filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            return redirect()->to($targetUrl);
        }

        if ($targetUrl && str_starts_with($targetUrl, '/')) {
            return redirect()->to(url($targetUrl));
        }

        return redirect()->back();
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllRead(Request $request)
    {
        $user = Auth::user();
        abort_unless($user, 401);

        $user->unreadNotifications->markAsRead();

        return redirect()->back()->with('success', 'Semua notifikasi telah ditandai sudah dibaca.');
    }
}

