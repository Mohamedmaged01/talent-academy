<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AdmissionRequest;
use App\Models\Batch;
use App\Models\Branch;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdmissionController extends Controller
{
    public function index(Request $request): View
    {
        $admissions = AdmissionRequest::with(['course', 'branch'])
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('pathway'), fn ($q, $v) => $q->where('pathway', $v))
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('full_name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%")->orWhere('reference', 'like', "%{$term}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = AdmissionRequest::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.admissions.index', compact('admissions', 'counts'));
    }

    public function show(AdmissionRequest $admission): View
    {
        $admission->load(['course', 'branch', 'reviewer', 'student']);

        return view('admin.admissions.show', [
            'admission' => $admission,
            'branches' => Branch::active()->get(),
            'batches' => Batch::with('course')
                ->whereIn('status', ['upcoming', 'active'])
                ->when($admission->course_id, fn ($q) => $q->orderByRaw('course_id = ? desc', [$admission->course_id]))
                ->get(),
        ]);
    }

    public function update(Request $request, AdmissionRequest $admission): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'contacted', 'rejected'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $admission->update($data + ['reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        ActivityLog::record('admission.updated', "Admission {$admission->reference} marked {$data['status']}", $admission);

        return back()->with('success', __('Application updated.'));
    }

    /** Accept the application: create the student record (and optionally enroll in a class). */
    public function convert(Request $request, AdmissionRequest $admission): RedirectResponse
    {
        if ($admission->student_id) {
            return back()->with('error', __('This application has already been converted.'));
        }

        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'batch_id' => ['nullable', 'exists:batches,id'],
        ]);

        $student = DB::transaction(function () use ($admission, $data) {
            $student = Student::create([
                'code' => Student::nextCode(),
                'branch_id' => $data['branch_id'],
                'full_name' => $admission->full_name,
                'phone' => $admission->phone,
                'email' => $admission->email,
                'pathway' => $admission->pathway,
                'grade_level' => $admission->grade_level,
                'status' => 'active',
                'enrolled_at' => now(),
                'notes' => trim(($admission->guardian_name ? "Guardian: {$admission->guardian_name} {$admission->guardian_phone}\n" : '').($admission->notes ?? '')),
            ]);

            if (! empty($data['batch_id'])) {
                $student->enrollments()->create(['batch_id' => $data['batch_id'], 'status' => 'active', 'enrolled_at' => now()]);
            }

            $admission->update([
                'status' => 'accepted',
                'student_id' => $student->id,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            return $student;
        });

        ActivityLog::record('admission.accepted', "Admission {$admission->reference} accepted → student {$student->code}", $student);

        return redirect()->route('admin.students.show', $student)->with('success', __('Application accepted and student record created.'));
    }
}
