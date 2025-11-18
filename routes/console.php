<?php

use App\Jobs\MarkOrdersEligibleJob;
use App\Jobs\ProcessWeeklyPayoutJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule daily job to mark orders as payout-eligible
Schedule::job(new MarkOrdersEligibleJob)->daily();

// Schedule weekly payout job (every Monday at 9 AM)
Schedule::job(new ProcessWeeklyPayoutJob)->weeklyOn(1, '9:00');
