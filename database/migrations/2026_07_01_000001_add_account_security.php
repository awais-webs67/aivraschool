<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Two-factor sign-in (authenticator app) for the Super Admin team. Secrets are stored encrypted.
        Schema::table('platform_users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });

        // "Forgot password" for school portal users (the same email can exist in two schools).
        Schema::create('school_password_resets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('email');
            $table->string('token'); // hashed
            $table->timestamp('created_at')->nullable();

            $table->index(['school_id', 'email']);
        });

        // Device shown in login history.
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('user_agent', 255)->nullable()->after('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', fn (Blueprint $table) => $table->dropColumn('user_agent'));
        Schema::dropIfExists('school_password_resets');
        Schema::table('platform_users', fn (Blueprint $table) => $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']));
    }
};
