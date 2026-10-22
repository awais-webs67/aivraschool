<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Discount codes for registration and renewals.
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('description')->nullable();
            $table->string('type', 10)->default('percent'); // percent | fixed
            $table->decimal('value', 12, 2);
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete(); // null = every plan
            $table->unsignedInteger('max_uses')->nullable(); // null = unlimited
            $table->unsignedInteger('used_count')->default(0);
            $table->date('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Invoice breakdown: amount stays the total to pay.
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->decimal('subtotal', 12, 2)->nullable()->after('student_count');
            $table->decimal('discount', 12, 2)->default(0)->after('subtotal');
            $table->string('coupon_code', 40)->nullable()->after('discount');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('coupon_code');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('tax_rate');
            $table->string('tax_name', 20)->nullable()->after('tax_amount');
            $table->timestamp('sent_at')->nullable()->after('paid_at');
            $table->timestamp('reminded_at')->nullable()->after('sent_at');
        });

        Schema::table('school_registrations', function (Blueprint $table) {
            $table->decimal('subtotal', 12, 2)->nullable()->after('amount');
            $table->decimal('discount', 12, 2)->default(0)->after('subtotal');
            $table->string('coupon_code', 40)->nullable()->after('discount');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('coupon_code');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('tax_rate');
        });

        // "Your plan ends in N days" emails: remembers the last one sent ("2026-11-30:7").
        Schema::table('schools', function (Blueprint $table) {
            $table->string('expiry_reminded_for', 30)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('schools', fn (Blueprint $table) => $table->dropColumn('expiry_reminded_for'));
        Schema::table('school_registrations', fn (Blueprint $table) => $table->dropColumn(['subtotal', 'discount', 'coupon_code', 'tax_rate', 'tax_amount']));
        Schema::table('subscriptions', fn (Blueprint $table) => $table->dropColumn(['subtotal', 'discount', 'coupon_code', 'tax_rate', 'tax_amount', 'tax_name', 'sent_at', 'reminded_at']));
        Schema::dropIfExists('coupons');
    }
};
