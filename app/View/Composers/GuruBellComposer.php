<?php

namespace App\View\Composers;

use App\Models\User;
use App\Services\TeacherNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GuruBellComposer
{
    public function compose(View $view): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        $teacher = $user?->teacher;

        if (!$teacher) {
            $view->with('bell', ['rows' => [], 'total' => 0]);
            return;
        }

        try {
            $bell = TeacherNotificationService::for($teacher)->bell();
        } catch (\Throwable $e) {
            report($e);
            $bell = ['rows' => [], 'total' => 0];
        }

        $view->with('bell', $bell);
    }
}