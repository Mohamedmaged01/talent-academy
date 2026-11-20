<?php

namespace App\Http\Controllers\TeacherPortal;

use App\Models\PayrollRecord;
use Illuminate\View\View;

class PayslipController extends TeacherController
{
    public function index(): View
    {
        return view('teacher.payslips', [
            'records' => auth()->user()->payrollRecords()->where('status', 'paid')->orderByDesc('period')->get(),
            'profile' => auth()->user()->teacherProfile,
        ]);
    }

    public function show(PayrollRecord $record): View
    {
        abort_unless((int) $record->user_id === (int) auth()->id() && $record->isPaid(), 403);

        return view('payroll.payslip', ['record' => $record->load(['user.teacherProfile', 'branch', 'payer'])]);
    }
}
