<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Everyone who works at a school (teachers, office, support staff). A staff member may have
     * a portal login (users row) or not, e.g. a guard or driver.
     */
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('employee_no', 30);
            $table->string('name', 120);
            $table->string('father_name', 120)->nullable();
            $table->string('gender', 10)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('national_id', 40)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('address')->nullable();
            $table->string('designation', 80)->nullable();
            $table->string('department', 80)->nullable();
            $table->string('qualification', 150)->nullable();
            $table->date('joining_date')->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->string('photo_path')->nullable();
            $table->string('status', 10)->default('active'); // active | left
            $table->date('left_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'employee_no']);
            $table->index(['school_id', 'status']);
        });

        // Class teacher of each section.
        Schema::table('sections', function (Blueprint $table) {
            $table->foreignId('class_teacher_id')->nullable()->after('capacity')->constrained('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('class_teacher_id');
        });
        Schema::dropIfExists('staff');
    }
};
