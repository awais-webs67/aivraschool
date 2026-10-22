<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every student, with parents / guardian on the same row (simple to fill and to import).
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('admission_no', 30);
            $table->string('roll_no', 20)->nullable();
            $table->string('name', 120);
            $table->string('gender', 10)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('national_id', 40)->nullable(); // B-Form / birth certificate no.
            $table->string('religion', 40)->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->string('photo_path')->nullable();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->date('admission_date')->nullable();
            $table->string('category', 30)->nullable(); // scholarship, staff child, orphan …
            $table->decimal('fee_discount', 5, 2)->default(0); // % off monthly fees
            $table->string('father_name', 120)->nullable();
            $table->string('father_phone', 30)->nullable();
            $table->string('father_national_id', 40)->nullable();
            $table->string('father_occupation', 80)->nullable();
            $table->string('mother_name', 120)->nullable();
            $table->string('mother_phone', 30)->nullable();
            $table->string('guardian_name', 120)->nullable();
            $table->string('guardian_relation', 40)->nullable();
            $table->string('guardian_phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 80)->nullable();
            $table->string('previous_school', 150)->nullable();
            $table->text('medical_notes')->nullable();
            $table->string('family_code', 40)->nullable(); // same code = siblings
            $table->string('status', 12)->default('active'); // active | left | graduated
            $table->date('left_on')->nullable();
            $table->string('left_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'admission_no']);
            $table->index(['school_id', 'status', 'class_id', 'section_id']);
            $table->index(['school_id', 'family_code']);
        });

        // Admission enquiries (walk-in, phone, website form) and their follow-up.
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('child_name', 120);
            $table->string('gender', 10)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->string('parent_name', 120)->nullable();
            $table->string('phone', 30);
            $table->string('email', 150)->nullable();
            $table->string('address')->nullable();
            $table->string('previous_school', 150)->nullable();
            $table->string('source', 20)->default('walk_in');
            $table->string('status', 20)->default('new');
            $table->date('follow_up_on')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
        Schema::dropIfExists('students');
    }
};
