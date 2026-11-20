<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Teacher tools: notes on a student's file and certificate nominations that an
 * administrator approves (issuing the certificate) or rejects.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->boolean('visible_to_parent')->default(false);
            $table->timestamps();
        });

        Schema::create('certificate_nominations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('nominated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note')->nullable();
            $table->foreignId('certificate_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['batch_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_nominations');
        Schema::dropIfExists('student_notes');
    }
};
