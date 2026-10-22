<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // AI assistant questions a school may ask per month: set on the plan, optionally overridden per school.
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('ai_requests_monthly')->default(0)->after('sms_credits');
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->unsignedInteger('ai_requests_limit')->nullable()->after('student_count');
        });

        Schema::create('ai_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->char('month', 7); // 2026-10
            $table->unsignedInteger('requests')->default(0);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->timestamps();

            $table->unique(['school_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage');
        Schema::table('schools', fn (Blueprint $table) => $table->dropColumn('ai_requests_limit'));
        Schema::table('plans', fn (Blueprint $table) => $table->dropColumn('ai_requests_monthly'));
    }
};
