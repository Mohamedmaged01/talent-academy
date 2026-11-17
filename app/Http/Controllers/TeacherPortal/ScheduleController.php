<?php

namespace App\Http\Controllers\TeacherPortal;

use App\Models\BatchSchedule;
use Illuminate\View\View;

class ScheduleController extends TeacherController
{
    public function __invoke(): View
    {
        $slots = BatchSchedule::with('batch.course')
            ->whereIn('batch_id', $this->myBatches()->whereIn('status', ['active', 'upcoming'])->pluck('id'))
            ->orderBy('starts_at')
            ->get()
            ->groupBy('day_of_week');

        return view('portal.schedule', ['slots' => $slots, 'days' => BatchSchedule::DAYS, 'showTeacher' => false]);
    }
}
