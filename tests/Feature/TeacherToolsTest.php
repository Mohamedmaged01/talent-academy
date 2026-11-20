<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\CertificateNomination;
use App\Models\Exam;
use App\Models\ExamResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsFixtures;
use Tests\TestCase;

class TeacherToolsTest extends TestCase
{
    use BuildsFixtures, RefreshDatabase;

    /** Give a student the attendance and exam record needed to (not) qualify for a certificate. */
    private function record($batch, $student, int $presentOutOfTen, float $score): void
    {
        foreach (range(1, 10) as $day) {
            AttendanceRecord::create(['batch_id' => $batch->id, 'student_id' => $student->id, 'date' => now()->subDays($day)->toDateString(), 'status' => $day <= $presentOutOfTen ? 'present' : 'absent']);
        }

        $exam = Exam::firstOrCreate(['batch_id' => $batch->id, 'title' => 'Final'], ['type' => 'final', 'exam_date' => today(), 'max_score' => 100, 'pass_score' => 50, 'results_published' => true]);
        ExamResult::create(['exam_id' => $exam->id, 'student_id' => $student->id, 'score' => $score]);
    }

    public function test_teacher_notes_are_scoped_and_shared_with_parents_only_when_chosen(): void
    {
        $teacher = $this->makeUser('teacher');
        $batch = $this->makeBatch($teacher);
        $student = $this->makeStudent([$batch]);
        $stranger = $this->makeStudent([$this->makeBatch()]);
        $parent = $this->makeUser('parent');
        $parent->children()->attach($student->id, ['relationship' => 'mother']);

        $this->actingAs($teacher)->post(route('teacher.students.notes.store', $student), ['body' => 'Great progress', 'visible_to_parent' => 1])->assertRedirect();
        $this->actingAs($teacher)->post(route('teacher.students.notes.store', $student), ['body' => 'Watch homework deadlines'])->assertRedirect();
        $this->actingAs($teacher)->post(route('teacher.students.notes.store', $stranger), ['body' => 'x'])->assertForbidden();
        $this->actingAs($teacher)->get(route('teacher.students.show', $stranger))->assertForbidden();

        $this->actingAs($parent)->get(route('parent.children.show', $student))
            ->assertOk()->assertSee('Great progress')->assertDontSee('Watch homework deadlines');
    }

    public function test_only_eligible_students_can_be_nominated_and_approval_issues_the_certificate(): void
    {
        $teacher = $this->makeUser('teacher');
        $batch = $this->makeBatch($teacher);
        $good = $this->makeStudent([$batch]);
        $weak = $this->makeStudent([$batch]);
        $this->record($batch, $good, 9, 85);
        $this->record($batch, $weak, 6, 90); // 60% attendance: not eligible

        $this->actingAs($teacher)->post(route('teacher.certificates.nominate', $batch), ['students' => [$good->id, $weak->id]])->assertRedirect();
        $this->assertSame(1, CertificateNomination::count());
        $nomination = CertificateNomination::first();
        $this->assertSame($good->id, $nomination->student_id);

        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->post(route('admin.nominations.approve', $nomination))->assertRedirect();

        $nomination->refresh();
        $this->assertSame('approved', $nomination->status);
        $this->assertNotNull($nomination->certificate_id);
        $this->assertSame('Very Good', $nomination->certificate->grade);
        $this->assertSame(1, $teacher->unreadNotifications()->count());
    }

    public function test_rejected_nomination_can_be_sent_again(): void
    {
        $teacher = $this->makeUser('teacher');
        $batch = $this->makeBatch($teacher);
        $student = $this->makeStudent([$batch]);
        $this->record($batch, $student, 10, 95);

        $this->actingAs($teacher)->post(route('teacher.certificates.nominate', $batch), ['students' => [$student->id]]);
        $nomination = CertificateNomination::first();
        $this->actingAs($this->makeUser('admin'))->post(route('admin.nominations.reject', $nomination), ['review_note' => 'Pending fees'])->assertRedirect();
        $this->assertSame('rejected', $nomination->fresh()->status);

        $this->actingAs($teacher)->post(route('teacher.certificates.nominate', $batch), ['students' => [$student->id]]);
        $this->assertSame('pending', $nomination->fresh()->status);
    }

    public function test_class_report_exports_csv_only_for_own_class(): void
    {
        $teacher = $this->makeUser('teacher');
        $batch = $this->makeBatch($teacher);
        $student = $this->makeStudent([$batch]);
        $this->record($batch, $student, 5, 40);

        $response = $this->actingAs($teacher)->get(route('teacher.reports.export', $batch));
        $response->assertOk();
        $this->assertStringContainsString($student->code, $response->streamedContent());
        $this->assertStringContainsString('yes', $response->streamedContent()); // at risk

        $this->actingAs($teacher)->get(route('teacher.reports.export', $this->makeBatch()))->assertForbidden();
    }
}
