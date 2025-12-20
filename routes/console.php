<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule reservation expiry check every hour
Schedule::command('reservations:check-expired')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Schedule overdue fines processing daily at midnight
Schedule::command('fines:process-overdue')
    ->daily()
    ->at('00:00')
    ->withoutOverlapping()
    ->runInBackground();
