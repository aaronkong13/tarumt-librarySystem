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

// Schedule due book email reminders daily at 8:00 AM
// Sends reminders for:
// - Books due in 3 days, 1 day, and today
// - Overdue books at 1, 3, 7, 14 days and weekly thereafter
Schedule::command('reminders:send-due-books')
    ->daily()
    ->at('08:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Due book reminders sent successfully');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Failed to send due book reminders');
    });
