<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The bell schedule: Period 1 08:00–08:40, Break, …
        Schema::create('periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name', 40);
            $table->time('starts_at');
            $table->time('ends_at');
            $table->boolean('is_break')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Who teaches what in each section, day and period. day: 1 = Monday … 7 = Sunday
        Schema::create('timetable_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();
            $table->unsignedTinyInteger('day');
            $table->foreignId('period_id')->constrained('periods')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('room', 30)->nullable();
            $table->timestamps();

            $table->unique(['section_id', 'day', 'period_id']);
            $table->index(['school_id', 'staff_id', 'day']);
        });

        // A teacher covering another teacher's period on one date.
        Schema::create('substitutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->date('date');
            $table->foreignId('timetable_entry_id')->constrained('timetable_entries')->cascadeOnDelete();
            $table->foreignId('absent_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('substitute_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['timetable_entry_id', 'date']);
            $table->index(['school_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('substitutions');
        Schema::dropIfExists('timetable_entries');
        Schema::dropIfExists('periods');
    }
};
