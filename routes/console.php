<?php

use App\Jobs\ExpireRegistrationFees;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expire fee pendaftaran calon siswa yang lewat 30 hari
Schedule::job(new ExpireRegistrationFees())->dailyAt('01:00');