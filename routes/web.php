<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ParentPortal;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\Site;
use App\Http\Controllers\StudentPortal;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\TeacherPortal;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/
Route::get('/', [Site\HomeController::class, 'index'])->name('home');
Route::get('/about', [Site\HomeController::class, 'about'])->name('about');
Route::get('/privacy', [Site\HomeController::class, 'privacy'])->name('privacy');
Route::get('/terms', [Site\HomeController::class, 'terms'])->name('terms');
Route::get('/programs', [Site\CourseController::class, 'index'])->name('courses.index');
Route::get('/programs/{course:code}', [Site\CourseController::class, 'show'])->name('courses.show');
Route::get('/news', [Site\NewsController::class, 'index'])->name('news.index');
Route::get('/news/{article}', [Site\NewsController::class, 'show'])->name('news.show');
Route::get('/apply', [Site\AdmissionController::class, 'create'])->name('admissions.create');
Route::post('/apply', [Site\AdmissionController::class, 'store'])->middleware('throttle:10,1')->name('admissions.store');
Route::get('/apply/thank-you/{reference}', [Site\AdmissionController::class, 'thanks'])->name('admissions.thanks');
Route::post('/contact', [Site\ContactController::class, 'store'])->middleware('throttle:10,1')->name('contact.store');
Route::get('/verify', [Site\CertificateVerificationController::class, 'show'])->middleware('throttle:30,1')->name('certificates.verify');
Route::get('/downloads', [ResourceController::class, 'publicDownloads'])->name('downloads.public');
Route::get('/downloads/{download}/file', [ResourceController::class, 'openDownload'])->middleware('throttle:60,1')->name('downloads.open');
Route::get('/locale/{locale}', LocaleController::class)->name('locale.switch');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login')->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', fn () => redirect(auth()->user()->homeRoute()))->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
});

