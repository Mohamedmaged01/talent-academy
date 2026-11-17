<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, array $attributes = []): User
    {
        return User::create($attributes + [
            'name' => ucfirst($role), 'email' => $role.uniqid().'@test.local', 'role' => $role, 'password' => 'password',
        ]);
    }

    private function makeBatch(?User $teacher = null): Batch
    {
        $branch = Branch::firstOrCreate(['code' => 'T-01'], ['name_ar' => 'فرع', 'name_en' => 'Branch']);
        $course = Course::create(['code' => 'C'.uniqid(), 'pathway' => 'training', 'title_ar' => 'برنامج', 'title_en' => 'Program', 'price' => 1000]);

        return Batch::create([
            'branch_id' => $branch->id, 'course_id' => $course->id, 'teacher_id' => $teacher?->id,
            'code' => 'B'.uniqid(), 'name' => 'Class', 'capacity' => 10, 'status' => 'active', 'start_date' => now()->subWeek(),
        ]);
    }

    private function makeStudent(?User $user = null): Student
    {
        $branch = Branch::firstOrCreate(['code' => 'T-01'], ['name_ar' => 'فرع', 'name_en' => 'Branch']);

        return Student::create(['branch_id' => $branch->id, 'code' => Student::nextCode(), 'full_name' => 'طالب '.uniqid(), 'user_id' => $user?->id]);
    }

    public function test_login_sends_each_role_to_its_own_portal(): void
    {
        foreach (['admin' => '/admin', 'teacher' => '/teacher', 'student' => '/student', 'parent' => '/parent'] as $role => $path) {
            $user = $this->makeUser($role);

            $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect($path);
            $this->post('/logout');
        }
    }

    public function test_login_ignores_an_intended_url_from_another_portal(): void
    {
        $teacher = $this->makeUser('teacher');

        // A guest hits the admin portal, gets bounced to login, then signs in as a teacher.
        $this->get('/admin')->assertRedirect('/login');
        $this->post('/login', ['email' => $teacher->email, 'password' => 'password'])->assertRedirect('/teacher');
    }

    public function test_login_keeps_an_intended_url_inside_the_users_own_portal(): void
    {
        $teacher = $this->makeUser('teacher');

        $this->get('/teacher/exams')->assertRedirect('/login');
        $this->post('/login', ['email' => $teacher->email, 'password' => 'password'])->assertRedirect('/teacher/exams');
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = $this->makeUser('admin');

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_accounts_cannot_sign_in(): void
    {
        $user = $this->makeUser('admin', ['is_active' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $user = $this->makeUser('admin');

        foreach (range(1, 5) as $_) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertStatus(429);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        foreach (['/admin', '/teacher', '/student', '/parent', '/profile'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }

    public function test_roles_cannot_open_other_portals(): void
    {
        $student = $this->makeUser('student');
        $teacher = $this->makeUser('teacher');
        $parent = $this->makeUser('parent');

        $this->actingAs($student)->get('/admin')->assertForbidden();
        $this->actingAs($student)->get('/teacher')->assertForbidden();
        $this->actingAs($teacher)->get('/admin/students')->assertForbidden();
        $this->actingAs($parent)->get('/student')->assertForbidden();
        $this->actingAs($teacher)->post('/admin/expenses', [])->assertForbidden();
    }

    public function test_teacher_cannot_touch_another_teachers_class(): void
    {
        $owner = $this->makeUser('teacher');
        $intruder = $this->makeUser('teacher');
        $batch = $this->makeBatch($owner);

        $this->actingAs($intruder)->get(route('teacher.batches.show', $batch))->assertForbidden();
        $this->actingAs($intruder)->get(route('teacher.attendance.edit', $batch))->assertForbidden();
        $this->actingAs($intruder)->put(route('teacher.attendance.update', $batch), ['date' => now()->toDateString(), 'status' => []])->assertForbidden();
        $this->actingAs($intruder)->post(route('teacher.assignments.store'), [
            'batch_id' => $batch->id, 'title' => 'x', 'due_at' => now()->addDay()->toDateTimeString(), 'max_score' => 10,
        ])->assertForbidden();

        $this->actingAs($owner)->get(route('teacher.batches.show', $batch))->assertOk();
    }

    public function test_attendance_only_records_students_on_the_roster(): void
    {
        $teacher = $this->makeUser('teacher');
        $batch = $this->makeBatch($teacher);
        $enrolled = $this->makeStudent();
        $outsider = $this->makeStudent();
        $batch->enrollments()->create(['student_id' => $enrolled->id, 'status' => 'active']);

        $this->actingAs($teacher)->put(route('teacher.attendance.update', $batch), [
            'date' => now()->toDateString(),
            'status' => [$enrolled->id => 'absent', $outsider->id => 'present'],
        ])->assertRedirect();

        $this->assertDatabaseHas('attendance_records', ['student_id' => $enrolled->id, 'status' => 'absent']);
        $this->assertDatabaseMissing('attendance_records', ['student_id' => $outsider->id]);

        // Saving again for the same day updates instead of duplicating.
        $this->actingAs($teacher)->put(route('teacher.attendance.update', $batch), [
            'date' => now()->toDateString(), 'status' => [$enrolled->id => 'late'],
        ]);
        $this->assertSame(1, $batch->attendance()->count());
        $this->assertSame('late', $batch->attendance()->first()->status);
    }

    public function test_attendance_cannot_be_recorded_for_future_dates(): void
    {
        $teacher = $this->makeUser('teacher');
        $batch = $this->makeBatch($teacher);

        $this->actingAs($teacher)->put(route('teacher.attendance.update', $batch), [
            'date' => now()->addDays(3)->toDateString(), 'status' => [],
        ])->assertSessionHasErrors('date');
    }

    public function test_parent_sees_only_their_own_children(): void
    {
        $parent = $this->makeUser('parent');
        $child = $this->makeStudent();
        $stranger = $this->makeStudent();
        $parent->children()->attach($child->id, ['relationship' => 'father']);

        $this->actingAs($parent)->get(route('parent.children.show', $child))->assertOk();
        $this->actingAs($parent)->get(route('parent.children.show', $stranger))->assertForbidden();
    }

    public function test_student_cannot_open_a_class_they_are_not_in(): void
    {
        $user = $this->makeUser('student');
        $this->makeStudent($user);
        $batch = $this->makeBatch();

        $this->actingAs($user)->get(route('student.courses.show', $batch))->assertForbidden();
    }

    public function test_student_sees_exam_results_only_after_they_are_published(): void
    {
        $teacher = $this->makeUser('teacher');
        $user = $this->makeUser('student');
        $student = $this->makeStudent($user);
        $batch = $this->makeBatch($teacher);
        $batch->enrollments()->create(['student_id' => $student->id, 'status' => 'active']);
        $exam = $batch->exams()->create(['title' => 'Quiz Alpha', 'exam_date' => now()->subDay(), 'max_score' => 20, 'pass_score' => 10]);

        $this->actingAs($teacher)->put(route('teacher.exams.results', $exam), ['scores' => [$student->id => 17], 'publish' => 0]);
        $this->actingAs($user)->get(route('student.grades'))->assertDontSee('Quiz Alpha');

        $this->actingAs($teacher)->put(route('teacher.exams.results', $exam), ['scores' => [$student->id => 17], 'publish' => 1]);
        $this->actingAs($user)->get(route('student.grades'))->assertSee('Quiz Alpha');
        $this->assertSame(85.0, $student->fresh()->averageExamPercent());
    }

    public function test_exam_scores_above_maximum_are_rejected(): void
    {
        $teacher = $this->makeUser('teacher');
        $student = $this->makeStudent();
        $batch = $this->makeBatch($teacher);
        $batch->enrollments()->create(['student_id' => $student->id, 'status' => 'active']);
        $exam = $batch->exams()->create(['title' => 'Q', 'exam_date' => now(), 'max_score' => 20, 'pass_score' => 10]);

        $this->actingAs($teacher)->put(route('teacher.exams.results', $exam), ['scores' => [$student->id => 25]])
            ->assertSessionHasErrors("scores.{$student->id}");
    }

    public function test_deactivating_a_user_from_admin_signs_them_out(): void
    {
        $admin = $this->makeUser('admin');
        $teacher = $this->makeUser('teacher');

        $this->actingAs($admin)->patch(route('admin.users.toggle', $teacher))->assertRedirect();
        $this->assertFalse($teacher->fresh()->is_active);

        $this->actingAs($teacher->fresh())->get('/teacher')->assertRedirect('/login');
    }

    public function test_admin_cannot_deactivate_themselves(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->patch(route('admin.users.toggle', $admin))->assertSessionHas('error');
        $this->assertTrue($admin->fresh()->is_active);
    }
}
