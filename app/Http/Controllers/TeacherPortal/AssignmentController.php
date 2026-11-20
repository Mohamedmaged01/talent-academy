<?php

namespace App\Http\Controllers\TeacherPortal;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Batch;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignmentController extends TeacherController
{
    public function index(): View
    {
        return view('teacher.assignments.index', [
            'assignments' => Assignment::with('batch.course')
                ->withCount(['submissions', 'submissions as graded_count' => fn ($q) => $q->whereNotNull('graded_at')])
                ->whereIn('batch_id', $this->myBatches()->pluck('id'))
                ->latest('due_at')
                ->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        return view('teacher.assignments.create', [
            'batches' => $this->myBatches()->whereIn('status', ['active', 'upcoming'])->with('course')->get(),
            'selected' => $request->query('batch'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'batch_id' => ['required', 'exists:batches,id'],
            'title' => ['required', 'string', 'max:150'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'due_at' => ['required', 'date', 'after:now'],
            'max_score' => ['required', 'integer', 'min:1', 'max:1000'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,ppt,pptx,png,jpg,jpeg,zip'],
        ]);

        $this->authorizeBatch(Batch::findOrFail($data['batch_id']));

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('assignments', 'public');
        }

        unset($data['attachment']);
        $assignment = Assignment::create($data + ['teacher_id' => auth()->id()]);

        foreach ($assignment->batch->activeStudents()->with('user')->get() as $student) {
            Notify::student($student, 'New assignment ":title" due :date', ['title' => $assignment->title, 'date' => $assignment->due_at->format('Y-m-d H:i')],
                route('student.assignments.show', $assignment), 'notebook-pen');
        }

        return redirect()->route('teacher.assignments.show', $assignment)->with('success', __('Assignment published.'));
    }

    public function show(Assignment $assignment): View
    {
        $this->authorizeBatch($assignment->batch);

        $assignment->load('batch.course');
        $submissions = $assignment->submissions()->with('student')->get()->keyBy('student_id');

        return view('teacher.assignments.show', [
            'assignment' => $assignment,
            'students' => $assignment->batch->activeStudents()->orderBy('full_name')->get(),
            'submissions' => $submissions,
        ]);
    }

    public function destroy(Assignment $assignment): RedirectResponse
    {
        $this->authorizeBatch($assignment->batch);
        $assignment->delete();

        return redirect()->route('teacher.assignments.index')->with('success', __('Assignment deleted.'));
    }

    public function grade(Request $request, AssignmentSubmission $submission): RedirectResponse
    {
        $assignment = $submission->assignment;
        $this->authorizeBatch($assignment->batch);

        $data = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:'.$assignment->max_score],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $submission->update($data + ['graded_at' => now()]);

        Notify::student($submission->student, 'Your work on ":title" was graded: :score / :max', [
            'title' => $assignment->title, 'score' => (float) $data['score'], 'max' => $assignment->max_score,
        ], route('student.assignments.show', $assignment), 'circle-check');

        return back()->with('success', __('Grade saved.'));
    }
}
