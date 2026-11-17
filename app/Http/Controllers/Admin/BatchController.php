<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Batch;
use App\Models\BatchSchedule;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BatchController extends Controller
{
    public function index(Request $request): View
    {
        $batches = Batch::with(['course', 'teacher', 'branch', 'schedules'])
            ->withCount(['enrollments' => fn ($q) => $q->where('status', 'active')])
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('branch'), fn ($q, $v) => $q->where('branch_id', $v))
            ->when($request->query('pathway'), fn ($q, $v) => $q->whereHas('course', fn ($q) => $q->where('pathway', $v)))
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%")))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.batches.index', [
            'batches' => $batches,
            'branches' => Branch::orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.batches.form', $this->formData(new Batch(['status' => 'upcoming', 'capacity' => 25, 'start_date' => now()->addWeek()])));
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $schedules] = $this->validated($request);

        $batch = DB::transaction(function () use ($data, $schedules) {
            $batch = Batch::create($data);
            $batch->schedules()->createMany($schedules);

            return $batch;
        });

        ActivityLog::record('batch.created', "Class {$batch->code} created", $batch);

        return redirect()->route('admin.batches.show', $batch)->with('success', __('Class saved.'));
    }

    public function show(Batch $batch, CertificateService $certificates): View
    {
        $batch->load(['course', 'teacher', 'branch', 'schedules', 'exams' => fn ($q) => $q->withCount('results')->latest('exam_date'), 'assignments' => fn ($q) => $q->withCount('submissions')->latest('due_at')]);

        $students = $batch->students()->orderBy('full_name')->get();

        return view('admin.batches.show', [
            'batch' => $batch,
            'students' => $students,
            'eligibility' => $certificates->eligibility($batch),
            'candidates' => Student::where('status', 'active')
                ->whereNotIn('id', $students->pluck('id'))
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'code', 'pathway']),
        ]);
    }

    public function edit(Batch $batch): View
    {
        return view('admin.batches.form', $this->formData($batch->load('schedules')));
    }

    public function update(Request $request, Batch $batch): RedirectResponse
    {
        [$data, $schedules] = $this->validated($request, $batch);

        DB::transaction(function () use ($batch, $data, $schedules) {
            $batch->update($data);
            $batch->schedules()->delete();
            $batch->schedules()->createMany($schedules);
        });

        ActivityLog::record('batch.updated', "Class {$batch->code} updated", $batch);

        return redirect()->route('admin.batches.show', $batch)->with('success', __('Class saved.'));
    }

    public function destroy(Batch $batch): RedirectResponse
    {
        if ($batch->enrollments()->exists() || $batch->attendance()->exists()) {
            $batch->update(['status' => 'cancelled']);

            return back()->with('warning', __('This class has enrolled students, so it was cancelled instead of deleted.'));
        }

        $batch->delete();
        ActivityLog::record('batch.deleted', "Class {$batch->code} deleted");

        return redirect()->route('admin.batches.index')->with('success', __('Class deleted.'));
    }

    public function enroll(Request $request, Batch $batch): RedirectResponse
    {
        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['exists:students,id'],
        ]);

        $current = $batch->enrollments()->where('status', 'active')->count();
        $incoming = collect($data['student_ids'])->unique();

        if ($current + $incoming->count() > $batch->capacity) {
            return back()->with('error', __('Not enough seats: :free seat(s) left in this class.', ['free' => max(0, $batch->capacity - $current)]));
        }

        foreach ($incoming as $studentId) {
            $batch->enrollments()->updateOrCreate(
                ['student_id' => $studentId],
                ['status' => 'active', 'enrolled_at' => now()],
            );
        }

        ActivityLog::record('batch.enrolled', "{$incoming->count()} student(s) enrolled in {$batch->code}", $batch);

        return back()->with('success', __(':count student(s) enrolled.', ['count' => $incoming->count()]));
    }

    public function unenroll(Batch $batch, Student $student): RedirectResponse
    {
        $batch->enrollments()->where('student_id', $student->id)->update(['status' => 'dropped']);
        ActivityLog::record('batch.unenrolled', "{$student->full_name} removed from {$batch->code}", $batch);

        return back()->with('success', __('Student removed from class.'));
    }

    private function formData(Batch $batch): array
    {
        return [
            'batch' => $batch,
            'courses' => Course::orderBy('pathway')->orderBy('sort_order')->get(),
            'teachers' => User::where('role', 'teacher')->where('is_active', true)->orderBy('name')->get(),
            'branches' => Branch::where('status', '!=', 'archived')->get(),
            'days' => BatchSchedule::DAYS,
        ];
    }

    private function validated(Request $request, ?Batch $batch = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:30', Rule::unique('batches')->ignore($batch?->id)],
            'course_id' => ['required', 'exists:courses,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'teacher_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'teacher')],
            'room' => ['nullable', 'string', 'max:120'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(Batch::STATUSES)],
            'schedules' => ['array'],
            'schedules.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'schedules.*.starts_at' => ['required', 'date_format:H:i'],
            'schedules.*.ends_at' => ['required', 'date_format:H:i', 'after:schedules.*.starts_at'],
        ]);

        $schedules = array_values($data['schedules'] ?? []);
        unset($data['schedules']);

        return [$data, $schedules];
    }
}
