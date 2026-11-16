<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\TeachingJournal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResyncJournalsCommand extends Command
{
    protected $signature = 'schedule:resync-journals {--dry-run : Only show what would be updated without applying changes}';

    protected $description = 'Merapikan teaching_journals yang tanggalnya tidak cocok dengan class_sessions terkait';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $this->info($isDryRun ? '=== DRY RUN: Memeriksa ketidaksinkronan jurnal dan sesi ===' : '=== Menjalankan sinkronisasi jurnal dan sesi ===');

        $mismatches = [];

        // 1. Jurnal yang sudah terhubung lewat class_session_id tetapi session_date berbeda
        $linkedJournals = TeachingJournal::with('classSession')
            ->whereNotNull('class_session_id')
            ->get();

        foreach ($linkedJournals as $j) {
            $session = $j->classSession;
            if (! $session) {
                continue;
            }

            $jDate = $j->session_date ? $j->session_date->toDateString() : null;
            $sDate = $session->session_date ? $session->session_date->toDateString() : null;

            if ($jDate !== $sDate) {
                $mismatches[] = [
                    'journal_id'   => $j->id,
                    'class_id'     => $j->class_id,
                    'session_id'   => $session->id,
                    'old_date'     => $jDate,
                    'new_date'     => $sDate,
                    'schedule_id'  => $session->class_schedule_id,
                    'type'         => 'Linked Mismatch',
                ];
            }
        }

        // 2. Jurnal yang belum punya class_session_id tetapi punya pasangan sesi dengan original_date = session_date jurnal
        $unlinkedJournals = TeachingJournal::whereNull('class_session_id')->get();
        foreach ($unlinkedJournals as $j) {
            $jDate = $j->session_date ? $j->session_date->toDateString() : null;
            if (! $jDate || ! $j->class_id) {
                continue;
            }

            $matchedSession = ClassSession::where('class_id', $j->class_id)
                ->where(function ($q) use ($j, $jDate) {
                    $q->whereDate('original_date', $jDate)
                      ->orWhere(fn ($sub) => $sub->whereDate('session_date', $jDate));
                })
                ->whereDoesntHave('journal')
                ->first();

            if ($matchedSession) {
                $sDate = $matchedSession->session_date->toDateString();
                if ($jDate !== $sDate || is_null($j->class_session_id)) {
                    $mismatches[] = [
                        'journal_id'   => $j->id,
                        'class_id'     => $j->class_id,
                        'session_id'   => $matchedSession->id,
                        'old_date'     => $jDate,
                        'new_date'     => $sDate,
                        'schedule_id'  => $matchedSession->class_schedule_id,
                        'type'         => 'Unlinked Match',
                    ];
                }
            }
        }

        if (empty($mismatches)) {
            $this->info('Semua jurnal sudah sinkron dengan class_sessions. Tidak ada data yang perlu diperbaiki.');
            return self::SUCCESS;
        }

        $this->table(
            ['Journal ID', 'Class ID', 'Session ID', 'Old Date', 'New Date', 'Type'],
            array_map(fn ($m) => [
                $m['journal_id'],
                $m['class_id'],
                $m['session_id'],
                $m['old_date'],
                $m['new_date'],
                $m['type'],
            ], $mismatches)
        );

        $this->info('Ditemukan ' . count($mismatches) . ' data tidak sinkron.');

        if ($isDryRun) {
            $this->warn('DRY RUN SELESAI: Tidak ada perubahan data yang disimpan ke database.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($mismatches) {
            foreach ($mismatches as $m) {
                $journal = TeachingJournal::find($m['journal_id']);
                if (! $journal) continue;

                $journal->update([
                    'session_date'      => $m['new_date'],
                    'class_session_id'  => $m['session_id'],
                    'class_schedule_id' => $m['schedule_id'] ?? $journal->class_schedule_id,
                ]);

                Attendance::where('teaching_journal_id', $journal->id)->update([
                    'attendance_date' => $m['new_date'],
                    'session_date'    => $m['new_date'],
                ]);
            }
        });

        $this->info('Berhasil menyinkronkan ' . count($mismatches) . ' jurnal ke tanggal yang sesuai.');

        return self::SUCCESS;
    }
}