/*
|--------------------------------------------------------------------------
| Shared by every portal: messages, notifications, support, library, downloads
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin,teacher,student,parent'])->group(function () {
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/new', [MessageController::class, 'create'])->name('messages.create');
    Route::post('/messages', [MessageController::class, 'store'])->middleware('throttle:30,1')->name('messages.store');
    Route::get('/messages/{thread}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{thread}/reply', [MessageController::class, 'reply'])->middleware('throttle:60,1')->name('messages.reply');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    Route::get('/resources', [ResourceController::class, 'downloads'])->name('downloads.portal');
});

Route::middleware(['auth', 'role:admin,teacher,student'])->group(function () {
    Route::get('/library/{item}/open', [ResourceController::class, 'openLibraryItem'])->name('library.open');
});

Route::middleware(['auth', 'role:teacher,student'])->get('/library', [ResourceController::class, 'library'])->name('library.index');

Route::prefix('support')->name('support.')->middleware(['auth', 'role:teacher,student,parent'])->group(function () {
    Route::get('/', [SupportController::class, 'index'])->name('index');
    Route::get('/new', [SupportController::class, 'create'])->name('create');
    Route::post('/', [SupportController::class, 'store'])->middleware('throttle:10,1')->name('store');
    Route::get('/{ticket}', [SupportController::class, 'show'])->name('show');
    Route::post('/{ticket}/reply', [SupportController::class, 'reply'])->middleware('throttle:30,1')->name('reply');
    Route::post('/{ticket}/close', [SupportController::class, 'close'])->name('close');
});

/*
|--------------------------------------------------------------------------
| Admin portal — each section requires its permission (see App\Support\Permissions)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin', 'branch.scope'])->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::middleware('admin.can:branches')->group(function () {
        Route::resource('branches', Admin\BranchController::class)->except('show');
    });

    Route::middleware('admin.can:students')->group(function () {
        Route::resource('students', Admin\StudentController::class);
        Route::post('students/{student}/notes', [Admin\StudentNoteController::class, 'store'])->name('students.notes.store');
        Route::delete('student-notes/{note}', [Admin\StudentNoteController::class, 'destroy'])->name('students.notes.destroy');
    });

    Route::middleware('admin.can:teachers')->group(function () {
        Route::resource('teachers', Admin\TeacherController::class);
    });

    Route::middleware('admin.can:parents')->group(function () {
        Route::resource('guardians', Admin\GuardianController::class)->except('show');
    });

    Route::middleware('admin.can:academics')->group(function () {
        Route::resource('courses', Admin\CourseController::class)->except('show');
        Route::resource('batches', Admin\BatchController::class);
        Route::post('batches/{batch}/enroll', [Admin\BatchController::class, 'enroll'])->name('batches.enroll');
        Route::delete('batches/{batch}/students/{student}', [Admin\BatchController::class, 'unenroll'])->name('batches.unenroll');
        Route::get('timetable', Admin\TimetableController::class)->name('timetable');
        Route::get('attendance', Admin\AttendanceReportController::class)->name('attendance');
    });

    Route::middleware('admin.can:exams')->group(function () {
        Route::get('exams', [Admin\ExamController::class, 'index'])->name('exams.index');
        Route::get('exams/{exam}', [Admin\ExamController::class, 'show'])->name('exams.show');
        Route::post('exams/{exam}/publish', [Admin\ExamController::class, 'publish'])->name('exams.publish');
        Route::get('lectures', [Admin\ExamController::class, 'lectures'])->name('lectures.index');
        Route::delete('lectures/{lecture}', [Admin\ExamController::class, 'destroyLecture'])->name('lectures.destroy');
        Route::post('questions/import', [Admin\QuestionController::class, 'import'])->name('questions.import');
        Route::resource('questions', Admin\QuestionController::class)->except('show');
    });

    Route::middleware('admin.can:library')->group(function () {
        Route::resource('library', Admin\LibraryController::class)->except('show')->parameters(['library' => 'item']);
        Route::resource('downloads', Admin\DownloadController::class)->except('show');
    });

    Route::middleware('admin.can:admissions')->group(function () {
        Route::get('admissions', [Admin\AdmissionController::class, 'index'])->name('admissions.index');
        Route::get('admissions/{admission}', [Admin\AdmissionController::class, 'show'])->name('admissions.show');
        Route::patch('admissions/{admission}', [Admin\AdmissionController::class, 'update'])->name('admissions.update');
        Route::post('admissions/{admission}/convert', [Admin\AdmissionController::class, 'convert'])->name('admissions.convert');
    });

    Route::middleware('admin.can:communication')->group(function () {
        Route::get('inquiries', [Admin\InquiryController::class, 'index'])->name('inquiries.index');
        Route::patch('inquiries/{inquiry}', [Admin\InquiryController::class, 'update'])->name('inquiries.update');
        Route::delete('inquiries/{inquiry}', [Admin\InquiryController::class, 'destroy'])->name('inquiries.destroy');
        Route::get('announcements', [Admin\AnnouncementController::class, 'index'])->name('announcements.index');
        Route::post('announcements', [Admin\AnnouncementController::class, 'store'])->name('announcements.store');
        Route::delete('announcements/{announcement}', [Admin\AnnouncementController::class, 'destroy'])->name('announcements.destroy');
        Route::get('broadcast', [Admin\BroadcastController::class, 'create'])->name('broadcast.create');
        Route::post('broadcast', [Admin\BroadcastController::class, 'store'])->name('broadcast.store');
        Route::delete('message-templates/{template}', [Admin\BroadcastController::class, 'destroyTemplate'])->name('templates.destroy');
    });

    Route::middleware('admin.can:support')->group(function () {
        Route::get('support', [Admin\SupportController::class, 'index'])->name('support.index');
        Route::get('support/{ticket}', [Admin\SupportController::class, 'show'])->name('support.show');
        Route::post('support/{ticket}/reply', [Admin\SupportController::class, 'reply'])->name('support.reply');
        Route::patch('support/{ticket}', [Admin\SupportController::class, 'status'])->name('support.status');
    });

    Route::middleware('admin.can:finance')->group(function () {
        Route::get('finance', Admin\FinanceController::class)->name('finance');
        Route::resource('invoices', Admin\InvoiceController::class)->except(['edit', 'update', 'destroy']);
        Route::post('invoices/{invoice}/cancel', [Admin\InvoiceController::class, 'cancel'])->name('invoices.cancel');
        Route::post('invoices/{invoice}/payments', [Admin\PaymentController::class, 'store'])->name('payments.store');
        Route::get('payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/{payment}/receipt', [Admin\PaymentController::class, 'receipt'])->name('payments.receipt');
        Route::get('expenses', [Admin\ExpenseController::class, 'index'])->name('expenses.index');
        Route::post('expenses', [Admin\ExpenseController::class, 'store'])->name('expenses.store');
        Route::delete('expenses/{expense}', [Admin\ExpenseController::class, 'destroy'])->name('expenses.destroy');
    });

    Route::middleware('admin.can:payroll')->prefix('payroll')->name('payroll.')->group(function () {
        Route::get('/', [Admin\PayrollController::class, 'index'])->name('index');
        Route::post('generate', [Admin\PayrollController::class, 'generate'])->name('generate');
        Route::post('pay-all', [Admin\PayrollController::class, 'payAll'])->name('pay-all');
        Route::put('{record}', [Admin\PayrollController::class, 'update'])->name('update');
        Route::post('{record}/pay', [Admin\PayrollController::class, 'pay'])->name('pay');
        Route::get('{record}/payslip', [Admin\PayrollController::class, 'payslip'])->name('payslip');
        Route::delete('{record}', [Admin\PayrollController::class, 'destroy'])->name('destroy');
    });

    Route::middleware('admin.can:ledger')->prefix('ledger')->name('ledger.')->group(function () {
        Route::get('/', [Admin\LedgerController::class, 'index'])->name('index');
        Route::get('new', [Admin\LedgerController::class, 'create'])->name('create');
        Route::post('/', [Admin\LedgerController::class, 'store'])->name('store');
        Route::post('entries/{entry}/reverse', [Admin\LedgerController::class, 'reverse'])->name('reverse');
        Route::get('trial-balance', [Admin\LedgerController::class, 'trialBalance'])->name('trial-balance');
        Route::post('rebuild', [Admin\LedgerController::class, 'rebuild'])->name('rebuild');
        Route::get('accounts', [Admin\LedgerController::class, 'accounts'])->name('accounts');
        Route::post('accounts', [Admin\LedgerController::class, 'storeAccount'])->name('accounts.store');
        Route::patch('accounts/{account}/toggle', [Admin\LedgerController::class, 'toggleAccount'])->name('accounts.toggle');
        Route::get('accounts/{account}', [Admin\LedgerController::class, 'account'])->name('accounts.show');
    });

    Route::middleware('admin.can:certificates')->group(function () {
        Route::get('certificates', [Admin\CertificateController::class, 'index'])->name('certificates.index');
        Route::get('certificates/issue', [Admin\CertificateController::class, 'create'])->name('certificates.create');
        Route::get('certificates/nominations', [Admin\NominationController::class, 'index'])->name('nominations.index');
        Route::post('certificates/nominations/{nomination}/approve', [Admin\NominationController::class, 'approve'])->name('nominations.approve');
        Route::post('certificates/nominations/{nomination}/reject', [Admin\NominationController::class, 'reject'])->name('nominations.reject');
        Route::post('certificates', [Admin\CertificateController::class, 'store'])->name('certificates.store');
        Route::post('certificates/batch/{batch}', [Admin\CertificateController::class, 'issueBatch'])->name('certificates.issue-batch');
        Route::get('certificates/{certificate}', [Admin\CertificateController::class, 'show'])->name('certificates.show');
        Route::post('certificates/{certificate}/revoke', [Admin\CertificateController::class, 'revoke'])->name('certificates.revoke');
    });

    Route::middleware('admin.can:content')->group(function () {
        Route::resource('news', Admin\NewsController::class)->except('show')->parameters(['news' => 'article']);
        Route::get('site-content', [Admin\SiteContentController::class, 'edit'])->name('content.edit');
        Route::put('site-content', [Admin\SiteContentController::class, 'update'])->name('content.update');
        Route::get('media', [Admin\SiteContentController::class, 'media'])->name('media.index');
        Route::post('media', [Admin\SiteContentController::class, 'upload'])->name('media.store');
        Route::patch('media/{item}', [Admin\SiteContentController::class, 'updateMedia'])->name('media.update');
        Route::delete('media/{item}', [Admin\SiteContentController::class, 'destroyMedia'])->name('media.destroy');
    });

    Route::middleware('admin.can:reports')->get('reports', Admin\ReportController::class)->name('reports');

    Route::middleware('admin.can:users')->group(function () {
        Route::get('users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::post('users', [Admin\UserController::class, 'store'])->name('users.store');
        Route::patch('users/{user}/toggle', [Admin\UserController::class, 'toggle'])->name('users.toggle');
        Route::patch('users/{user}/password', [Admin\UserController::class, 'resetPassword'])->name('users.password');
        Route::resource('roles', Admin\RoleController::class)->except('show');
        Route::patch('roles/assign/{user}', [Admin\RoleController::class, 'assign'])->name('roles.assign');
    });

    // Every administrator can see their own delegations and ask for access; granting is checked in the controller.
    Route::get('delegations', [Admin\DelegationController::class, 'index'])->name('delegations.index');
    Route::post('delegations', [Admin\DelegationController::class, 'store'])->name('delegations.store');
    Route::post('delegations/request', [Admin\DelegationController::class, 'request'])->middleware('throttle:10,1')->name('delegations.request');
    Route::post('delegations/{delegation}/revoke', [Admin\DelegationController::class, 'revoke'])->name('delegations.revoke');
    Route::post('delegations/{delegation}/approve', [Admin\DelegationController::class, 'approve'])->name('delegations.approve');
    Route::post('delegations/{delegation}/reject', [Admin\DelegationController::class, 'reject'])->name('delegations.reject');

    Route::middleware('admin.can:settings')->group(function () {
        Route::get('settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
        Route::get('integrations', [Admin\IntegrationController::class, 'index'])->name('integrations.index');
        Route::post('integrations', [Admin\IntegrationController::class, 'store'])->name('integrations.store');
        Route::put('integrations/{webhook}', [Admin\IntegrationController::class, 'update'])->name('integrations.update');
        Route::post('integrations/{webhook}/test', [Admin\IntegrationController::class, 'test'])->middleware('throttle:10,1')->name('integrations.test');
        Route::post('integrations/{webhook}/rotate', [Admin\IntegrationController::class, 'rotate'])->name('integrations.rotate');
        Route::delete('integrations/{webhook}', [Admin\IntegrationController::class, 'destroy'])->name('integrations.destroy');
    });

    Route::middleware('admin.can:backups')->prefix('backups')->name('backups.')->group(function () {
        Route::get('/', [Admin\BackupController::class, 'index'])->name('index');
        Route::post('/', [Admin\BackupController::class, 'store'])->name('store');
        Route::post('upload', [Admin\BackupController::class, 'upload'])->name('upload');
        Route::get('{backup}/download', [Admin\BackupController::class, 'download'])->name('download');
        Route::post('{backup}/verify', [Admin\BackupController::class, 'verify'])->name('verify');
        Route::post('{backup}/restore', [Admin\BackupController::class, 'restore'])->name('restore');
        Route::delete('{backup}', [Admin\BackupController::class, 'destroy'])->name('destroy');
    });

    Route::middleware('admin.can:security')->group(function () {
        Route::get('activity', Admin\ActivityLogController::class)->name('activity');
        Route::get('security', Admin\SecurityController::class)->name('security');
    });

    Route::middleware('admin.can:assistant')->group(function () {
        Route::get('assistant', [AssistantController::class, 'index'])->name('assistant');
        Route::post('assistant', [AssistantController::class, 'ask'])->middleware('throttle:assistant')->name('assistant.ask');
        Route::delete('assistant', [AssistantController::class, 'clear'])->name('assistant.clear');
    });
});

/*
|--------------------------------------------------------------------------
| Teacher / trainer portal
|--------------------------------------------------------------------------
*/
Route::prefix('teacher')->name('teacher.')->middleware(['auth', 'role:teacher'])->group(function () {
    Route::get('/', TeacherPortal\DashboardController::class)->name('dashboard');
    Route::get('schedule', TeacherPortal\ScheduleController::class)->name('schedule');
    Route::get('batches', [TeacherPortal\BatchController::class, 'index'])->name('batches.index');
    Route::get('batches/{batch}', [TeacherPortal\BatchController::class, 'show'])->name('batches.show');
    Route::patch('batches/{batch}/meeting', [TeacherPortal\BatchController::class, 'meeting'])->name('batches.meeting');

    Route::get('batches/{batch}/attendance', [TeacherPortal\AttendanceController::class, 'edit'])->name('attendance.edit');
    Route::put('batches/{batch}/attendance', [TeacherPortal\AttendanceController::class, 'update'])->name('attendance.update');

    Route::post('batches/{batch}/materials', [TeacherPortal\MaterialController::class, 'store'])->name('materials.store');
    Route::delete('materials/{material}', [TeacherPortal\MaterialController::class, 'destroy'])->name('materials.destroy');
    Route::post('batches/{batch}/announcements', [TeacherPortal\BatchController::class, 'announce'])->name('batches.announce');

    Route::get('assignments', [TeacherPortal\AssignmentController::class, 'index'])->name('assignments.index');
    Route::get('assignments/create', [TeacherPortal\AssignmentController::class, 'create'])->name('assignments.create');
    Route::post('assignments', [TeacherPortal\AssignmentController::class, 'store'])->name('assignments.store');
    Route::get('assignments/{assignment}', [TeacherPortal\AssignmentController::class, 'show'])->name('assignments.show');
    Route::delete('assignments/{assignment}', [TeacherPortal\AssignmentController::class, 'destroy'])->name('assignments.destroy');
    Route::patch('submissions/{submission}', [TeacherPortal\AssignmentController::class, 'grade'])->name('submissions.grade');

    Route::get('exams', [TeacherPortal\ExamController::class, 'index'])->name('exams.index');
    Route::get('exams/create', [TeacherPortal\ExamController::class, 'create'])->name('exams.create');
    Route::post('exams', [TeacherPortal\ExamController::class, 'store'])->name('exams.store');
    Route::get('exams/{exam}', [TeacherPortal\ExamController::class, 'show'])->name('exams.show');
    Route::put('exams/{exam}/results', [TeacherPortal\ExamController::class, 'saveResults'])->name('exams.results');
    Route::post('exams/{exam}/questions', [TeacherPortal\ExamController::class, 'attachQuestions'])->name('exams.questions.attach');
    Route::delete('exams/{exam}/questions/{question}', [TeacherPortal\ExamController::class, 'detachQuestion'])->name('exams.questions.detach');
    Route::delete('exams/{exam}', [TeacherPortal\ExamController::class, 'destroy'])->name('exams.destroy');

    Route::post('questions/import', [TeacherPortal\QuestionController::class, 'import'])->name('questions.import');
    Route::resource('questions', TeacherPortal\QuestionController::class)->except('show');

    Route::get('lectures', [TeacherPortal\LectureController::class, 'index'])->name('lectures.index');
    Route::post('lectures', [TeacherPortal\LectureController::class, 'store'])->name('lectures.store');
    Route::get('lectures/{lecture}/edit', [TeacherPortal\LectureController::class, 'edit'])->name('lectures.edit');
    Route::put('lectures/{lecture}', [TeacherPortal\LectureController::class, 'update'])->name('lectures.update');
    Route::post('lectures/{lecture}/move/{direction}', [TeacherPortal\LectureController::class, 'move'])->whereIn('direction', ['up', 'down'])->name('lectures.move');
    Route::delete('lectures/{lecture}', [TeacherPortal\LectureController::class, 'destroy'])->name('lectures.destroy');

    Route::get('students', [TeacherPortal\StudentController::class, 'index'])->name('students.index');
    Route::get('students/{student}', [TeacherPortal\StudentController::class, 'show'])->name('students.show');
    Route::post('students/{student}/notes', [TeacherPortal\StudentController::class, 'storeNote'])->name('students.notes.store');
    Route::delete('notes/{note}', [TeacherPortal\StudentController::class, 'destroyNote'])->name('notes.destroy');

    Route::get('certificates', [TeacherPortal\CertificateController::class, 'index'])->name('certificates');
    Route::post('certificates/{batch}/nominate', [TeacherPortal\CertificateController::class, 'nominate'])->name('certificates.nominate');

    Route::get('reports', [TeacherPortal\ReportController::class, 'index'])->name('reports');
    Route::get('reports/{batch}/export', [TeacherPortal\ReportController::class, 'export'])->name('reports.export');

    Route::get('payslips', [TeacherPortal\PayslipController::class, 'index'])->name('payslips');
    Route::get('payslips/{record}', [TeacherPortal\PayslipController::class, 'show'])->name('payslips.show');

    Route::get('assistant', [AssistantController::class, 'index'])->name('assistant');
    Route::post('assistant', [AssistantController::class, 'ask'])->middleware('throttle:assistant')->name('assistant.ask');
    Route::delete('assistant', [AssistantController::class, 'clear'])->name('assistant.clear');

    Route::get('announcements', [TeacherPortal\DashboardController::class, 'announcements'])->name('announcements');
});

