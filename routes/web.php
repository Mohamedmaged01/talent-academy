<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ParentPortal;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Site;
use App\Http\Controllers\StudentPortal;
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
Route::get('/verify', [Site\CertificateVerificationController::class, 'show'])->name('certificates.verify');
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
| Admin portal
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::resource('branches', Admin\BranchController::class)->except('show');
    Route::resource('students', Admin\StudentController::class);
    Route::resource('teachers', Admin\TeacherController::class);
    Route::resource('guardians', Admin\GuardianController::class)->except('show');
    Route::resource('courses', Admin\CourseController::class)->except('show');

    Route::resource('batches', Admin\BatchController::class);
    Route::post('batches/{batch}/enroll', [Admin\BatchController::class, 'enroll'])->name('batches.enroll');
    Route::delete('batches/{batch}/students/{student}', [Admin\BatchController::class, 'unenroll'])->name('batches.unenroll');

    Route::get('timetable', Admin\TimetableController::class)->name('timetable');
    Route::get('attendance', Admin\AttendanceReportController::class)->name('attendance');

    Route::get('admissions', [Admin\AdmissionController::class, 'index'])->name('admissions.index');
    Route::get('admissions/{admission}', [Admin\AdmissionController::class, 'show'])->name('admissions.show');
    Route::patch('admissions/{admission}', [Admin\AdmissionController::class, 'update'])->name('admissions.update');
    Route::post('admissions/{admission}/convert', [Admin\AdmissionController::class, 'convert'])->name('admissions.convert');

    Route::get('inquiries', [Admin\InquiryController::class, 'index'])->name('inquiries.index');
    Route::patch('inquiries/{inquiry}', [Admin\InquiryController::class, 'update'])->name('inquiries.update');
    Route::delete('inquiries/{inquiry}', [Admin\InquiryController::class, 'destroy'])->name('inquiries.destroy');

    Route::get('finance', Admin\FinanceController::class)->name('finance');
    Route::resource('invoices', Admin\InvoiceController::class)->except(['edit', 'update', 'destroy']);
    Route::post('invoices/{invoice}/cancel', [Admin\InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::post('invoices/{invoice}/payments', [Admin\PaymentController::class, 'store'])->name('payments.store');
    Route::get('payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/{payment}/receipt', [Admin\PaymentController::class, 'receipt'])->name('payments.receipt');
    Route::get('expenses', [Admin\ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('expenses', [Admin\ExpenseController::class, 'store'])->name('expenses.store');
    Route::delete('expenses/{expense}', [Admin\ExpenseController::class, 'destroy'])->name('expenses.destroy');

    Route::get('certificates', [Admin\CertificateController::class, 'index'])->name('certificates.index');
    Route::get('certificates/issue', [Admin\CertificateController::class, 'create'])->name('certificates.create');
    Route::post('certificates', [Admin\CertificateController::class, 'store'])->name('certificates.store');
    Route::post('certificates/batch/{batch}', [Admin\CertificateController::class, 'issueBatch'])->name('certificates.issue-batch');
    Route::get('certificates/{certificate}', [Admin\CertificateController::class, 'show'])->name('certificates.show');
    Route::post('certificates/{certificate}/revoke', [Admin\CertificateController::class, 'revoke'])->name('certificates.revoke');

    Route::resource('news', Admin\NewsController::class)->except('show')->parameters(['news' => 'article']);
    Route::get('announcements', [Admin\AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('announcements', [Admin\AnnouncementController::class, 'store'])->name('announcements.store');
    Route::delete('announcements/{announcement}', [Admin\AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    Route::get('users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::post('users', [Admin\UserController::class, 'store'])->name('users.store');
    Route::patch('users/{user}/toggle', [Admin\UserController::class, 'toggle'])->name('users.toggle');
    Route::patch('users/{user}/password', [Admin\UserController::class, 'resetPassword'])->name('users.password');

    Route::get('settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    Route::get('activity', Admin\ActivityLogController::class)->name('activity');
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
    Route::delete('exams/{exam}', [TeacherPortal\ExamController::class, 'destroy'])->name('exams.destroy');

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
    Route::get('courses', [StudentPortal\PortalController::class, 'courses'])->name('courses');
    Route::get('courses/{batch}', [StudentPortal\PortalController::class, 'course'])->name('courses.show');
    Route::get('assignments', [StudentPortal\AssignmentController::class, 'index'])->name('assignments.index');
    Route::get('assignments/{assignment}', [StudentPortal\AssignmentController::class, 'show'])->name('assignments.show');
    Route::post('assignments/{assignment}', [StudentPortal\AssignmentController::class, 'submit'])->name('assignments.submit');
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
