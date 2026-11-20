<?php

namespace App\Http\Controllers\TeacherPortal;

use App\Models\Announcement;
use App\Models\Batch;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BatchController extends TeacherController
{
    public function index(): View
    {
        return view('teacher.batches.index', [
            'batches' => $this->myBatches()->with(['course', 'branch', 'schedules'])
                ->withCount(['enrollments' => fn ($q) => $q->where('status', 'active')])
                ->orderByRaw("case status when 'active' then 0 when 'upcoming' then 1 else 2 end")
                ->get(),
        ]);
    }

    public function show(Batch $batch): View
    {
        $this->authorizeBatch($batch);

        $batch->load(['course', 'branch', 'schedules', 'materials' => fn ($q) => $q->latest(), 'announcements' => fn ($q) => $q->latest()->take(5)]);
        $students = $batch->activeStudents()->orderBy('full_name')->get();

        return view('teacher.batches.show', [
            'batch' => $batch,
            'students' => $students->map(fn ($s) => [
                'student' => $s,
                'attendance' => $s->attendanceRate($batch->id),
                'average' => $s->averageExamPercent($batch->id),
            ]),
        ]);
    }

    public function announce(Request $request, Batch $batch): RedirectResponse
    {
        $this->authorizeBatch($batch);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:3000'],
        ]);

        Announcement::create($data + ['audience' => 'batch', 'batch_id' => $batch->id, 'author_id' => auth()->id()]);

        foreach ($batch->activeStudents()->with(['user', 'guardians'])->get() as $student) {
            Notify::student($student, 'New announcement in :class: :title', ['class' => $batch->name, 'title' => $data['title']],
                route('student.announcements'), 'megaphone', route('parent.announcements'));
        }

        return back()->with('success', __('Announcement sent to the class.'));
    }

    /** Set the online meeting link (Zoom, Google Meet, Teams…) students use to join live sessions. */
    public function meeting(Request $request, Batch $batch): RedirectResponse
    {
        $this->authorizeBatch($batch);

        $data = $request->validate(['meeting_url' => ['nullable', 'url:http,https', 'max:500']]);
        $batch->update($data);

        return back()->with('success', $batch->meeting_url ? __('Meeting link saved. Students can join from their portal.') : __('Meeting link removed.'));
    }
}
