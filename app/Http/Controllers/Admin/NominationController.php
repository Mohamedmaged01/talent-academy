<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CertificateNomination;
use App\Services\CertificateService;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Teachers' certificate nominations. Approving re-checks the rules and issues the certificate. */
class NominationController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');

        return view('admin.certificates.nominations', [
            'nominations' => CertificateNomination::with(['student', 'batch.course', 'nominator', 'reviewer', 'certificate'])
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'status' => $status,
            'counts' => CertificateNomination::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function approve(CertificateNomination $nomination, CertificateService $certificates): RedirectResponse
    {
        if ($nomination->status !== 'pending') {
            return back()->with('error', __('This nomination has already been decided.'));
        }

        $row = $certificates->eligibility($nomination->batch)->first(fn ($r) => $r['student']->id === $nomination->student_id);

        if (! $row || ! $row['eligible']) {
            return back()->with('error', __('The student no longer meets the certificate requirements.'));
        }

        if ($row['certificate']) {
            $nomination->update(['status' => 'approved', 'certificate_id' => $row['certificate']->id, 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);

            return back()->with('success', __('The student already had a certificate for this class; nomination closed.'));
        }

        DB::transaction(function () use ($nomination, $certificates, $row) {
            $course = $nomination->batch->course;
            $certificate = $certificates->issue($nomination->student, $nomination->batch, $course->title_ar, $course->title_en, CertificateService::gradeFor($row['average']));

            $nomination->update(['status' => 'approved', 'certificate_id' => $certificate->id, 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        });

        Notify::users($nomination->nominator, 'Your nomination of :name was approved and the certificate issued', ['name' => $nomination->student->full_name],
            route('teacher.certificates', ['batch' => $nomination->batch_id]), 'award');

        return back()->with('success', __('Certificate issued.'));
    }

    public function reject(Request $request, CertificateNomination $nomination): RedirectResponse
    {
        if ($nomination->status !== 'pending') {
            return back()->with('error', __('This nomination has already been decided.'));
        }

        $data = $request->validate(['review_note' => ['required', 'string', 'max:255']]);
        $nomination->update($data + ['status' => 'rejected', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        ActivityLog::record('nomination.rejected', "Certificate nomination for {$nomination->student->full_name} rejected", $nomination);

        Notify::users($nomination->nominator, 'Your nomination of :name was not approved: :reason', ['name' => $nomination->student->full_name, 'reason' => $data['review_note']],
            route('teacher.certificates', ['batch' => $nomination->batch_id]), 'award');

        return back()->with('success', __('Nomination rejected.'));
    }
}
