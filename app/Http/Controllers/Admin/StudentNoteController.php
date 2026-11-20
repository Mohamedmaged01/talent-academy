<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudentNoteController extends Controller
{
    public function store(Request $request, Student $student): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $student->fileNotes()->create($data + ['author_id' => auth()->id(), 'visible_to_parent' => $request->boolean('visible_to_parent')]);

        return back()->with('success', __('Note saved to the student file.'));
    }

    public function destroy(StudentNote $note): RedirectResponse
    {
        $note->delete();

        return back()->with('success', __('Note deleted.'));
    }
}
