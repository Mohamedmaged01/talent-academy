<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\NewsArticle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(): View
    {
        return view('admin.news.index', ['articles' => NewsArticle::latest('published_at')->paginate(12)]);
    }

    public function create(): View
    {
        return view('admin.news.form', ['article' => new NewsArticle(['is_published' => true, 'published_at' => now()])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $article = NewsArticle::create($this->validated($request));
        ActivityLog::record('news.created', "Article \"{$article->title_en}\" created", $article);

        return redirect()->route('admin.news.index')->with('success', __('Article saved.'));
    }

    public function edit(NewsArticle $article): View
    {
        return view('admin.news.form', compact('article'));
    }

    public function update(Request $request, NewsArticle $article): RedirectResponse
    {
        $article->update($this->validated($request, $article));

        return redirect()->route('admin.news.index')->with('success', __('Article saved.'));
    }

    public function destroy(NewsArticle $article): RedirectResponse
    {
        if ($article->image_path && ! str_starts_with($article->image_path, 'images/')) {
            Storage::disk('public')->delete($article->image_path);
        }

        $article->delete();
        ActivityLog::record('news.deleted', "Article \"{$article->title_en}\" deleted");

        return back()->with('success', __('Article deleted.'));
    }

    private function validated(Request $request, ?NewsArticle $article = null): array
    {
        $data = $request->validate([
            'title_ar' => ['required', 'string', 'max:200'],
            'title_en' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'alpha_dash', 'max:120', Rule::unique('news_articles')->ignore($article?->id)],
            'category_ar' => ['nullable', 'string', 'max:80'],
            'category_en' => ['nullable', 'string', 'max:80'],
            'excerpt_ar' => ['nullable', 'string', 'max:500'],
            'excerpt_en' => ['nullable', 'string', 'max:500'],
            'body_ar' => ['nullable', 'string', 'max:20000'],
            'body_en' => ['nullable', 'string', 'max:20000'],
            'author_name' => ['nullable', 'string', 'max:120'],
            'published_at' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $data['slug'] = $data['slug'] ?: ($article?->slug ?? $this->uniqueSlug($data['title_en']));
        $data['is_published'] = $request->boolean('is_published');

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('news', 'public');
        }

        unset($data['image']);

        return $data;
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'article';
        $slug = $base;
        $i = 2;

        while (NewsArticle::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
