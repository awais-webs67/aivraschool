<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Who did what, on the platform and inside schools.
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->cascadeOnDelete();
            $table->string('actor_type', 20)->default('system'); // platform | school | system
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name', 120)->nullable();
            $table->string('action', 60)->index();
            $table->string('description', 255);
            $table->string('subject_type', 60)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });

        // Notices from AivraSol shown on school dashboards.
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->text('body');
            $table->string('tone', 20)->default('info');
            $table->string('audience', 20)->default('all'); // all | plan | school
            $table->foreignId('plan_id')->nullable()->constrained('plans')->cascadeOnDelete();
            $table->foreignId('school_id')->nullable()->constrained('schools')->cascadeOnDelete();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->timestamps();
        });

        // Help desk between schools and AivraSol.
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject', 150);
            $table->string('category', 30)->default('general');
            $table->string('priority', 20)->default('normal');
            $table->string('status', 20)->default('open')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
        });

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->string('author_type', 20); // school | platform
            $table->unsignedBigInteger('author_id')->nullable();
            $table->string('author_name', 120);
            $table->text('body');
            $table->timestamp('created_at')->nullable();
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('invoice_number', 30)->nullable()->unique()->after('id');
        });

        Schema::table('demo_requests', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('message');
            $table->date('follow_up_on')->nullable()->after('notes');
            $table->foreignId('school_id')->nullable()->after('status')->constrained('schools')->nullOnDelete();
        });

        Schema::table('platform_users', function (Blueprint $table) {
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });

        // Existing data: number old invoices, and "staff" becomes the new "manager" role.
        foreach (DB::table('subscriptions')->whereNull('invoice_number')->orderBy('id')->get(['id', 'created_at']) as $row) {
            $year = $row->created_at ? substr((string) $row->created_at, 0, 4) : date('Y');
            DB::table('subscriptions')->where('id', $row->id)->update(['invoice_number' => 'INV-'.$year.'-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)]);
        }
        DB::table('platform_users')->where('role', 'staff')->update(['role' => 'manager']);
    }

    public function down(): void
    {
        Schema::table('platform_users', fn (Blueprint $table) => $table->dropColumn('last_login_ip'));
        Schema::table('demo_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
            $table->dropColumn(['notes', 'follow_up_on']);
        });
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropUnique(['invoice_number']);
            $table->dropColumn('invoice_number');
        });
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('activity_logs');
    }
};
