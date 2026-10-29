<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Gestão de clientes: rotinas agendadas (rode `php artisan schedule:work` ou o cron do schedule:run).
Schedule::command('gestao:generate-charges')->dailyAt('06:00')->withoutOverlapping();
Schedule::command('gestao:dunning')->dailyAt('09:00')->withoutOverlapping();
Schedule::command('gestao:monitor')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('gestao:check-domains')->dailyAt('03:30');
Schedule::command('gestao:sync-signatures')->hourly()->withoutOverlapping();
