<?php

use App\Models\PlatformUser;
use App\Services\BillingReminders;
use App\Services\SubscriptionManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;

Artisan::command('skooline:create-admin {email} {--name=Super Admin}', function (string $email) {
    $password = $this->secret('Password (min 10 characters)');

    $validator = Validator::make(['email' => $email, 'password' => $password], [
        'email' => ['required', 'email', 'unique:platform_users,email'],
        'password' => ['required', 'min:10'],
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }

    PlatformUser::create([
        'name' => $this->option('name'),
        'email' => strtolower($email),
        'password' => $password,
        'role' => 'super_admin',
    ]);

    $this->info("Super Admin {$email} created.");
})->purpose('Create a Skooline Super Admin account');

Artisan::command('skooline:expire-subscriptions', function (SubscriptionManager $manager) {
    $count = $manager->expireOverdue();

    $this->info("{$count} school(s) moved to expired.");
})->purpose('Expire schools whose trial or subscription (plus grace period) has ended');

Artisan::command('skooline:send-reminders', function (BillingReminders $reminders) {
    $sent = $reminders->send();

    $this->info("{$sent['expiry']} expiry reminder(s) and {$sent['unpaid']} unpaid-invoice reminder(s) sent.");
})->purpose('Email schools about trials/subscriptions ending soon and unpaid invoices');

// Runs from the single cron entry: * * * * * php artisan schedule:run
Schedule::call(fn (SubscriptionManager $manager) => $manager->expireOverdue())
    ->name('skooline:expire-subscriptions')
    ->dailyAt('00:10');

Schedule::call(fn (BillingReminders $reminders) => $reminders->send())
    ->name('skooline:send-reminders')
    ->dailyAt('08:00');

// Shared hosting has no long-running queue worker: drain the database queue every minute.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();
