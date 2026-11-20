<?php

namespace App\Http\Controllers\TeacherPortal;

use App\Models\Batch;
use App\Models\Student;
use App\Models\StudentNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** The students in a teacher's classes, with progress figures and notes on their file. */
class StudentController extends TeacherController
{
    public function index(Request $request): View
    {
        $batches = $this->myBatches()->whereIn('status', ['active', 'upcoming', 'completed'])->get();

        $students = Student::whereHas('enrollments', fn ($q) => $q->whereIn('batch_id', $batches->pluck('id'))->where('status', 'active'))
            ->with(['batches' => fn ($q) => $q->whereIn('batches.id', $batches->pluck('id'))])
            ->withCount(['fileNotes as notes_count' => fn ($q) => $q->where('author_id', auth()->id())])
            ->search($request->query('q'))
            ->when($request->query('batch'), fn ($q, $v) => $q->whereHas('enrollments', fn ($q) => $q->where('batch_id', $v)))
            ->orderBy('full_name')
            ->paginate(25)
            ->withQueryString();

        return view('teacher.students.index', [
            'students' => $students,
            'batches' => $batches,
            'stats' => $students->getCollection()->mapWithKeys(fn (Student $s) => [$s->id => [
                'attendance' => $s->attendanceRate(),
                'average' => $s->averageExamPercent(),
            ]]),
        ]);
    }

    public function show(Student $student): View
    {
        $batches = $this->authorizeStudent($student);

        return view('teacher.students.show', [
            'student' => $student->load('guardians'),
            'rows' => $batches->map(fn (Batch $batch) => [
                'batch' => $batch,
                'attendance' => $student->attendanceRate($batch->id),
                'average' => $student->averageExamPercent($batch->id),
                'submitted' => $student->submissions()->whereIn('assignment_id', $batch->assignments()->pluck('id'))->count(),
                'assignments' => $batch->assignments()->count(),
            ]),
            'notes' => $student->fileNotes()->with(['author', 'batch'])->where(fn ($q) => $q
                ->where('author_id', auth()->id())
                ->orWhereIn('batch_id', $batches->pluck('id')))
                ->latest()->get(),
            'batches' => $batches,
        ]);
    }

    public function storeNote(Request $request, Student $student): RedirectResponse
    {
        $batches = $this->authorizeStudent($student);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'batch_id' => ['nullable', Rule::in($batches->pluck('id')->all())],
        ]);

        $student->fileNotes()->create($data + ['author_id' => auth()->id(), 'visible_to_parent' => $request->boolean('visible_to_parent')]);

        return back()->with('success', __('Note saved to the student file.'));
    }

    public function destroyNote(StudentNote $note): RedirectResponse
    {
        abort_unless((int) $note->author_id === (int) auth()->id(), 403);
        $note->delete();

        return back()->with('success', __('Note deleted.'));
    }

    /** The teacher's classes this student is enrolled in; 403 when there are none. */
    private function authorizeStudent(Student $student)
    {
        $batches = $this->myBatches()->with('course')
            ->whereHas('enrollments', fn ($q) => $q->where('student_id', $student->id))
            ->get();

        abort_if($batches->isEmpty(), 403, __('This student is not in any of your classes.'));

        return $batches;
    }
}
