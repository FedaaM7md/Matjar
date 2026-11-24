<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Demo reset schedule
|--------------------------------------------------------------------------
| While demo mode is enabled, reset the demo database every hour so the demo
| always returns to a known baseline. Runs only when a scheduler is active.
*/
Schedule::command('demo:reset --force')
    ->hourly()
    ->when(fn () => (bool) config('matjar.demo.enabled'))
    ->withoutOverlapping();
