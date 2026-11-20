<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\Batch;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        return view('admin.announcements.index', [
            'announcements' => Announcement::with(['author', 'batch'])->latest()->paginate(15),
            'batches' => Batch::whereIn('status', ['active', 'upcoming'])->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:3000'],
            'audience' => ['required', Rule::in(Announcement::AUDIENCES)],
            'batch_id' => ['nullable', 'required_if:audience,batch', 'exists:batches,id'],
        ]);

        $announcement = Announcement::create($data + [
            'author_id' => auth()->id(),
            'is_pinned' => $request->boolean('is_pinned'),
        ]);

        ActivityLog::record('announcement.created', "Announcement \"{$announcement->title}\" published to {$announcement->audience}", $announcement);

        // Everyone who can see it gets a notification in their own portal.
        $roles = ['all' => ['teacher', 'student', 'parent'], 'students' => ['student'], 'teachers' => ['teacher'], 'parents' => ['parent']][$announcement->audience] ?? [];
        $routes = ['teacher' => 'teacher.announcements', 'student' => 'student.announcements', 'parent' => 'parent.announcements'];
        foreach ($roles as $role) {
            Notify::users(User::where('role', $role)->where('is_active', true)->get(), 'New announcement: :title', ['title' => $announcement->title], route($routes[$role]), 'megaphone');
        }

        if ($announcement->audience === 'batch' && $announcement->batch) {
            foreach ($announcement->batch->activeStudents()->with(['user', 'guardians'])->get() as $student) {
                Notify::student($student, 'New announcement: :title', ['title' => $announcement->title], route('student.announcements'), 'megaphone', route('parent.announcements'));
            }
            Notify::users($announcement->batch->teacher, 'New announcement: :title', ['title' => $announcement->title], route('teacher.announcements'), 'megaphone');
        }

        return back()->with('success', __('Announcement published.'));
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return back()->with('success', __('Announcement deleted.'));
    }
}
