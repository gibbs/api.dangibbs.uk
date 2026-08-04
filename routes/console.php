<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled commands
Schedule::command('feed:activity')->hourly();

Schedule::command('feed:insights')->weekly();
Schedule::command('feed:litmusimage')->weekly();
