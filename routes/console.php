<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sync:products')->everyThirtyMinutes();
Schedule::command('sync:orders')->everyTenMinutes();
Schedule::command('sync:customers')->hourly();
Schedule::command('calculate:statistics')->hourly();
Schedule::command('meli:refresh-tokens')->everyThirtyMinutes();
Schedule::command('sync:questions')->everyFifteenMinutes();
Schedule::command('sync:post-sale-messages')->hourly();
Schedule::command('sync:product-statuses')->everySixHours();
Schedule::command('sync:reputation')->daily();
Schedule::command('sync:dollar')->hourly();
