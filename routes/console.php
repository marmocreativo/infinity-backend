<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Libera los pedidos programados para hoy (requiere el cron de Laravel: schedule:run cada minuto)
Illuminate\Support\Facades\Schedule::command('orders:release-scheduled')->everyFiveMinutes()->withoutOverlapping();
