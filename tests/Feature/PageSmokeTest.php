<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ReportController;
use App\Models\Account;
use App\Models\AdminRole;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Download;
use App\Models\Exam;
use App\Models\Invoice;
use App\Models\Lecture;
use App\Models\LibraryItem;
use App\Models\NewsArticle;
use App\Models\Payment;
use App\Models\PayrollRecord;
use App\Models\Question;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Renders every page of the application, in both languages, as the role that
 * owns it. Catches broken Blade templates, missing relations and bad routes.
 */
class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // RefreshDatabase migrates the in-memory DB once per process, so `$seed = true`
        // only applies if this class runs first. Seed explicitly inside each test's transaction.
        $this->seed();
    }

    public function test_public_pages_render_in_both_languages(): void
    {
        $course = Course::first();
        $article = NewsArticle::first();
        $certificate = Certificate::first();

        foreach (['ar', 'en'] as $locale) {
            $this->withSession(['locale' => $locale]);

            foreach ([
                '/', '/about', '/privacy', '/terms', '/programs', '/programs?pathway=training', '/programs?q=AI',
                "/programs/{$course->code}", '/news', "/news/{$article->slug}", '/apply', '/verify',
                '/verify?serial='.$certificate->serial, '/verify?serial=NOPE', '/login',
            ] as $url) {
                $this->get($url)->assertOk();
            }
        }
    }

    public function test_every_admin_page_renders(): void
    {
        $admin = User::where('email', 'admin@talent.test')->first();
        $batch = Batch::where('status', 'completed')->first();

        $urls = [
            route('admin.dashboard'),
            route('admin.branches.index'), route('admin.branches.create'), route('admin.branches.edit', 1),
            route('admin.students.index'), route('admin.students.index', ['pathway' => 'academic', 'q' => 'م']), route('admin.students.create'),
            route('admin.students.show', 1), route('admin.students.edit', 1),
            route('admin.teachers.index'), route('admin.teachers.create'),
            route('admin.teachers.show', User::where('role', 'teacher')->first()), route('admin.teachers.edit', User::where('role', 'teacher')->first()),
            route('admin.guardians.index'), route('admin.guardians.create'), route('admin.guardians.edit', User::where('role', 'parent')->first()),
            route('admin.courses.index'), route('admin.courses.create'), route('admin.courses.edit', 1),
            route('admin.batches.index'), route('admin.batches.create'), route('admin.batches.show', $batch), route('admin.batches.edit', $batch),
            route('admin.timetable'), route('admin.attendance'),
            route('admin.admissions.index'), route('admin.admissions.show', 1),
            route('admin.inquiries.index'),
            route('admin.finance'), route('admin.invoices.index'), route('admin.invoices.index', ['status' => 'overdue']), route('admin.invoices.create'),
            route('admin.invoices.show', Invoice::first()), route('admin.payments.index'), route('admin.payments.receipt', Payment::first()),
            route('admin.expenses.index'),
            route('admin.certificates.index'), route('admin.certificates.create'), route('admin.certificates.show', Certificate::first()),
            route('admin.news.index'), route('admin.news.create'), route('admin.news.edit', NewsArticle::first()),
            route('admin.announcements.index'), route('admin.users.index'), route('admin.settings.edit'), route('admin.activity'),
            route('profile.edit'),
        ];

        foreach (['ar', 'en'] as $locale) {
            foreach ($urls as $url) {
                $this->actingAs($admin)->withSession(['locale' => $locale])->get($url)->assertOk();
            }
        }
    }

    public function test_every_teacher_page_renders(): void
    {
        $teacher = User::where('email', 'teacher@talent.test')->first();
        $batch = $teacher->taughtBatches()->where('status', 'active')->first();
        $assignment = Assignment::where('batch_id', $batch->id)->first();
        $exam = Exam::where('batch_id', $batch->id)->first();

        foreach ([
            route('teacher.dashboard'), route('teacher.schedule'), route('teacher.batches.index'),
            route('teacher.batches.show', $batch), route('teacher.attendance.edit', $batch),
            route('teacher.attendance.edit', [$batch, 'date' => now()->subWeek()->toDateString()]),
            route('teacher.assignments.index'), route('teacher.assignments.create'), route('teacher.assignments.show', $assignment),
            route('teacher.exams.index'), route('teacher.exams.create'), route('teacher.exams.show', $exam),
            route('teacher.announcements'), route('profile.edit'),
        ] as $url) {
            $this->actingAs($teacher)->get($url)->assertOk();
        }
    }

    public function test_every_student_page_renders(): void
    {
        $user = User::where('email', 'student@talent.test')->first();
        $student = $user->studentRecord;
        $batch = $student->batches()->first();
        $assignment = Assignment::where('batch_id', $batch->id)->first();

        foreach ([
            route('student.dashboard'), route('student.schedule'), route('student.courses'), route('student.courses.show', $batch),
            route('student.assignments.index'), route('student.assignments.show', $assignment), route('student.grades'),
            route('student.attendance'), route('student.payments'), route('student.certificates'), route('student.announcements'),
        ] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_every_parent_page_renders(): void
    {
        $parent = User::where('email', 'parent@talent.test')->first();

        $this->actingAs($parent)->get(route('parent.dashboard'))->assertOk()->assertSee($parent->children->first()->full_name);
        $this->actingAs($parent)->get(route('parent.children.show', $parent->children->first()))->assertOk();
        $this->actingAs($parent)->get(route('parent.announcements'))->assertOk();
    }

    public function test_every_new_admin_page_renders_in_both_languages(): void
    {
        $admin = User::where('email', 'admin@talent.test')->first();
        $onlineExam = Exam::where('mode', 'online')->first();
        $paid = PayrollRecord::where('status', 'paid')->first();
        $thread = $admin->messageThreads()->first();

        $urls = [
            route('admin.exams.index'), route('admin.exams.index', ['mode' => 'online', 'status' => 'draft']), route('admin.exams.show', $onlineExam),
            route('admin.questions.index'), route('admin.questions.index', ['type' => 'mcq']), route('admin.questions.create'), route('admin.questions.edit', Question::first()),
            route('admin.lectures.index'),
            route('admin.library.index'), route('admin.library.create'), route('admin.library.edit', LibraryItem::first()),
            route('admin.downloads.index'), route('admin.downloads.create'), route('admin.downloads.edit', Download::first()),
            route('admin.broadcast.create'), route('admin.support.index'), route('admin.support.index', ['status' => 'all']), route('admin.support.show', SupportTicket::first()),
            route('admin.payroll.index'), route('admin.payroll.index', ['period' => $paid->period]), route('admin.payroll.payslip', $paid),
            route('admin.ledger.index'), route('admin.ledger.index', ['kind' => 'manual']), route('admin.ledger.create'), route('admin.ledger.trial-balance'),
            route('admin.ledger.accounts'), route('admin.ledger.accounts.show', Account::where('code', '1100')->first()),
            route('admin.nominations.index'), route('admin.nominations.index', ['status' => 'all']),
            route('admin.content.edit'), route('admin.media.index'),
            route('admin.roles.index'), route('admin.roles.create'), route('admin.roles.edit', AdminRole::first()), route('admin.delegations.index'),
            route('admin.integrations.index'), route('admin.backups.index'),
            route('admin.security'), route('admin.security', ['tab' => 'verifications']), route('admin.security', ['tab' => 'integrity']),
            route('admin.assistant'),
            route('messages.index'), route('messages.create'), route('messages.show', $thread), route('notifications.index'), route('downloads.portal'),
        ];

        foreach (ReportController::TABS as $tab) {
            $urls[] = route('admin.reports', ['tab' => $tab]);
        }

        foreach (['ar', 'en'] as $locale) {
            foreach ($urls as $url) {
                $this->actingAs($admin)->withSession(['locale' => $locale])->get($url)->assertOk();
            }
        }
    }

    public function test_every_new_teacher_page_renders(): void
    {
        $teacher = User::where('email', 'teacher@talent.test')->first();
        $batch = $teacher->taughtBatches()->where('status', 'active')->first();
        $student = $batch->activeStudents()->first();
        $ticket = SupportTicket::where('user_id', $teacher->id)->first();

        foreach ([
            route('teacher.lectures.index'), route('teacher.lectures.edit', Lecture::where('batch_id', $batch->id)->first()),
            route('teacher.questions.index'), route('teacher.questions.create'), route('teacher.questions.edit', Question::where('created_by', $teacher->id)->first()),
            route('teacher.exams.show', Exam::where('batch_id', $batch->id)->where('mode', 'online')->first()),
            route('teacher.students.index'), route('teacher.students.index', ['q' => 'م']), route('teacher.students.show', $student),
            route('teacher.certificates'), route('teacher.reports'), route('teacher.payslips'),
            route('teacher.payslips.show', PayrollRecord::where('user_id', $teacher->id)->where('status', 'paid')->first()),
            route('teacher.assistant'), route('library.index'), route('downloads.portal'),
            route('messages.index'), route('messages.create'), route('messages.show', $teacher->messageThreads()->first()), route('notifications.index'),
            route('support.index'), route('support.create'), route('support.show', $ticket),
        ] as $url) {
            $this->actingAs($teacher)->get($url)->assertOk();
        }
    }

    public function test_every_new_student_and_parent_page_renders(): void
    {
        $user = User::where('email', 'student@talent.test')->first();
        $batchIds = $user->studentRecord->batches()->pluck('batches.id');
        $past = Exam::where('mode', 'online')->whereIn('batch_id', $batchIds)->where('exam_date', '<', today())->first();
        $open = Exam::where('mode', 'online')->whereIn('batch_id', $batchIds)->where('exam_date', today())->first();

        foreach ([
            route('student.live'), route('student.lectures'), route('student.lectures.show', Lecture::whereIn('batch_id', $batchIds)->first()),
            route('student.quizzes.index'), route('student.quizzes.show', $past), route('student.quizzes.show', $open),
            route('library.index'), route('library.index', ['type' => 'book', 'pathway' => 'academic']), route('downloads.portal'),
            route('messages.index'), route('messages.create'), route('messages.show', $user->messageThreads()->first()), route('notifications.index'),
            route('support.index'), route('support.create'), route('support.show', SupportTicket::where('user_id', $user->id)->first()),
        ] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }

        $this->actingAs($user)->get(route('student.quizzes.show', $past))->assertSee(__('Review your answers'));

        $parent = User::where('email', 'parent@talent.test')->first();
        foreach ([
            route('messages.index'), route('messages.create'), route('messages.show', $parent->messageThreads()->first()), route('notifications.index'),
            route('support.index'), route('support.show', SupportTicket::where('user_id', $parent->id)->first()), route('downloads.portal'),
        ] as $url) {
            $this->actingAs($parent)->get($url)->assertOk();
        }

        $this->actingAs($parent)->get(route('parent.children.show', $parent->children->first()))->assertOk()->assertSee(__('Notes from teachers'));
    }

    public function test_public_downloads_page_and_file(): void
    {
        $public = Download::where('audience', 'public')->first();

        $this->get(route('downloads.public'))->assertOk()->assertSee($public->title_ar);
        $this->get(route('downloads.open', $public))->assertOk()->assertHeader('content-disposition');
    }

    public function test_student_can_print_own_certificate(): void
    {
        $certificate = Certificate::whereHas('student', fn ($q) => $q->whereNotNull('user_id'))->first();

        if (! $certificate) {
            $this->markTestSkipped('Seed produced no certificate for a student with a login.');
        }

        $this->actingAs($certificate->student->user)
            ->get(route('student.certificates.show', $certificate))
            ->assertOk()
            ->assertSee($certificate->serial);
    }
}
