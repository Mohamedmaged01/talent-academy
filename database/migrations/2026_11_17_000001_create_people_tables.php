<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('instructor_type')->default('academic_teacher');
            $table->string('title_ar')->nullable();
            $table->string('title_en')->nullable();
            $table->string('specialization_ar')->nullable();
            $table->string('specialization_en')->nullable();
            $table->text('bio')->nullable();
            $table->string('national_id')->nullable();
            $table->date('hire_date')->nullable();
            $table->decimal('monthly_salary', 12, 2)->default(0);
            $table->string('office_hours')->nullable();
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('full_name');
            $table->string('full_name_en')->nullable();
            $table->string('national_id')->nullable();
            $table->string('gender', 10)->default('male');
            $table->date('birth_date')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('pathway')->default('academic');
            $table->string('grade_level')->nullable();
            $table->string('school_name')->nullable();
            $table->string('address')->nullable();
            $table->string('status')->default('active');
            $table->date('enrolled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
        });

        Schema::create('guardian_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guardian_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('relationship')->default('father');
            $table->timestamps();

            $table->unique(['guardian_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_student');
        Schema::dropIfExists('students');
        Schema::dropIfExists('teacher_profiles');
    }
};
