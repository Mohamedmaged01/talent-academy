<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BatchSchedule;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimetableController extends Controller
{
    public function __invoke(Request $request): View
    {
        $slots = BatchSchedule::with(['batch.course', 'batch.teacher'])
            ->whereHas('batch', fn ($q) => $q->whereIn('status', ['active', 'upcoming'])
                ->when($request->query('branch'), fn ($q, $v) => $q->where('branch_id', $v)))
            ->orderBy('starts_at')
            ->get()
            ->groupBy('day_of_week');

        return view('admin.timetable', [
            'slots' => $slots,
            'days' => BatchSchedule::DAYS,
            'branches' => Branch::active()->get(),
        ]);
    }
}
