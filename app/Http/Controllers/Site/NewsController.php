<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\NewsArticle;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(): View
    {
        return view('site.news.index', [
            'articles' => NewsArticle::published()->latest('published_at')->paginate(9),
        ]);
    }

    public function show(NewsArticle $article): View
    {
        abort_unless($article->is_published, 404);

        return view('site.news.show', [
            'article' => $article,
            'more' => NewsArticle::published()->whereKeyNot($article->id)->latest('published_at')->take(3)->get(),
        ]);
    }
}
