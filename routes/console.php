<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// מייל חודשי — רץ כל יום בבוקר; הפקודה עצמה בודקת אם היום ראש חודש ושולחת רק אז.
Schedule::command('digest:monthly')
    ->dailyAt('08:00')
    ->timezone('Asia/Jerusalem');
