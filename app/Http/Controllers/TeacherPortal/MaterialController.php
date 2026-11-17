<?php

namespace App\Http\Controllers\TeacherPortal;

use App\Models\Batch;
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MaterialController extends TeacherController
{
    public function store(Request $request, Batch $batch): RedirectResponse
    {
        $this->authorizeBatch($batch);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(Material::TYPES)],
            'url' => ['nullable', 'required_unless:type,file', 'url', 'max:500'],
            'file' => ['nullable', 'required_if:type,file', 'file', 'max:20480', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,png,jpg,jpeg,mp4'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $material = new Material($data);
        $material->batch_id = $batch->id;
        $material->uploaded_by = auth()->id();

        if ($request->hasFile('file')) {
            $material->file_path = $request->file('file')->store("materials/{$batch->id}", 'public');
            $material->original_name = $request->file('file')->getClientOriginalName();
        }

        $material->save();

        return back()->with('success', __('Material shared with the class.'));
    }

    public function destroy(Material $material): RedirectResponse
    {
        $this->authorizeBatch($material->batch);

        if ($material->file_path) {
            Storage::disk('public')->delete($material->file_path);
        }

        $material->delete();

        return back()->with('success', __('Material removed.'));
    }
}
