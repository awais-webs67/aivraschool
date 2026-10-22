<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Behaviour record: problems and achievements.
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('date');
            $table->string('kind', 10)->default('concern'); // concern | merit
            $table->string('category', 40);
            $table->string('severity', 10)->default('low'); // low | medium | high
            $table->text('description');
            $table->string('action_taken', 40)->nullable();
            $table->boolean('parent_informed')->default(false);
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'student_id']);
            $table->index(['school_id', 'date']);
        });

        // Principal's classroom observation of a teacher.
        Schema::create('observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->date('date');
            $table->json('ratings');
            $table->decimal('overall', 3, 1);
            $table->text('strengths')->nullable();
            $table->text('improvements')->nullable();
            $table->foreignId('observer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'staff_id']);
        });

        // Certificates issued (leaving, character, bonafide) with a serial number.
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('serial_no', 30);
            $table->date('issued_on');
            $table->json('details')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'type']);
        });

        // Front office: visitor log and complaints / suggestions.
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('phone', 30)->nullable();
            $table->string('national_id', 40)->nullable();
            $table->string('purpose', 150);
            $table->string('to_meet', 120)->nullable();
            $table->unsignedSmallInteger('people')->default(1);
            $table->timestamp('in_at');
            $table->timestamp('out_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'in_at']);
        });

        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('kind', 12)->default('complaint'); // complaint | suggestion
            $table->string('from_name', 120);
            $table->string('phone', 30)->nullable();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('category', 40)->nullable();
            $table->text('details');
            $table->string('status', 12)->default('open'); // open | in_progress | resolved
            $table->text('response')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        foreach (['complaints', 'visitors', 'certificates', 'observations', 'incidents'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
