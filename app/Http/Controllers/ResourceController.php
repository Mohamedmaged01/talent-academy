<?php

namespace App\Http\Controllers;

use App\Models\Download;
use App\Models\LibraryItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Browsing and opening the digital library (teachers and students) and the
 * downloads center (everyone, filtered by audience). Files live on the private
 * disk and are streamed from here, so audience rules cannot be bypassed.
 */
class ResourceController extends Controller
{
    public function library(Request $request): View
    {
        return view('library.index', [
            'items' => LibraryItem::published()->with('course')
                ->when($request->query('type'), fn ($q, $v) => $q->where('type', $v))
                ->when($request->query('pathway'), fn ($q, $v) => $q->whereIn('pathway', [$v, 'all']))
                ->when($request->query('q'), fn ($q, $v) => $q->where(fn ($q) => $q->where('title', 'like', "%{$v}%")->orWhere('author', 'like', "%{$v}%")))
                ->orderByDesc('downloads_count')->latest()
                ->paginate(18)
                ->withQueryString(),
        ]);
    }

    public function openLibraryItem(LibraryItem $item): StreamedResponse|RedirectResponse
    {
        abort_unless($item->is_published || auth()->user()->isAdmin(), 404);
        $item->increment('downloads_count');

        return $this->deliver($item->file_path, $item->original_name, $item->url);
    }

    /** Downloads center inside the portal (signed-in users). */
    public function downloads(Request $request): View
    {
        return view('downloads.portal', ['downloads' => $this->downloadList($request)]);
    }

    /** Public downloads page on the website. */
    public function publicDownloads(Request $request): View
    {
        return view('site.downloads', ['downloads' => $this->downloadList($request)]);
    }

    public function openDownload(Download $download): StreamedResponse|RedirectResponse
    {
        abort_unless($download->is_published && in_array($download->audience, Download::audiencesFor(auth()->user()), true), 404);
        $download->increment('downloads_count');

        return $this->deliver($download->file_path, $download->original_name, $download->url);
    }

    private function downloadList(Request $request)
    {
        return Download::visibleTo(auth()->user())
            ->when($request->query('category'), fn ($q, $v) => $q->where('category', $v))
            ->orderBy('category')->latest()
            ->get()
            ->groupBy('category');
    }

    private function deliver(?string $path, ?string $name, ?string $url): StreamedResponse|RedirectResponse
    {
        if ($path && Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->download($path, $name ?: basename($path));
        }

        abort_unless($url, 404);

        return redirect()->away($url);
    }
}
