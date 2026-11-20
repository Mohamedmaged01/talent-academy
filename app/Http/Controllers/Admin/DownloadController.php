<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Download;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DownloadController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.downloads.index', [
            'downloads' => Download::with('uploader')
                ->when($request->query('category'), fn ($q, $v) => $q->where('category', $v))
                ->when($request->query('audience'), fn ($q, $v) => $q->where('audience', $v))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.downloads.form', ['download' => new Download(['category' => 'forms', 'audience' => 'public', 'is_published' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $download = new Download($this->validated($request, true));
        $download->uploaded_by = auth()->id();
        $this->storeFile($request, $download);
        $download->save();

        ActivityLog::record('download.created', "Download \"{$download->title_ar}\" published", $download);

        return redirect()->route('admin.downloads.index')->with('success', __('File added to the downloads center.'));
    }

    public function edit(Download $download): View
    {
        return view('admin.downloads.form', ['download' => $download]);
    }

    public function update(Request $request, Download $download): RedirectResponse
    {
        $download->fill($this->validated($request, false));
        $this->storeFile($request, $download);
        $download->save();

        return redirect()->route('admin.downloads.index')->with('success', __('Download updated.'));
    }

    public function destroy(Download $download): RedirectResponse
    {
        if ($download->file_path) {
            Storage::disk('local')->delete($download->file_path);
        }

        $download->delete();

        return back()->with('success', __('Download deleted.'));
    }

    private function validated(Request $request, bool $creating): array
    {
        $data = $request->validate([
            'title_ar' => ['required', 'string', 'max:200'],
            'title_en' => ['nullable', 'string', 'max:200'],
            'description_ar' => ['nullable', 'string', 'max:1000'],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'category' => ['required', Rule::in(Download::CATEGORIES)],
            'audience' => ['required', Rule::in(Download::AUDIENCES)],
            'url' => ['nullable', 'url:http,https', 'max:500'],
            'file' => ['nullable', 'file', 'max:20480', 'mimes:'.LibraryController::MIMES],
        ]);

        if ($creating && ! $request->hasFile('file') && blank($data['url'] ?? null)) {
            throw ValidationException::withMessages(['file' => __('Upload a file or enter a link.')]);
        }

        unset($data['file']);

        return $data + ['is_published' => $request->boolean('is_published')];
    }

    private function storeFile(Request $request, Download $download): void
    {
        if (! $request->hasFile('file')) {
            return;
        }

        if ($download->file_path) {
            Storage::disk('local')->delete($download->file_path);
        }

        $file = $request->file('file');
        $download->file_path = $file->store('downloads', 'local');
        $download->original_name = $file->getClientOriginalName();
        $download->size_bytes = $file->getSize();
    }
}
