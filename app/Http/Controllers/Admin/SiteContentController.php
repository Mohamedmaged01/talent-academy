<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\MediaItem;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Edit the public website's texts and images, and manage the media library they come from. */
class SiteContentController extends Controller
{
    public function edit(): View
    {
        return view('admin.content.edit', [
            'values' => collect(Setting::CONTENT_DEFAULTS)->mapWithKeys(fn ($d, $key) => [$key => Setting::get($key)]),
            'media' => MediaItem::where('mime', 'like', 'image/%')->latest()->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = collect(Setting::CONTENT_DEFAULTS)->mapWithKeys(fn ($v, $key) => [$key => ['nullable', 'string', 'max:3000']])->all();

        foreach (Setting::CONTENT_IMAGES as $key) {
            $rules[$key] = ['nullable', 'string', 'max:255', Rule::in(MediaItem::pluck('path')->push('')->all())];
        }

        Setting::put(array_map(fn ($v) => $v ?? '', $request->validate($rules)));
        ActivityLog::record('content.updated', 'Website content updated');

        return back()->with('success', __('Website content saved.'));
    }

    public function media(): View
    {
        return view('admin.content.media', ['items' => MediaItem::with('uploader')->latest()->paginate(24)]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'files' => ['required', 'array', 'max:10'],
            'files.*' => ['file', 'max:8192', 'mimes:jpg,jpeg,png,webp,gif,svg,pdf'],
            'title' => ['nullable', 'string', 'max:150'],
        ]);

        foreach ($data['files'] as $file) {
            MediaItem::create([
                'title' => $data['title'] ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'path' => $file->store('media', 'public'),
                'mime' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        }

        return back()->with('success', trans_choice(':count file uploaded.|:count files uploaded.', count($data['files']), ['count' => count($data['files'])]));
    }

    public function updateMedia(Request $request, MediaItem $item): RedirectResponse
    {
        $item->update($request->validate(['title' => ['required', 'string', 'max:150'], 'alt' => ['nullable', 'string', 'max:255']]));

        return back()->with('success', __('Media details saved.'));
    }

    public function destroyMedia(MediaItem $item): RedirectResponse
    {
        $inUse = collect(Setting::CONTENT_IMAGES)->contains(fn ($key) => Setting::get($key) === $item->path);

        if ($inUse) {
            return back()->with('error', __('This image is used on the website. Choose another image there first.'));
        }

        if (! str_starts_with($item->path, 'images/')) {
            Storage::disk('public')->delete($item->path);
        }

        $item->delete();

        return back()->with('success', __('File deleted.'));
    }
}
