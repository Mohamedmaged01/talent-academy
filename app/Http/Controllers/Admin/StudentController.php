<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Batch;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function index(Request $request): View|StreamedResponse
    {
        $query = Student::with('branch')->forAdminBranch()
            ->search($request->query('q'))
            ->when($request->query('pathway'), fn ($q, $v) => $q->where('pathway', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('branch'), fn ($q, $v) => $q->where('branch_id', $v))
            ->latest();

        if ($request->query('export') === 'csv') {
            return $this->exportCsv($query->get());
        }

        return view('admin.students.index', [
            'students' => $query->paginate(15)->withQueryString(),
            'branches' => Branch::orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.students.form', [
            'student' => new Student(['status' => 'active', 'pathway' => 'academic', 'gender' => 'male', 'enrolled_at' => now()]),
            'branches' => Branch::active()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $student = DB::transaction(function () use ($data, $request) {
            $student = Student::create($data + ['code' => Student::nextCode()]);
            $this->syncLogin($student, $request);

            return $student;
        });

        ActivityLog::record('student.created', "Student {$student->code} ({$student->full_name}) registered", $student);

        return redirect()->route('admin.students.show', $student)->with('success', __('Student saved.'));
    }

    public function show(Student $student): View
    {
        $student->load([
            'branch', 'user', 'guardians',
            'batches.course', 'batches.teacher',
            'invoices' => fn ($q) => $q->latest('issued_at'),
            'payments' => fn ($q) => $q->latest('paid_at'),
            'certificates.course',
            'examResults' => fn ($q) => $q->with('exam.batch')->latest(),
            'attendance' => fn ($q) => $q->with('batch')->latest('date')->take(20),
        ]);

        return view('admin.students.show', [
            'student' => $student,
            'attendanceRate' => $student->attendanceRate(),
            'average' => $student->averageExamPercent(),
            'balance' => $student->outstandingBalance(),
            'availableBatches' => Batch::with('course')->whereIn('status', ['upcoming', 'active'])
                ->whereNotIn('id', $student->batches->pluck('id'))->get(),
        ]);
    }

    public function edit(Student $student): View
    {
        return view('admin.students.form', [
            'student' => $student->load('user'),
            'branches' => Branch::where('status', '!=', 'archived')->get(),
        ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $data = $this->validated($request, $student);

        DB::transaction(function () use ($student, $data, $request) {
            $student->update($data);
            $this->syncLogin($student, $request);
        });

        ActivityLog::record('student.updated', "Student {$student->code} updated", $student);

        return redirect()->route('admin.students.show', $student)->with('success', __('Student saved.'));
    }

    public function destroy(Student $student): RedirectResponse
    {
        if ($student->invoices()->exists()) {
            $student->update(['status' => 'withdrawn']);

            return back()->with('warning', __('This student has financial records, so they were marked as withdrawn instead of deleted.'));
        }

        DB::transaction(function () use ($student) {
            $user = $student->user;
            $student->delete();
            $user?->delete();
        });

        ActivityLog::record('student.deleted', "Student {$student->code} deleted");

        return redirect()->route('admin.students.index')->with('success', __('Student deleted.'));
    }

    private function validated(Request $request, ?Student $student = null): array
    {
        return $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'full_name_en' => ['nullable', 'string', 'max:120'],
            'branch_id' => ['required', 'exists:branches,id'],
            'national_id' => ['nullable', 'digits:14'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:120'],
            'pathway' => ['required', Rule::in(Course::PATHWAYS)],
            'grade_level' => ['nullable', 'string', 'max:60'],
            'school_name' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(Student::STATUSES)],
            'enrolled_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'login_email' => ['nullable', 'email', 'max:120', Rule::unique('users', 'email')->ignore($student?->user_id)],
            'login_password' => ['nullable', 'string', 'min:8'],
        ]);
    }

    /** Create or update the student's portal account when login details are supplied. */
    private function syncLogin(Student $student, Request $request): void
    {
        $email = $request->input('login_email');
        $password = $request->input('login_password');

        if (! $email) {
            return;
        }

        if ($student->user) {
            $student->user->update(array_filter([
                'email' => $email,
                'name' => $student->full_name,
                'branch_id' => $student->branch_id,
                'password' => $password ?: null,
            ]));

            return;
        }

        $user = User::create([
            'name' => $student->full_name,
            'email' => $email,
            'phone' => $student->phone,
            'role' => 'student',
            'branch_id' => $student->branch_id,
            'password' => $password ?: str()->random(12),
        ]);

        $student->update(['user_id' => $user->id]);
    }

    private function exportCsv($students): StreamedResponse
    {
        $filename = 'students-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($students) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel renders Arabic correctly
            fputcsv($out, ['Code', 'Name', 'Name (EN)', 'Pathway', 'Grade', 'Branch', 'Phone', 'Status', 'Enrolled']);
            foreach ($students as $s) {
                fputcsv($out, array_map([self::class, 'csvSafe'], [
                    $s->code, $s->full_name, $s->full_name_en, $s->pathway, $s->grade_level,
                    $s->branch?->name_en, $s->phone, $s->status, $s->enrolled_at?->toDateString(),
                ]));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Neutralise spreadsheet formula injection. */
    public static function csvSafe(?string $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