/*
|--------------------------------------------------------------------------
| Student / trainee portal
|--------------------------------------------------------------------------
*/
Route::prefix('student')->name('student.')->middleware(['auth', 'role:student'])->group(function () {
    Route::get('/', [StudentPortal\PortalController::class, 'dashboard'])->name('dashboard');
    Route::get('schedule', [StudentPortal\PortalController::class, 'schedule'])->name('schedule');
    Route::get('live', [StudentPortal\LearningController::class, 'live'])->name('live');
    Route::get('courses', [StudentPortal\PortalController::class, 'courses'])->name('courses');
    Route::get('courses/{batch}', [StudentPortal\PortalController::class, 'course'])->name('courses.show');
    Route::get('lectures', [StudentPortal\LearningController::class, 'lectures'])->name('lectures');
    Route::get('lectures/{lecture}', [StudentPortal\LearningController::class, 'lecture'])->name('lectures.show');
    Route::get('assignments', [StudentPortal\AssignmentController::class, 'index'])->name('assignments.index');
    Route::get('assignments/{assignment}', [StudentPortal\AssignmentController::class, 'show'])->name('assignments.show');
    Route::post('assignments/{assignment}', [StudentPortal\AssignmentController::class, 'submit'])->name('assignments.submit');
    Route::get('quizzes', [StudentPortal\QuizController::class, 'index'])->name('quizzes.index');
    Route::get('quizzes/{exam}', [StudentPortal\QuizController::class, 'show'])->name('quizzes.show');
    Route::post('quizzes/{exam}/start', [StudentPortal\QuizController::class, 'start'])->name('quizzes.start');
    Route::post('quizzes/{exam}/submit', [StudentPortal\QuizController::class, 'submit'])->name('quizzes.submit');
    Route::get('grades', [StudentPortal\PortalController::class, 'grades'])->name('grades');
    Route::get('attendance', [StudentPortal\PortalController::class, 'attendance'])->name('attendance');
    Route::get('payments', [StudentPortal\PortalController::class, 'payments'])->name('payments');
    Route::get('certificates', [StudentPortal\PortalController::class, 'certificates'])->name('certificates');
    Route::get('certificates/{certificate}', [StudentPortal\PortalController::class, 'certificate'])->name('certificates.show');
    Route::get('announcements', [StudentPortal\PortalController::class, 'announcements'])->name('announcements');
});

/*
|--------------------------------------------------------------------------
| Parent portal
|--------------------------------------------------------------------------
*/
Route::prefix('parent')->name('parent.')->middleware(['auth', 'role:parent'])->group(function () {
    Route::get('/', [ParentPortal\PortalController::class, 'dashboard'])->name('dashboard');
    Route::get('children/{student}', [ParentPortal\PortalController::class, 'child'])->name('children.show');
    Route::get('announcements', [ParentPortal\PortalController::class, 'announcements'])->name('announcements');
});
