<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AttendanceReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $from = Carbon::parse($request->query('from', now()->subDays(30)->toDateString()))->startOfDay();
        $to = Carbon::parse($request->query('to', now()->toDateString()))->endOfDay();

        $records = AttendanceRecord::whereBetween('date', [$from, $to])
            ->when($request->query('batch'), fn ($q, $v) => $q->where('batch_id', $v))
            ->get(['batch_id', 'student_id', 'status']);

        $byBatch = Batch::with('course')
            ->whereIn('id', $records->pluck('batch_id')->unique())
            ->get()
            ->map(function (Batch $batch) use ($records) {
                $rows = $records->where('batch_id', $batch->id);
                $total = max(1, $rows->count());

                return [
                    'batch' => $batch,
                    'present' => $rows->where('status', 'present')->count(),
                    'late' => $rows->where('status', 'late')->count(),
                    'absent' => $rows->where('status', 'absent')->count(),
                    'excused' => $rows->where('status', 'excused')->count(),
                    'rate' => round($rows->whereIn('status', ['present', 'late'])->count() / $total * 100, 1),
                ];
            })
            ->sortBy('rate');

        // Students with the most absences in the window: the follow-up list.
        $absentees = AttendanceRecord::with(['student', 'batch'])
            ->whereBetween('date', [$from, $to])
            ->where('status', 'absent')
            ->when($request->query('batch'), fn ($q, $v) => $q->where('batch_id', $v))
            ->get()
            ->groupBy('student_id')
            ->map(fn ($rows) => ['student' => $rows->first()->student, 'count' => $rows->count(), 'batch' => $rows->first()->batch])
            ->sortByDesc('count')
            ->take(10);

        return view('admin.attendance', [
            'byBatch' => $byBatch,
            'absentees' => $absentees,
            'batches' => Batch::orderBy('name')->get(['id', 'name', 'code']),
            'from' => $from,
            'to' => $to,
            'totals' => [
                'present' => $records->where('status', 'present')->count(),
                'late' => $records->where('status', 'late')->count(),
                'absent' => $records->where('status', 'absent')->count(),
                'excused' => $records->where('status', 'excused')->count(),
            ],
        ]);
    }
}
