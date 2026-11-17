<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\Batch;
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

        return back()->with('success', __('Announcement published.'));
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return back()->with('success', __('Announcement deleted.'));
    }
}
