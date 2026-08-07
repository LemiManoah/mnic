<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// The daily chase: overdue contributions, overdue actions and votes about to
// close. Runs mid-morning Kampala time so a reminder lands during the working
// day rather than overnight.
Schedule::command('club:sweep-overdue')
    ->dailyAt('09:00')
    ->withoutOverlapping();
