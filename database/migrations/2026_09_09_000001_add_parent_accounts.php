<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Parents often have no email: they sign in with their phone number instead.
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->index(['school_id', 'phone']);
        });

        // Which children a parent login can see.
        Schema::create('guardian_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_student');
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['school_id', 'phone']);
        });
    }
};
