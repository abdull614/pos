<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Update cache skor trending (sembako & produk lokal terlaris) setiap hari jam 03:30
Schedule::command('trends:update-cache')
    ->dailyAt('03:30')
    ->withoutOverlapping();