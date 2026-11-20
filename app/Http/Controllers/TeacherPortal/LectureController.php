<?php

namespace App\Http\Controllers\TeacherPortal;

use App\Models\Batch;
use App\Models\Lecture;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LectureController extends TeacherController
{
    public function index(Request $request): View
    {
        $batches = $this->myBatches()->with('course')->whereIn('status', ['active', 'upcoming', 'completed'])->orderBy('name')->get();

        return view('teacher.lectures.index', [
            'batches' => $batches,
            'lectures' => Lecture::with('batch.course')->withCount('views')
                ->whereIn('batch_id', $batches->pluck('id'))
                ->when($request->query('batch'), fn ($q, $v) => $q->where('batch_id', $v))
                ->orderBy('batch_id')->orderBy('sort_order')->orderBy('id')
                ->get()
                ->groupBy('batch_id'),
            'enrolled' => $batches->mapWithKeys(fn (Batch $b) => [$b->id => $b->activeStudents()->count()]),
            'selected' => $request->query('batch'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $batch = Batch::findOrFail($data['batch_id']);
        $this->authorizeBatch($batch);

        $lecture = Lecture::create($data + [
            'teacher_id' => auth()->id(),
            'sort_order' => ($batch->lectures()->max('sort_order') ?? 0) + 1,
            'is_published' => $request->boolean('is_published', true),
        ]);

        if ($lecture->is_published) {
            foreach ($batch->activeStudents()->with('user')->get() as $student) {
                Notify::student($student, 'New recorded lecture in :class: :title', ['class' => $batch->name, 'title' => $lecture->title], route('student.lectures.show', $lecture), 'video');
            }
        }

        return redirect()->route('teacher.lectures.index', ['batch' => $batch->id])->with('success', __('Lecture published.'));
    }

    public function edit(Lecture $lecture): View
    {
        $this->authorizeBatch($lecture->batch);

        return view('teacher.lectures.edit', [
            'lecture' => $lecture,
            'batches' => $this->myBatches()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Lecture $lecture): RedirectResponse
    {
        $this->authorizeBatch($lecture->batch);
        $data = $this->validated($request);
        $this->authorizeBatch(Batch::findOrFail($data['batch_id']));

        $lecture->update($data + ['is_published' => $request->boolean('is_published')]);

        return redirect()->route('teacher.lectures.index', ['batch' => $lecture->batch_id])->with('success', __('Lecture updated.'));
    }

    public function move(Lecture $lecture, string $direction): RedirectResponse
    {
        $this->authorizeBatch($lecture->batch);

        $siblings = $lecture->batch->lectures()->get()->values();
        $index = $siblings->search(fn ($l) => $l->id === $lecture->id);
        $swapWith = $siblings->get($direction === 'up' ? $index - 1 : $index + 1);

        if ($swapWith) {
            // Re-number the whole list so equal sort values from older data cannot get stuck.
            $ordered = $siblings->all();
            [$ordered[$index], $ordered[$siblings->search($swapWith)]] = [$swapWith, $lecture];
            foreach (array_values($ordered) as $position => $item) {
                $item->update(['sort_order' => $position + 1]);
            }
        }

        return back();
    }

    public function destroy(Lecture $lecture): RedirectResponse
    {
        $this->authorizeBatch($lecture->batch);
        $lecture->delete();

        return back()->with('success', __('Lecture removed.'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'batch_id' => ['required', 'exists:batches,id'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'video_url' => ['required', 'url:http,https', 'max:500'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
        ]);
    }
}
