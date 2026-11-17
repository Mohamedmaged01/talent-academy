<?php

namespace App\Http\Controllers\StudentPortal;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(): View
    {
        $student = PortalController::currentStudent();
        $batchIds = $student->batches()->pluck('batches.id');

        $assignments = Assignment::with('batch.course')->whereIn('batch_id', $batchIds)->latest('due_at')->get();
        $submissions = $student->submissions()->whereIn('assignment_id', $assignments->pluck('id'))->get()->keyBy('assignment_id');

        return view('student.assignments.index', compact('assignments', 'submissions'));
    }

    public function show(Assignment $assignment): View
    {
        $student = PortalController::currentStudent();
        $this->authorizeAssignment($assignment);

        return view('student.assignments.show', [
            'assignment' => $assignment->load('batch.course', 'teacher'),
            'submission' => $student->submissions()->where('assignment_id', $assignment->id)->first(),
        ]);
    }

    public function submit(Request $request, Assignment $assignment): RedirectResponse
    {
        $student = PortalController::currentStudent();
        $this->authorizeAssignment($assignment);

        $existing = $student->submissions()->where('assignment_id', $assignment->id)->first();

        if ($existing?->graded_at) {
            return back()->with('error', __('This assignment has already been graded and can no longer be changed.'));
        }

        $data = $request->validate([
            'body' => ['nullable', 'required_without:attachment', 'string', 'max:10000'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,ppt,pptx,png,jpg,jpeg,zip,txt'],
        ]);

        $path = $existing?->attachment_path;
        if ($request->hasFile('attachment')) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            $path = $request->file('attachment')->store("submissions/{$assignment->id}", 'public');
        }

        $student->submissions()->updateOrCreate(
            ['assignment_id' => $assignment->id],
            ['body' => $data['body'] ?? null, 'attachment_path' => $path, 'submitted_at' => now()],
        );

        return back()->with('success', __('Your work has been submitted.'));
    }

    private function authorizeAssignment(Assignment $assignment): void
    {
        $enrolled = PortalController::currentStudent()->batches()->whereKey($assignment->batch_id)->exists();
        abort_unless($enrolled, 403);
    }
}
