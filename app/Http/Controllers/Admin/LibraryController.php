<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\LibraryItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LibraryController extends Controller
{
    public const MIMES = 'pdf,epub,doc,docx,ppt,pptx,xls,xlsx,zip,rar,txt,png,jpg,jpeg,mp3,mp4';

    public function index(Request $request): View
    {
        return view('admin.library.index', [
            'items' => LibraryItem::with(['course', 'uploader'])
                ->when($request->query('type'), fn ($q, $v) => $q->where('type', $v))
                ->when($request->query('pathway'), fn ($q, $v) => $q->where('pathway', $v))
                ->when($request->query('q'), fn ($q, $v) => $q->where(fn ($q) => $q->where('title', 'like', "%{$v}%")->orWhere('author', 'like', "%{$v}%")))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'totals' => [
                'items' => LibraryItem::count(),
                'downloads' => (int) LibraryItem::sum('downloads_count'),
                'kits' => LibraryItem::where('type', 'training_kit')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.library.form', ['item' => new LibraryItem(['type' => 'book', 'pathway' => 'all', 'is_published' => true]), 'courses' => Course::orderBy('title_en')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $item = new LibraryItem($this->validated($request, true));
        $item->uploaded_by = auth()->id();
        $this->storeFile($request, $item);
        $item->save();

        ActivityLog::record('library.created', "Library item \"{$item->title}\" added", $item);

        return redirect()->route('admin.library.index')->with('success', __('Added to the library.'));
    }

    public function edit(LibraryItem $item): View
    {
        return view('admin.library.form', ['item' => $item, 'courses' => Course::orderBy('title_en')->get()]);
    }

    public function update(Request $request, LibraryItem $item): RedirectResponse
    {
        $item->fill($this->validated($request, false));
        $this->storeFile($request, $item);
        $item->save();

        return redirect()->route('admin.library.index')->with('success', __('Library item updated.'));
    }

    public function destroy(LibraryItem $item): RedirectResponse
    {
        if ($item->file_path) {
            Storage::disk('local')->delete($item->file_path);
        }

        $item->delete();
        ActivityLog::record('library.deleted', "Library item \"{$item->title}\" deleted");

        return back()->with('success', __('Library item deleted.'));
    }

    private function validated(Request $request, bool $creating): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'author' => ['nullable', 'string', 'max:150'],
            'type' => ['required', Rule::in(LibraryItem::TYPES)],
            'pathway' => ['required', Rule::in(LibraryItem::PATHWAYS)],
            'course_id' => ['nullable', 'exists:courses,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'pages' => ['nullable', 'integer', 'min:1', 'max:20000'],
            'url' => ['nullable', 'url:http,https', 'max:500'],
            'file' => ['nullable', 'file', 'max:51200', 'mimes:'.self::MIMES],
        ]);

        if ($creating && ! $request->hasFile('file') && blank($data['url'] ?? null)) {
            throw ValidationException::withMessages(['file' => __('Upload a file or enter a link.')]);
        }

        unset($data['file']);

        return $data + ['is_published' => $request->boolean('is_published')];
    }

    private function storeFile(Request $request, LibraryItem $item): void
    {
        if (! $request->hasFile('file')) {
            return;
        }

        if ($item->file_path) {
            Storage::disk('local')->delete($item->file_path);
        }

        $item->file_path = $request->file('file')->store('library', 'local');
        $item->original_name = $request->file('file')->getClientOriginalName();
    }
}
