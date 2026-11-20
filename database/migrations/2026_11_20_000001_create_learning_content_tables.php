<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Learning content: recorded lectures, live-class links, the question bank,
 * online (auto-graded) quizzes, the digital library and the downloads center.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->string('meeting_url', 500)->nullable()->after('room');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->string('mode')->default('paper')->after('type');
            $table->text('instructions')->nullable()->after('mode');
            $table->dateTime('closes_at')->nullable()->after('duration_minutes');
        });

        Schema::create('lectures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('video_url', 500);
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('lecture_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lecture_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('views')->default(1);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();

            $table->unique(['lecture_id', 'student_id']);
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('mcq');
            $table->text('body');
            $table->json('options')->nullable();
            $table->string('correct_answer');
            $table->unsignedInteger('points')->default(1);
            $table->string('difficulty')->default('medium');
            $table->text('explanation')->nullable();
            $table->timestamps();

            $table->index(['course_id', 'type']);
        });

        Schema::create('exam_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            $table->unique(['exam_id', 'question_id']);
        });

        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('submitted_at')->nullable();
            $table->json('answers')->nullable();
            $table->decimal('score', 6, 2)->nullable();
            $table->string('status')->default('in_progress');
            $table->timestamps();

            $table->unique(['exam_id', 'student_id']);
        });

        Schema::create('library_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('type')->default('book');
            $table->string('pathway')->default('all');
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('url', 500)->nullable();
            $table->unsignedInteger('pages')->nullable();
            $table->unsignedInteger('downloads_count')->default(0);
            $table->boolean('is_published')->default(true);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->string('category')->default('forms');
            $table->string('audience')->default('public');
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('url', 500)->nullable();
            $table->unsignedInteger('downloads_count')->default(0);
            $table->boolean('is_published')->default(true);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['downloads', 'library_items', 'exam_attempts', 'exam_question', 'questions', 'lecture_views', 'lectures'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('exams', fn (Blueprint $table) => $table->dropColumn(['mode', 'instructions', 'closes_at']));
        Schema::table('batches', fn (Blueprint $table) => $table->dropColumn('meeting_url'));
    }
};
