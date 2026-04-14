<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('milestones:sync-statuses')->everyMinute();

// Schedule::command('team-milestone:update')->everyMinute();
Schedule::command('team-milestone:update')->dailyAt('00:00');