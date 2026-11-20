<?php

namespace Tests\Feature\Concerns;

use App\Models\Batch;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;

/** Small, explicit fixtures for feature tests that do not need the full demo seed. */
trait BuildsFixtures
{
    protected function makeUser(string $role, array $attributes = []): User
    {
        return User::create($attributes + [
            'name' => ucfirst($role).' '.uniqid(), 'email' => $role.uniqid().'@test.local', 'role' => $role, 'password' => 'password',
        ]);
    }

    protected function branch(): Branch
    {
        return Branch::firstOrCreate(['code' => 'T-01'], ['name_ar' => 'فرع', 'name_en' => 'Branch', 'status' => 'active']);
    }

    protected function makeCourse(string $pathway = 'training'): Course
    {
        return Course::create(['code' => 'C'.uniqid(), 'pathway' => $pathway, 'title_ar' => 'برنامج', 'title_en' => 'Program', 'price' => 1000]);
    }

    protected function makeBatch(?User $teacher = null, ?Course $course = null, array $attributes = []): Batch
    {
        return Batch::create($attributes + [
            'branch_id' => $this->branch()->id, 'course_id' => ($course ?? $this->makeCourse())->id, 'teacher_id' => $teacher?->id,
            'code' => 'B'.uniqid(), 'name' => 'Class '.uniqid(), 'capacity' => 10, 'status' => 'active', 'start_date' => now()->subWeek(),
        ]);
    }

    /** A student with a portal account, enrolled in the given batches. */
    protected function makeStudent(array $batches = [], ?User $user = null): Student
    {
        $user ??= $this->makeUser('student');
        $student = Student::create(['branch_id' => $this->branch()->id, 'code' => Student::nextCode(), 'full_name' => 'طالب '.uniqid(), 'user_id' => $user->id]);

        foreach ($batches as $batch) {
            $batch->enrollments()->create(['student_id' => $student->id, 'status' => 'active', 'enrolled_at' => now()->subWeek()]);
        }

        return $student;
    }
}
