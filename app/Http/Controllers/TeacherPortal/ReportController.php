<?php

namespace App\Http\Controllers\TeacherPortal;

use App\Models\Batch;
use App\Models\Student;
use App\Support\Csv;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Class performance for a teacher: attendance, exam averages, homework and students at risk. */
class ReportController extends TeacherController
{
    public function index(Request $request): View
    {
        $batches = $this->myBatches()->with('course')->whereIn('status', ['active', 'completed', 'upcoming'])->orderBy('name')->get();
        $summaries = $batches->map(fn (Batch $batch) => ['batch' => $batch] + $this->summary($this->rows($batch)));
        $selected = $batches->firstWhere('id', (int) $request->query('batch')) ?? $batches->first();

        return view('teacher.reports', [
            'summaries' => $summaries,
            'batch' => $selected,
            'rows' => $selected ? $this->rows($selected) : collect(),
        ]);
    }

    public function export(Batch $batch): StreamedResponse
    {
        $this->authorizeBatch($batch);

        return Csv::download('class-report-'.$batch->code.'-'.now()->format('Y-m-d').'.csv',
            ['Student code', 'Student', 'Attendance %', 'Exam average %', 'Assignments submitted', 'Assignments total', 'At risk'],
            $this->rows($batch)->map(fn ($r) => [
                $r['student']->code, $r['student']->full_name, $r['attendance'] ?? '', $r['average'] ?? '',
                $r['submitted'], $r['assignments'], $r['at_risk'] ? 'yes' : 'no',
            ]));
    }

    /** @return Collection<int, array> one row per active student */
    private function rows(Batch $batch): Collection
    {
        $assignmentIds = $batch->assignments()->pluck('id');

        return $batch->activeStudents()->orderBy('full_name')->get()->map(function (Student $student) use ($batch, $assignmentIds) {
            $attendance = $student->attendanceRate($batch->id);
            $average = $student->averageExamPercent($batch->id);
            $submitted = $student->submissions()->whereIn('assignment_id', $assignmentIds)->count();

            return [
                'student' => $student,
                'attendance' => $attendance,
                'average' => $average,
                'submitted' => $submitted,
                'assignments' => $assignmentIds->count(),
                'at_risk' => ($attendance !== null && $attendance < 80) || ($average !== null && $average < 70),
            ];
        });
    }

    private function summary(Collection $rows): array
    {
        $assignments = $rows->sum('assignments');

        return [
            'students' => $rows->count(),
            'attendance' => $rows->whereNotNull('attendance')->avg('attendance'),
            'average' => $rows->whereNotNull('average')->avg('average'),
            'submission_rate' => $assignments > 0 ? round($rows->sum('submitted') / $assignments * 100, 1) : null,
            'at_risk' => $rows->where('at_risk', true)->count(),
        ];
    }
}
