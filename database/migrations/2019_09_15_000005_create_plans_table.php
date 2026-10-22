<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('billing_model', 20);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('price_per_student', 10, 2)->default(0);
            $table->decimal('setup_fee', 12, 2)->default(0);
            $table->unsignedSmallInteger('trial_days')->default(14);
            $table->unsignedInteger('max_students')->nullable();
            $table->unsignedInteger('max_staff')->nullable();
            $table->unsignedSmallInteger('max_branches')->default(1);
            $table->unsignedInteger('storage_gb')->default(5);
            $table->unsignedInteger('sms_credits')->default(0);
            $table->json('modules');
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_website')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
