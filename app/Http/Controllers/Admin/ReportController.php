<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\ReportService;
use App\Support\Csv;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Reports & analytics: overview, academic, training, financial, branches & faculty. */
class ReportController extends Controller
{
    public const TABS = ['overview', 'academic', 'training', 'financial', 'branches'];

    public function __invoke(Request $request): View|StreamedResponse
    {
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'overview';
        $from = $request->date('from') ?? now()->subMonths(5)->startOfMonth();
        $to = $request->date('to') ?? now();
        $branchId = $request->user()->branchScope() ?? ($request->integer('branch') ?: null);
        $reports = ReportService::for($from, $to, $branchId);

        $data = match ($tab) {
            'overview' => ['overview' => $reports->overview(), 'financial' => $reports->financial()],
            'academic' => ['rows' => $reports->pathwayBatches('academic')],
            'training' => ['rows' => $reports->pathwayBatches('training')],
            'financial' => ['financial' => $reports->financial()],
            'branches' => ['branchRows' => $reports->branches(), 'faculty' => $reports->faculty()],
        };

        if ($request->query('export') === 'csv') {
            return $this->export($tab, $data, $from->format('Y-m-d').'_'.$to->format('Y-m-d'));
        }

        return view('admin.reports.index', $data + [
            'tab' => $tab,
            'from' => $from,
            'to' => $to,
            'branches' => Branch::orderBy('code')->get(),
            'branchId' => $branchId,
        ]);
    }

    private function export(string $tab, array $data, string $suffix): StreamedResponse
    {
        return match ($tab) {
            'academic', 'training' => Csv::download("{$tab}-report-{$suffix}.csv",
                ['Class', 'Course', 'Teacher', 'Students', 'Attendance %', 'Exam average %', 'Pass rate %', 'Homework submitted %', 'Certificates'],
                $data['rows']->map(fn ($r) => [$r['batch']->name, $r['batch']->course->title_en, $r['batch']->teacher?->name, $r['students'],
                    $r['attendance'] ?? '', $r['average'] ?? '', $r['pass_rate'] ?? '', $r['submission_rate'] ?? '', $r['certificates']])),
            'financial', 'overview' => Csv::download("financial-report-{$suffix}.csv", ['Month', 'Revenue (EGP)', 'Expenses (EGP)', 'Net (EGP)'],
                $data['financial']['monthly']->map(fn ($m) => [$m['label'], $m['revenue'], $m['expenses'], round($m['revenue'] - $m['expenses'], 2)])),
            'branches' => Csv::download("branches-report-{$suffix}.csv", ['Branch', 'Active students', 'Active classes', 'Revenue (EGP)', 'Expenses (EGP)', 'Net (EGP)', 'Outstanding (EGP)'],
                $data['branchRows']->map(fn ($b) => [$b['branch']->name_en, $b['students'], $b['batches'], $b['revenue'], $b['expenses'], $b['net'], $b['outstanding']])),
        };
    }
}
