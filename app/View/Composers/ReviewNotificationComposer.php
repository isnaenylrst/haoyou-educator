<?php

namespace App\View\Composers;

use App\Models\ProgressReport;
use App\Models\TeacherLeave;
use App\Models\TeacherMaterial;
use App\Models\TeachingJournal;
use Illuminate\View\View;

class ReviewNotificationComposer
{
    public function compose(View $view): void
    {
        // ---- Ambil semua item yang masih menunggu review Kurikulum ----

        $materials = TeacherMaterial::with('teacher')
            ->where('status', 'Pending')
            ->latest()
            ->get()
            ->map(fn ($item) => [
                'label' => ($item->teacher->name ?? 'Guru') . ' mengirim LP & PPT',
                'time'  => $item->created_at,
                'tab'   => 'material',
            ]);

        $journals = TeachingJournal::with('teacher')
            ->where('status', 'Pending')
            ->latest()
            ->get()
            ->map(fn ($item) => [
                'label' => ($item->teacher->name ?? 'Guru') . ' mengisi jurnal mengajar',
                'time'  => $item->created_at,
                'tab'   => 'jurnal',
            ]);

        $reports = ProgressReport::with('teacher')
            ->where('status', 'Submitted')
            ->whereNull('reviewed_at')
            ->latest('uploaded_at')
            ->get()
            ->map(fn ($item) => [
                'label' => ($item->teacher->name ?? 'Guru') . ' mengirim progress report',
                'time'  => $item->uploaded_at,
                'tab'   => 'report',
            ]);

        $leaves = TeacherLeave::with('teacher')
            ->where('status', 'Pending')
            ->latest()
            ->get()
            ->map(fn ($item) => [
                'label' => ($item->teacher->name ?? 'Guru') . ' mengajukan cuti/ganti kelas',
                'time'  => $item->created_at,
                'tab'   => 'cuti',
            ]);

        $allNotifications = $materials
            ->concat($journals)
            ->concat($reports)
            ->concat($leaves)
            ->sortByDesc('time')
            ->values();

        $view->with('notifCount', $allNotifications->count());
        $view->with('notifItems', $allNotifications->take(6));
    }
}