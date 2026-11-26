<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncStudentStatus extends Command
{
    protected $signature = 'students:sync-status';

    protected $description = 'Sinkronkan status siswa (Aktif / Cuti / Lulus) dengan kondisi kelas';

    public function handle(StudentStatusService $service): int
    {
        $result = $service->syncAll();

        $this->info("Dicek {$result['checked']} siswa, {$result['changed']} status berubah.");

        return self::SUCCESS;
    }
}