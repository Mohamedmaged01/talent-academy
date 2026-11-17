<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\NewsArticle;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('site.home', [
            'news' => NewsArticle::published()->latest('published_at')->take(4)->get(),
            'academicCourses' => Course::published()->where('pathway', 'academic')->orderBy('sort_order')->take(3)->get(),
            'trainingCourses' => Course::published()->where('pathway', 'training')->orderBy('sort_order')->take(3)->get(),
        ]);
    }

    public function about(): View
    {
        return view('site.about');
    }

    public function privacy(): View
    {
        return view('site.legal', ['type' => 'privacy']);
    }

    public function terms(): View
    {
        return view('site.legal', ['type' => 'terms']);
    }
}
