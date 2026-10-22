<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-school options (fee due day, late fee, …) as one JSON column.
        Schema::table('schools', function (Blueprint $table) {
            $table->json('settings')->nullable();
        });

        // What the school charges for: tuition, admission, exam, transport …
        Schema::create('fee_heads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('frequency', 10)->default('monthly'); // monthly | yearly | once
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['school_id', 'name']);
        });

        // Amount of each fee head for each class.
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('fee_head_id')->constrained('fee_heads')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->unique(['class_id', 'fee_head_id']);
        });

        // A fee voucher (challan) for one student.
        Schema::create('fee_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('invoice_no', 30);
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('month')->nullable(); // first day of the month it bills; null for one-off vouchers
            $table->string('title', 120);
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('fine', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->decimal('paid', 12, 2)->default(0);
            $table->string('status', 10)->default('unpaid'); // unpaid | partial | paid | cancelled
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'invoice_no']);
            $table->index(['school_id', 'status', 'due_date']);
            $table->index(['school_id', 'student_id', 'month']);
        });

        Schema::create('fee_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_invoice_id')->constrained('fee_invoices')->cascadeOnDelete();
            $table->foreignId('fee_head_id')->nullable()->constrained('fee_heads')->nullOnDelete();
            $table->string('description', 120);
            $table->decimal('amount', 12, 2);
        });

        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('receipt_no', 30);
            $table->foreignId('fee_invoice_id')->constrained('fee_invoices')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method', 20)->default('cash');
            $table->string('reference', 80)->nullable();
            $table->date('paid_on');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'paid_on']);
            $table->index(['school_id', 'receipt_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_payments');
        Schema::dropIfExists('fee_invoice_items');
        Schema::dropIfExists('fee_invoices');
        Schema::dropIfExists('fee_structures');
        Schema::dropIfExists('fee_heads');
        Schema::table('schools', fn (Blueprint $table) => $table->dropColumn('settings'));
    }
};
