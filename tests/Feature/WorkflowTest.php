<?php

namespace Tests\Feature;

use App\Models\AdmissionRequest;
use App\Models\Batch;
use App\Models\Branch;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Inquiry;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** End-to-end journeys across the public site and portals. */
class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Branch $branch;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'role' => 'admin', 'password' => 'password']);
        $this->branch = Branch::create(['code' => 'BMZ', 'name_ar' => 'بني مزار', 'name_en' => 'Beni Mazar']);
        $this->course = Course::create(['code' => 'AI-1', 'pathway' => 'training', 'title_ar' => 'ذكاء اصطناعي', 'title_en' => 'AI', 'price' => 6000]);
    }

    private function batch(?User $teacher = null, int $capacity = 10): Batch
    {
        return Batch::create([
            'branch_id' => $this->branch->id, 'course_id' => $this->course->id, 'teacher_id' => $teacher?->id,
            'code' => 'B'.uniqid(), 'name' => 'AI Group', 'capacity' => $capacity, 'status' => 'active', 'start_date' => now()->subMonth(),
        ]);
    }

    public function test_public_application_is_received_and_converted_into_an_enrolled_student(): void
    {
        $batch = $this->batch();

        $response = $this->post(route('admissions.store'), [
            'full_name' => 'ندى محمد', 'phone' => '01123456789', 'pathway' => 'training', 'course_id' => $this->course->id,
        ]);

        $admission = AdmissionRequest::firstOrFail();
        $response->assertRedirect(route('admissions.thanks', $admission->reference));
        $this->get(route('admissions.thanks', $admission->reference))->assertSee($admission->reference);

        $this->actingAs($this->admin)->post(route('admin.admissions.convert', $admission), [
            'branch_id' => $this->branch->id, 'batch_id' => $batch->id,
        ])->assertRedirect();

        $student = Student::where('full_name', 'ندى محمد')->firstOrFail();
        $this->assertSame('accepted', $admission->fresh()->status);
        $this->assertSame($student->id, $admission->fresh()->student_id);
        $this->assertTrue($batch->students()->whereKey($student->id)->exists());

        // Converting twice must not create a duplicate student.
        $this->actingAs($this->admin)->post(route('admin.admissions.convert', $admission), ['branch_id' => $this->branch->id])->assertSessionHas('error');
        $this->assertSame(1, Student::count());
    }

    public function test_application_validates_phone_and_pathway(): void
    {
        $this->post(route('admissions.store'), ['full_name' => 'x', 'phone' => 'abc', 'pathway' => 'nope'])
            ->assertSessionHasErrors(['phone', 'pathway']);
        $this->assertSame(0, AdmissionRequest::count());
    }

    public function test_contact_form_creates_an_inquiry(): void
    {
        $this->post(route('contact.store'), ['name' => 'Mona', 'phone' => '0100', 'message' => 'I would like to know the physics schedule.'])
            ->assertSessionHas('contact_success');

        $this->assertSame(1, Inquiry::where('status', 'new')->count());
    }

    public function test_enrollment_respects_class_capacity(): void
    {
        $batch = $this->batch(capacity: 2);
        $students = collect(range(1, 3))->map(fn ($i) => Student::create(['branch_id' => $this->branch->id, 'code' => "S{$i}", 'full_name' => "Student {$i}"]));

        $this->actingAs($this->admin)->post(route('admin.batches.enroll', $batch), ['student_ids' => $students->pluck('id')->all()])
            ->assertSessionHas('error');
        $this->assertSame(0, $batch->enrollments()->count());

        $this->actingAs($this->admin)->post(route('admin.batches.enroll', $batch), ['student_ids' => $students->take(2)->pluck('id')->all()])
            ->assertSessionHas('success');
        $this->assertSame(2, $batch->enrollments()->count());
    }

    public function test_certificates_are_issued_only_to_eligible_students_and_can_be_verified_and_revoked(): void
    {
        $teacher = User::create(['name' => 'T', 'email' => 't@test.local', 'role' => 'teacher', 'password' => 'password']);
        $batch = $this->batch($teacher);
        $good = Student::create(['branch_id' => $this->branch->id, 'code' => 'G1', 'full_name' => 'Good Student']);
        $weak = Student::create(['branch_id' => $this->branch->id, 'code' => 'W1', 'full_name' => 'Weak Student']);
        foreach ([$good, $weak] as $s) {
            $batch->enrollments()->create(['student_id' => $s->id, 'status' => 'active']);
        }

        // 10 sessions: good attends all, weak misses 4 (60%).
        foreach (range(1, 10) as $d) {
            $date = now()->subDays($d)->toDateString();
            $batch->attendance()->create(['student_id' => $good->id, 'date' => $date, 'status' => 'present']);
            $batch->attendance()->create(['student_id' => $weak->id, 'date' => $date, 'status' => $d <= 4 ? 'absent' : 'present']);
        }
        $exam = $batch->exams()->create(['title' => 'Final', 'exam_date' => now()->subDay(), 'max_score' => 100, 'pass_score' => 50, 'results_published' => true]);
        $exam->results()->create(['student_id' => $good->id, 'score' => 92]);
        $exam->results()->create(['student_id' => $weak->id, 'score' => 88]);

        $this->actingAs($this->admin)->post(route('admin.certificates.issue-batch', $batch))->assertSessionHas('success');

        $this->assertSame(1, Certificate::count());
        $certificate = Certificate::firstOrFail();
        $this->assertSame($good->id, $certificate->student_id);
        $this->assertSame('Excellent', $certificate->grade);

        // Issuing again does not duplicate.
        $this->actingAs($this->admin)->post(route('admin.certificates.issue-batch', $batch))->assertSessionHas('warning');
        $this->assertSame(1, Certificate::count());

        $this->get(route('certificates.verify', ['serial' => strtolower($certificate->serial)]))->assertSee('Good Student');

        $this->actingAs($this->admin)->post(route('admin.certificates.revoke', $certificate), ['revoked_reason' => 'Issued in error']);
        $this->assertSame('revoked', $certificate->fresh()->status);
        $this->get(route('certificates.verify', ['serial' => $certificate->serial]))->assertDontSee('Good Student');
    }

    public function test_student_submits_assignment_and_teacher_grades_it(): void
    {
        Storage::fake('public');

        $teacher = User::create(['name' => 'T', 'email' => 't2@test.local', 'role' => 'teacher', 'password' => 'password']);
        $studentUser = User::create(['name' => 'S', 'email' => 's@test.local', 'role' => 'student', 'password' => 'password']);
        $student = Student::create(['branch_id' => $this->branch->id, 'code' => 'S9', 'full_name' => 'S', 'user_id' => $studentUser->id]);
        $batch = $this->batch($teacher);
        $batch->enrollments()->create(['student_id' => $student->id, 'status' => 'active']);

        $this->actingAs($teacher)->post(route('teacher.assignments.store'), [
            'batch_id' => $batch->id, 'title' => 'Build a chatbot', 'due_at' => now()->addDays(3)->format('Y-m-d H:i'), 'max_score' => 20,
        ])->assertRedirect();
        $assignment = $batch->assignments()->firstOrFail();

        $this->actingAs($studentUser)->post(route('student.assignments.submit', $assignment), [
            'body' => 'My answer', 'attachment' => UploadedFile::fake()->create('answer.pdf', 120, 'application/pdf'),
        ])->assertSessionHas('success');

        $submission = $assignment->submissions()->firstOrFail();
        Storage::disk('public')->assertExists($submission->attachment_path);

        $this->actingAs($teacher)->patch(route('teacher.submissions.grade', $submission), ['score' => 25])->assertSessionHasErrors('score');
        $this->actingAs($teacher)->patch(route('teacher.submissions.grade', $submission), ['score' => 18, 'feedback' => 'Great'])->assertSessionHas('success');

        // Once graded, the student can no longer change the submission.
        $this->actingAs($studentUser)->post(route('student.assignments.submit', $assignment), ['body' => 'changed'])->assertSessionHas('error');
        $this->assertSame('My answer', $submission->fresh()->body);
    }

    public function test_language_switch_changes_direction(): void
    {
        $this->get('/')->assertSee('dir="rtl"', false)->assertSee('أكاديمية تالنت');
        $this->get(route('locale.switch', 'en'))->assertRedirect();
        $this->get('/')->assertSee('dir="ltr"', false)->assertSee('Your Journey to a Brighter Future');
        $this->get(route('locale.switch', 'fr'))->assertNotFound();
    }

    public function test_branch_with_records_is_archived_instead_of_deleted(): void
    {
        Student::create(['branch_id' => $this->branch->id, 'code' => 'X', 'full_name' => 'X']);

        $this->actingAs($this->admin)->delete(route('admin.branches.destroy', $this->branch))->assertSessionHas('warning');
        $this->assertSame('archived', $this->branch->fresh()->status);
    }

    public function test_admin_can_create_student_with_portal_login(): void
    {
        $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'full_name' => 'سارة علي', 'branch_id' => $this->branch->id, 'gender' => 'female', 'pathway' => 'academic',
            'status' => 'active', 'login_email' => 'sara@test.local', 'login_password' => 'secret123',
        ])->assertRedirect();

        $student = Student::where('full_name', 'سارة علي')->firstOrFail();
        $this->assertNotNull($student->user);
        $this->assertSame('student', $student->user->role);

        $this->post('/logout');
        $this->post('/login', ['email' => 'sara@test.local', 'password' => 'secret123'])->assertRedirect('/student');
    }
}
