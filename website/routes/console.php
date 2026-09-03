<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Job;
use App\Models\Subscription;
use App\Models\JobAlert;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    Job::where('status', 'published')->whereNotNull('closing_date')->whereDate('closing_date', '<', today())->update(['status' => 'expired']);
    Subscription::where('status', 'active')->whereDate('expires_at', '<', today())->update(['status' => 'expired']);
})->daily();

Schedule::call(function (): void {
    JobAlert::where('is_active', true)->each(function (JobAlert $alert): void {
        $alert->touch();
    });
})->hourly();
