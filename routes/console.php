<?php

use App\Models\User;
use App\Services\SubscriptionManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;

Artisan::command('skooline:create-admin {email} {--name=Super Admin}', function (string $email) {
    $password = $this->secret('Password (min 10 characters)');

    $validator = Validator::make(['email' => $email, 'password' => $password], [
        'email' => ['required', 'email', 'unique:users,email'],
        'password' => ['required', 'min:10'],
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }

    User::create([
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

Schedule::command('skooline:expire-subscriptions')->dailyAt('00:10');
