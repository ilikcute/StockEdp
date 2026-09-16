<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// -----------------------------------------------------------------------------
// Maintenance scheduler — jalankan `php artisan schedule:work` di server produksi
// (atau tambahkan "* * * * * php /path/artisan schedule:run" ke crontab).
// -----------------------------------------------------------------------------

// Bersihkan token Sanctum yang kedaluwarsa setiap hari (kebersihan tabel auth).
Schedule::command('sanctum:prune-expired --hours=24')->daily();

// Bersihkan job queue yang gagal setelah 7 hari.
Schedule::command('queue:prune-failed --hours=168')->daily();