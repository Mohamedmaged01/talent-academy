<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $courses = Course::withCount('batches')
            ->when($request->query('pathway'), fn ($q, $v) => $q->where('pathway', $v))
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('title_ar', 'like', "%{$term}%")->orWhere('title_en', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%")))
            ->orderBy('pathway')->orderBy('sort_order')
            ->paginate(15)
            ->withQueryString();

        return view('admin.courses.index', compact('courses'));
    }

    public function create(): View
    {
        return view('admin.courses.form', ['course' => new Course(['pathway' => 'training', 'is_published' => true, 'price' => 0])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $course = Course::create($this->validated($request));
        ActivityLog::record('course.created', "Program {$course->code} created", $course);

        return redirect()->route('admin.courses.index')->with('success', __('Program saved.'));
    }

    public function edit(Course $course): View
    {
        return view('admin.courses.form', compact('course'));
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $course->update($this->validated($request, $course));
        ActivityLog::record('course.updated', "Program {$course->code} updated", $course);

        return redirect()->route('admin.courses.index')->with('success', __('Program saved.'));
    }

    public function destroy(Course $course): RedirectResponse
    {
        if ($course->batches()->exists()) {
            $course->update(['is_published' => false]);

            return back()->with('warning', __('This program has classes, so it was unpublished instead of deleted.'));
        }

        $course->delete();
        ActivityLog::record('course.deleted', "Program {$course->code} deleted");

        return back()->with('success', __('Program deleted.'));
    }

    private function validated(Request $request, ?Course $course = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('courses')->ignore($course?->id)],
            'pathway' => ['required', Rule::in(Course::PATHWAYS)],
            'category_ar' => ['nullable', 'string', 'max:100'],
            'category_en' => ['nullable', 'string', 'max:100'],
            'title_ar' => ['required', 'string', 'max:200'],
            'title_en' => ['required', 'string', 'max:200'],
            'description_ar' => ['nullable', 'string', 'max:3000'],
            'description_en' => ['nullable', 'string', 'max:3000'],
            'level_ar' => ['nullable', 'string', 'max:100'],
            'level_en' => ['nullable', 'string', 'max:100'],
            'duration_hours' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'duration_weeks' => ['nullable', 'integer', 'min:1', 'max:200'],
            'price' => ['required', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('image')) {
            if ($course?->image_path && ! str_starts_with($course->image_path, 'images/')) {
                Storage::disk('public')->delete($course->image_path);
            }
            $data['image_path'] = $request->file('image')->store('courses', 'public');
        }

        unset($data['image']);
        $data['is_published'] = $request->boolean('is_published');
        $data['sort_order'] ??= 0;

        return $data;
    }
}
