<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Batch;
use App\Models\Certificate;
use App\Models\Student;
use App\Services\CertificateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificateController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.certificates.index', [
            'certificates' => Certificate::with(['student', 'course'])
                ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
                ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($q) => $q
                    ->where('serial', 'like', "%{$term}%")
                    ->orWhereHas('student', fn ($q) => $q->search($term))))
                ->latest('issued_at')->latest('id')
                ->paginate(15)
                ->withQueryString(),
            'batches' => Batch::with('course')->whereIn('status', ['active', 'completed'])->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.certificates.create', [
            'students' => Student::orderBy('full_name')->get(['id', 'full_name', 'code']),
            'batches' => Batch::with('course')->orderBy('name')->get(),
            'selectedStudent' => $request->query('student'),
        ]);
    }

    public function store(Request $request, CertificateService $service): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'batch_id' => ['nullable', 'exists:batches,id'],
            'title_ar' => ['required', 'string', 'max:200'],
            'title_en' => ['required', 'string', 'max:200'],
            'grade' => ['nullable', 'string', 'max:50'],
        ]);

        $certificate = $service->issue(
            Student::findOrFail($data['student_id']),
            isset($data['batch_id']) ? Batch::find($data['batch_id']) : null,
            $data['title_ar'],
            $data['title_en'],
            $data['grade'] ?? null,
        );

        return redirect()->route('admin.certificates.show', $certificate)->with('success', __('Certificate issued.'));
    }

    /** Issue certificates to every eligible student in a class who does not already have one. */
    public function issueBatch(Batch $batch, CertificateService $service): RedirectResponse
    {
        $batch->load('course');
        $issued = 0;

        foreach ($service->eligibility($batch) as $row) {
            if ($row['eligible'] && ! $row['certificate']) {
                $service->issue(
                    $row['student'],
                    $batch,
                    'شهادة إتمام: '.$batch->course->title_ar,
                    'Certificate of Completion: '.$batch->course->title_en,
                    CertificateService::gradeFor($row['average']),
                );
                $issued++;
            }
        }

        return back()->with($issued ? 'success' : 'warning', $issued
            ? __(':count certificate(s) issued.', ['count' => $issued])
            : __('No new eligible students in this class.'));
    }

    public function show(Certificate $certificate): View
    {
        return view('certificates.print', ['certificate' => $certificate->load(['student', 'course', 'batch.teacher'])]);
    }

    public function revoke(Request $request, Certificate $certificate): RedirectResponse
    {
        $data = $request->validate(['revoked_reason' => ['required', 'string', 'max:255']]);

        $certificate->update(['status' => 'revoked'] + $data);
        ActivityLog::record('certificate.revoked', "Certificate {$certificate->serial} revoked: {$data['revoked_reason']}", $certificate);

        return back()->with('success', __('Certificate revoked.'));
    }
}
