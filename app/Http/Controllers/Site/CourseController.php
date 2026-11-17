<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $pathway = in_array($request->query('pathway'), Course::PATHWAYS, true) ? $request->query('pathway') : null;

        $courses = Course::published()
            ->when($pathway, fn ($q) => $q->where('pathway', $pathway))
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('title_ar', 'like', "%{$term}%")
                ->orWhere('title_en', 'like', "%{$term}%")
                ->orWhere('category_ar', 'like', "%{$term}%")
                ->orWhere('category_en', 'like', "%{$term}%")))
            ->orderBy('pathway')
            ->orderBy('sort_order')
            ->get();

        return view('site.courses.index', compact('courses', 'pathway'));
    }

    public function show(Course $course): View
    {
        abort_unless($course->is_published, 404);

        $course->load(['batches' => fn ($q) => $q->whereIn('status', ['upcoming', 'active'])->with(['schedules', 'branch', 'teacher'])]);

        $related = Course::published()
            ->where('pathway', $course->pathway)
            ->whereKeyNot($course->id)
            ->take(3)
            ->get();

        return view('site.courses.show', compact('course', 'related'));
    }
}
