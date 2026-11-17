<?php

namespace App\Http\Controllers\TeacherPortal;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base for teacher-portal controllers. A teacher may only touch classes
 * assigned to them; everything else is a 403.
 */
abstract class TeacherController extends Controller
{
    protected function authorizeBatch(Batch $batch): void
    {
        abort_unless((int) $batch->teacher_id === (int) auth()->id(), 403, __('This class is not assigned to you.'));
    }

    protected function myBatches(): Builder
    {
        return Batch::where('teacher_id', auth()->id());
    }
}
