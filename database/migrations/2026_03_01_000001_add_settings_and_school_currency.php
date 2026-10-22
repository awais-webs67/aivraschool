<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Platform-wide settings edited by the Super Admin (billing currency, website currencies…).
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 80)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->string('country', 80)->nullable()->after('city');
            $table->char('currency', 3)->nullable()->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['country', 'currency']);
        });

        Schema::dropIfExists('settings');
    }
};
