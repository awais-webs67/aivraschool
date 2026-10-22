<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Principal's profile and the school's picture.
        Schema::table('schools', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('logo_path');
            $table->string('principal_email', 150)->nullable()->after('principal_name');
            $table->string('principal_phone', 30)->nullable()->after('principal_email');
            $table->string('principal_national_id', 40)->nullable()->after('principal_phone');
            $table->string('principal_qualification', 150)->nullable()->after('principal_national_id');
            $table->string('principal_address')->nullable()->after('principal_qualification');
            $table->text('principal_bio')->nullable()->after('principal_address');
            $table->string('principal_photo_path')->nullable()->after('principal_bio');
            $table->unsignedInteger('student_count')->nullable()->after('currency');
        });

        // What the pricing button does, and exact prices in other currencies (e.g. {"USD": 29}).
        Schema::table('plans', function (Blueprint $table) {
            $table->string('cta_mode', 20)->default('trial')->after('trial_days'); // trial | register | stripe | contact
            $table->string('cta_label', 60)->nullable()->after('cta_mode');
            $table->text('prices')->nullable()->after('price_per_student');
        });

        // "Register your school" form submissions, reviewed by the Super Admin.
        Schema::create('school_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20)->default('pending')->index(); // pending | approved | rejected
            $table->string('school_name', 150);
            $table->string('slug', 60);
            $table->string('school_email', 150)->nullable();
            $table->string('school_phone', 30)->nullable();
            $table->string('school_address')->nullable();
            $table->string('city', 80)->nullable();
            $table->string('country', 80)->nullable();
            $table->char('currency', 3)->nullable();
            $table->unsignedInteger('students')->nullable();
            $table->string('school_photo_path')->nullable();
            $table->string('principal_name', 120);
            $table->string('principal_email', 150);
            $table->string('principal_phone', 30);
            $table->string('principal_national_id', 40)->nullable();
            $table->string('principal_qualification', 150)->nullable();
            $table->string('principal_address')->nullable();
            $table->text('principal_bio')->nullable();
            $table->string('principal_photo_path')->nullable();
            $table->string('admin_password'); // already hashed; becomes the school admin's password
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->string('billing_choice', 20)->default('trial'); // trial | pay | request
            $table->decimal('amount', 12, 2)->nullable();
            $table->char('amount_currency', 3)->nullable();
            $table->string('payment_status', 20)->default('none'); // none | pending | paid
            $table->string('stripe_session_id')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_registrations');
        Schema::table('plans', fn (Blueprint $table) => $table->dropColumn(['cta_mode', 'cta_label', 'prices']));
        Schema::table('schools', fn (Blueprint $table) => $table->dropColumn([
            'photo_path', 'principal_email', 'principal_phone', 'principal_national_id', 'principal_qualification',
            'principal_address', 'principal_bio', 'principal_photo_path', 'student_count',
        ]));
    }
};
