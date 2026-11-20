<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\AdmissionRequest;
use App\Models\Branch;
use App\Models\Course;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdmissionController extends Controller
{
    public function create(Request $request): View
    {
        return view('site.apply', [
            'courses' => Course::published()->orderBy('pathway')->orderBy('sort_order')->get(),
            'branches' => Branch::active()->get(),
            'selectedCourse' => $request->query('course'),
            'selectedPathway' => in_array($request->query('pathway'), Course::PATHWAYS, true) ? $request->query('pathway') : 'academic',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^[0-9+\s-]{8,20}$/'],
            'email' => ['nullable', 'email', 'max:120'],
            'pathway' => ['required', Rule::in(Course::PATHWAYS)],
            'course_id' => ['nullable', Rule::exists('courses', 'id')->where('is_published', true)],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('status', 'active')],
            'grade_level' => ['nullable', 'string', 'max:60'],
            'guardian_name' => ['nullable', 'string', 'max:120'],
            'guardian_phone' => ['nullable', 'string', 'regex:/^[0-9+\s-]{8,20}$/'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $admission = AdmissionRequest::create($data + [
            'reference' => AdmissionRequest::newReference(),
            'status' => 'pending',
        ]);

        Notify::admins('New online application :reference from :name', ['reference' => $admission->reference, 'name' => $admission->full_name],
            route('admin.admissions.show', $admission), 'user-plus', 'admissions');

        return redirect()->route('admissions.thanks', $admission->reference);
    }

    public function thanks(string $reference): View
    {
        $admission = AdmissionRequest::where('reference', $reference)->firstOrFail();

        return view('site.apply-thanks', compact('admission'));
    }
}
