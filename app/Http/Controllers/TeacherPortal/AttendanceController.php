<?php

namespace App\Http\Controllers\TeacherPortal;

use App\Models\ActivityLog;
use App\Models\AttendanceRecord;
use App\Models\Batch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttendanceController extends TeacherController
{
    public function edit(Request $request, Batch $batch): View
    {
        $this->authorizeBatch($batch);

        $date = Carbon::parse($request->query('date', now()->toDateString()));
        if ($date->isFuture()) {
            $date = now();
        }

        $records = AttendanceRecord::where('batch_id', $batch->id)->whereDate('date', $date)->get()->keyBy('student_id');

        $history = AttendanceRecord::where('batch_id', $batch->id)
            ->selectRaw('date, sum(case when status in (\'present\', \'late\') then 1 else 0 end) as attended, count(*) as total')
            ->groupBy('date')
            ->orderByDesc('date')
            ->take(10)
            ->get();

        return view('teacher.attendance', [
            'batch' => $batch->load('course'),
            'date' => $date,
            'students' => $batch->activeStudents()->orderBy('full_name')->get(),
            'records' => $records,
            'history' => $history,
        ]);
    }

    public function update(Request $request, Batch $batch): RedirectResponse
    {
        $this->authorizeBatch($batch);

        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'status' => ['required', 'array'],
            'status.*' => ['required', Rule::in(AttendanceRecord::STATUSES)],
            'note' => ['array'],
            'note.*' => ['nullable', 'string', 'max:255'],
        ]);

        $enrolled = $batch->activeStudents()->pluck('students.id')->map(fn ($id) => (int) $id)->all();
        $date = Carbon::parse($data['date'])->toDateString();

        DB::transaction(function () use ($data, $enrolled, $batch, $date) {
            foreach ($data['status'] as $studentId => $status) {
                // Ignore ids that are not on this class roster.
                if (! in_array((int) $studentId, $enrolled, true)) {
                    continue;
                }

                AttendanceRecord::updateOrCreate(
                    ['batch_id' => $batch->id, 'student_id' => $studentId, 'date' => $date],
                    ['status' => $status, 'note' => $data['note'][$studentId] ?? null, 'recorded_by' => auth()->id()],
                );
            }
        });

        ActivityLog::record('attendance.recorded', "Attendance recorded for {$batch->code} on {$date}", $batch);

        return redirect()->route('teacher.attendance.edit', [$batch, 'date' => $date])->with('success', __('Attendance saved.'));
    }
}
