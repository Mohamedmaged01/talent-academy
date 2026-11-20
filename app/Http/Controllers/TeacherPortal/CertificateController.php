<?php

namespace App\Http\Controllers\TeacherPortal;

use App\Models\Batch;
use App\Models\CertificateNomination;
use App\Services\CertificateService;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Teachers check who meets the certificate rules (≥ 80% attendance, ≥ 70% exam
 * average) and nominate them. An administrator approves, which issues the certificate.
 */
class CertificateController extends TeacherController
{
    public function index(Request $request, CertificateService $certificates): View
    {
        $batches = $this->myBatches()->with('course')->whereIn('status', ['active', 'completed'])->orderByRaw("case status when 'completed' then 0 else 1 end")->get();
        $batch = $batches->firstWhere('id', (int) $request->query('batch')) ?? $batches->first();

        return view('teacher.certificates', [
            'batches' => $batches,
            'batch' => $batch,
            'rows' => $batch ? $certificates->eligibility($batch) : collect(),
            'nominations' => $batch ? CertificateNomination::where('batch_id', $batch->id)->get()->keyBy('student_id') : collect(),
            'minAttendance' => CertificateService::MIN_ATTENDANCE,
            'minAverage' => CertificateService::MIN_AVERAGE,
        ]);
    }

    public function nominate(Request $request, Batch $batch, CertificateService $certificates): RedirectResponse
    {
        $this->authorizeBatch($batch);

        $data = $request->validate([
            'students' => ['required', 'array', 'min:1'],
            'students.*' => ['integer'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $eligible = $certificates->eligibility($batch)
            ->filter(fn ($row) => $row['eligible'] && ! $row['certificate'])
            ->keyBy(fn ($row) => $row['student']->id);

        $count = 0;

        foreach (array_unique($data['students']) as $studentId) {
            if (! $eligible->has($studentId)) {
                continue; // ineligible or already certified: silently skipped, the page shows why
            }

            $nomination = CertificateNomination::firstOrNew(['batch_id' => $batch->id, 'student_id' => $studentId]);

            if ($nomination->exists && $nomination->status !== 'rejected') {
                continue;
            }

            $nomination->fill([
                'nominated_by' => auth()->id(), 'note' => $data['note'] ?? null, 'status' => 'pending',
                'reviewed_by' => null, 'reviewed_at' => null, 'review_note' => null,
            ])->save();
            $count++;
        }

        if ($count === 0) {
            return back()->with('warning', __('No eligible students were selected.'));
        }

        Notify::admins(':teacher nominated :count student(s) in :class for certificates', ['teacher' => auth()->user()->name, 'count' => $count, 'class' => $batch->name],
            route('admin.nominations.index'), 'award', 'certificates');

        return back()->with('success', trans_choice(':count nomination sent to the administration.|:count nominations sent to the administration.', $count, ['count' => $count]));
    }
}
