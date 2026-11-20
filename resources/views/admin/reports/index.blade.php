@extends('layouts.portal')

@section('title', __('Reports & Analytics'))

@section('content')
    @php
        $tabs = [
            'overview' => ['layout-dashboard', __('Overview')],
            'academic' => ['graduation-cap', __('Academic')],
            'training' => ['monitor', __('Training')],
            'financial' => ['wallet', __('Financial')],
            'branches' => ['building-2', __('Branches & faculty')],
        ];
        $query = ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'branch' => $branchId];
        $chartOptions = [
            'maintainAspectRatio' => false,
            'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'boxWidth' => 8]]],
            'scales' => ['x' => ['grid' => ['display' => false]], 'y' => ['grid' => ['color' => '#EEF4F9'], 'border' => ['display' => false], 'beginAtZero' => true]],
        ];
    @endphp

    <x-page-header :title="__('Reports & Analytics')" :subtitle="__(':from to :to', ['from' => $from->translatedFormat('j M Y'), 'to' => $to->translatedFormat('j M Y')])">
        <a href="{{ route('admin.reports', $query + ['tab' => $tab, 'export' => 'csv']) }}" class="btn btn-outline"><x-lucide-file-spreadsheet class="size-4" />{{ __('Export CSV') }}</a>
        <button type="button" onclick="window.print()" class="btn btn-outline"><x-lucide-printer class="size-4" />{{ __('Print') }}</button>
    </x-page-header>

    <form method="GET" class="card mb-4 grid gap-3 p-4 sm:grid-cols-4 print:hidden">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <x-input name="from" :label="__('From')" type="date" :value="$from->toDateString()" />
        <x-input name="to" :label="__('To')" type="date" :value="$to->toDateString()" />
        @if (auth()->user()->branchScope())
            <div><p class="label">{{ __('Branch') }}</p><p class="input bg-canvas">{{ $branches->firstWhere('id', $branchId)?->tr('name') }}</p></div>
        @else
            <x-select name="branch" :label="__('Branch')" :value="$branchId" :options="$branches->mapWithKeys(fn ($b) => [$b->id => $b->tr('name')])" :placeholder="__('All branches')" />
        @endif
        <div class="flex items-end"><button class="btn btn-primary w-full"><x-lucide-filter class="size-4" />{{ __('Apply') }}</button></div>
    </form>

    <nav class="mb-6 flex flex-wrap gap-2 print:hidden" aria-label="{{ __('Reports') }}">
        @foreach ($tabs as $key => [$icon, $label])
            <a href="{{ route('admin.reports', $query + ['tab' => $key]) }}" @class(['btn btn-sm', 'btn-primary' => $tab === $key, 'btn-outline' => $tab !== $key])>
                <x-dynamic-component :component="'lucide-'.$icon" class="size-4" />{{ $label }}
            </a>
        @endforeach
    </nav>

    @if ($tab === 'overview')
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat :label="__('Active students')" :value="number_format($overview['active_students'])" icon="graduation-cap" :hint="__(':count new enrollments', ['count' => $overview['new_enrollments']])" />
            <x-stat :label="__('Attendance rate')" :value="pct($overview['attendance_rate'])" icon="clipboard-check" tone="emerald" />
            <x-stat :label="__('Exam average')" :value="pct($overview['exam_average'])" icon="chart-column" tone="violet" :hint="__('Pass rate :rate', ['rate' => pct($overview['pass_rate'])])" />
            <x-stat :label="__('Certificates issued')" :value="$overview['certificates']" icon="award" tone="amber" />
            <x-stat :label="__('Revenue collected')" :value="money($overview['revenue'], false)" icon="trending-up" tone="emerald" />
            <x-stat :label="__('Expenses')" :value="money($overview['expenses'], false)" icon="trending-down" tone="amber" />
            <x-stat :label="__('Net result')" :value="money($overview['net'], false)" icon="scale" :tone="$overview['net'] >= 0 ? 'brand' : 'rose'" />
            <x-stat :label="__('Applications')" :value="$overview['admissions']" icon="user-plus" :hint="__(':count accepted · :rate conversion', ['count' => $overview['admissions_accepted'], 'rate' => pct($overview['conversion'])])" />
        </div>

        <div class="card mt-6">
            <div class="card-header"><h2 class="card-title">{{ __('Revenue vs Expenses') }}</h2></div>
            <div class="h-80 p-5">
                <canvas role="img" aria-label="{{ __('Revenue vs Expenses') }}" data-chart="{{ json_encode([
                    'type' => 'bar',
                    'data' => ['labels' => $financial['monthly']->pluck('label'), 'datasets' => [
                        ['label' => __('Revenue'), 'data' => $financial['monthly']->pluck('revenue'), 'backgroundColor' => '#0874B9', 'borderRadius' => 6, 'maxBarThickness' => 26],
                        ['label' => __('Expenses'), 'data' => $financial['monthly']->pluck('expenses'), 'backgroundColor' => '#F4D600', 'borderRadius' => 6, 'maxBarThickness' => 26],
                    ]],
                    'options' => $chartOptions,
                ]) }}"></canvas>
            </div>
        </div>
    @endif

    @if (in_array($tab, ['academic', 'training'], true))
        @php
            $withAttendance = $rows->whereNotNull('attendance');
        @endphp
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat :label="__('Classes')" :value="$rows->count()" icon="users-round" />
            <x-stat :label="__('Students')" :value="$rows->sum('students')" icon="graduation-cap" tone="violet" />
            <x-stat :label="__('Average attendance')" :value="pct($withAttendance->isNotEmpty() ? round($withAttendance->avg('attendance'), 1) : null)" icon="clipboard-check" tone="emerald" />
            <x-stat :label="__('Certificates issued')" :value="$rows->sum('certificates')" icon="award" tone="amber" />
        </div>

        @if ($rows->isNotEmpty())
            <div class="card mt-6">
                <div class="card-header"><h2 class="card-title">{{ __('Attendance and exam average by class') }}</h2></div>
                <div class="h-80 p-5">
                    <canvas role="img" aria-label="{{ __('Attendance and exam average by class') }}" data-chart="{{ json_encode([
                        'type' => 'bar',
                        'data' => ['labels' => $rows->map(fn ($r) => $r['batch']->name)->values(), 'datasets' => [
                            ['label' => __('Attendance %'), 'data' => $rows->pluck('attendance')->values(), 'backgroundColor' => '#0874B9', 'borderRadius' => 6, 'maxBarThickness' => 22],
                            ['label' => __('Exam average %'), 'data' => $rows->pluck('average')->values(), 'backgroundColor' => '#7C3AED', 'borderRadius' => 6, 'maxBarThickness' => 22],
                        ]],
                        'options' => array_replace_recursive($chartOptions, ['scales' => ['y' => ['max' => 100]]]),
                    ]) }}"></canvas>
                </div>
            </div>
        @endif

        <div class="card mt-6">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('Class') }}</th><th>{{ __('Teacher') }}</th><th>{{ __('Students') }}</th><th>{{ __('Attendance') }}</th><th>{{ __('Exam average') }}</th><th>{{ __('Pass rate') }}</th><th>{{ __('Homework submitted') }}</th><th>{{ __('Certificates') }}</th></tr></thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td><a href="{{ route('admin.batches.show', $row['batch']) }}" class="font-semibold text-navy hover:text-brand">{{ $row['batch']->name }}</a><p class="text-xs text-muted">{{ $row['batch']->course->tr('title') }}</p></td>
                                <td class="text-muted">{{ $row['batch']->teacher?->name ?? '—' }}</td>
                                <td class="tabular-nums">{{ $row['students'] }}</td>
                                <td><span @class(['font-semibold tabular-nums', 'text-rose-600' => $row['attendance'] !== null && $row['attendance'] < 80])>{{ pct($row['attendance']) }}</span></td>
                                <td><span @class(['font-semibold tabular-nums', 'text-rose-600' => $row['average'] !== null && $row['average'] < 70])>{{ pct($row['average']) }}</span></td>
                                <td class="tabular-nums">{{ pct($row['pass_rate']) }}</td>
                                <td class="tabular-nums">{{ pct($row['submission_rate']) }}</td>
                                <td class="tabular-nums">{{ $row['certificates'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><x-empty icon="chart-column" :title="__('No classes in this period')" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($tab === 'financial')
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat :label="__('Revenue collected')" :value="money($financial['revenue'], false)" icon="trending-up" tone="emerald" />
            <x-stat :label="__('Expenses')" :value="money($financial['expenses'], false)" icon="trending-down" tone="amber" />
            <x-stat :label="__('Net result')" :value="money($financial['net'], false)" icon="scale" :tone="$financial['net'] >= 0 ? 'brand' : 'rose'" />
            <x-stat :label="__('Outstanding (all open invoices)')" :value="money($financial['outstanding'], false)" icon="circle-alert" tone="rose" />
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-3">
            <div class="card xl:col-span-2">
                <div class="card-header"><h2 class="card-title">{{ __('Monthly revenue and expenses') }}</h2></div>
                <div class="h-80 p-5">
                    <canvas role="img" aria-label="{{ __('Monthly revenue and expenses') }}" data-chart="{{ json_encode([
                        'type' => 'line',
                        'data' => ['labels' => $financial['monthly']->pluck('label'), 'datasets' => [
                            ['label' => __('Revenue'), 'data' => $financial['monthly']->pluck('revenue'), 'borderColor' => '#0874B9', 'backgroundColor' => 'rgba(8,116,185,.12)', 'fill' => true, 'tension' => 0.35],
                            ['label' => __('Expenses'), 'data' => $financial['monthly']->pluck('expenses'), 'borderColor' => '#E5C800', 'backgroundColor' => 'rgba(244,214,0,.12)', 'fill' => true, 'tension' => 0.35],
                        ]],
                        'options' => $chartOptions,
                    ]) }}"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Receivables aging') }}</h2></div>
                @php $agingTotal = max(1, array_sum($financial['aging'])); @endphp
                <ul class="space-y-3 p-5 text-sm">
                    @foreach ($financial['aging'] as $bucket => $amount)
                        <li>
                            <div class="flex justify-between"><span>{{ __('aging.'.$bucket) }}</span><span class="font-semibold tabular-nums">{{ money($amount, false) }}</span></div>
                            <div class="mt-1.5 h-1.5 rounded-full bg-line"><div @class(['h-1.5 rounded-full', 'bg-emerald-500' => $bucket === 'current', 'bg-amber-400' => in_array($bucket, ['1-30', '31-60'], true), 'bg-rose-500' => in_array($bucket, ['61-90', '90+'], true)]) style="width: {{ round($amount / $agingTotal * 100) }}%"></div></div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Income statement (ledger)') }}</h2></div>
                <div class="space-y-1.5 p-5 text-sm">
                    <div class="flex justify-between"><span class="text-muted">{{ __('Total revenue') }}</span><span class="tabular-nums">{{ money($financial['income']['total_revenue'], false) }}</span></div>
                    @foreach ($financial['income']['expenses'] as $row)
                        @if ($row['amount'] != 0)
                            <div class="flex justify-between"><span class="text-muted">− {{ $row['account']->tr('name') }}</span><span class="tabular-nums">{{ money($row['amount'], false) }}</span></div>
                        @endif
                    @endforeach
                    <div class="flex justify-between border-t border-line pt-2 font-bold"><span>{{ $financial['income']['net'] >= 0 ? __('Net profit') : __('Net loss') }}</span><span class="tabular-nums">{{ money(abs($financial['income']['net']), false) }}</span></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Collections by method') }}</h2></div>
                <ul class="space-y-2 p-5 text-sm">
                    @forelse ($financial['by_method'] as $method => $amount)
                        <li class="flex justify-between"><span>{{ __(\Illuminate\Support\Str::headline($method)) }}</span><span class="font-semibold tabular-nums">{{ money($amount, false) }}</span></li>
                    @empty
                        <li class="text-muted">{{ __('No payments in this period.') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Expenses by category') }}</h2></div>
                <ul class="space-y-2 p-5 text-sm">
                    @forelse ($financial['by_category'] as $category => $amount)
                        <li class="flex justify-between"><span>{{ __(ucfirst($category)) }}</span><span class="font-semibold tabular-nums">{{ money($amount, false) }}</span></li>
                    @empty
                        <li class="text-muted">{{ __('No expenses in this period.') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="card xl:col-span-3">
                <div class="card-header"><h2 class="card-title">{{ __('Largest outstanding balances') }}</h2></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>{{ __('Student') }}</th><th>{{ __('Outstanding') }}</th><th>{{ __('Overdue') }}</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($financial['debtors'] as $row)
                                <tr>
                                    <td><a href="{{ route('admin.students.show', $row['student']) }}" class="font-semibold text-navy hover:text-brand">{{ $row['student']->full_name }}</a><p class="font-latin text-xs text-muted" dir="ltr">{{ $row['student']->code }}</p></td>
                                    <td class="font-semibold tabular-nums">{{ money($row['balance'], false) }}</td>
                                    <td @class(['tabular-nums', 'text-rose-600 font-semibold' => $row['overdue'] > 0])>{{ money($row['overdue'], false) }}</td>
                                    <td class="text-end">
                                        @if ($wa = whatsapp_link($row['student']->phone, __('Hello :name, this is a friendly reminder from Talent Academy that :amount is outstanding on your account. Thank you.', ['name' => $row['student']->full_name, 'amount' => money($row['balance'])])))
                                            <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm text-emerald-700"><x-lucide-message-circle class="size-4" />{{ __('Remind') }}</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><x-empty icon="circle-check" :title="__('Nothing outstanding')" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if ($tab === 'branches')
        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Branches') }}</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('Branch') }}</th><th>{{ __('Active students') }}</th><th>{{ __('Active classes') }}</th><th>{{ __('Revenue') }}</th><th>{{ __('Expenses') }}</th><th>{{ __('Net result') }}</th><th>{{ __('Outstanding') }}</th></tr></thead>
                    <tbody>
                        @foreach ($branchRows as $row)
                            <tr>
                                <td class="font-semibold text-navy">{{ $row['branch']->tr('name') }} <x-status :value="$row['branch']->status" /></td>
                                <td class="tabular-nums">{{ $row['students'] }}</td>
                                <td class="tabular-nums">{{ $row['batches'] }}</td>
                                <td class="tabular-nums">{{ money($row['revenue'], false) }}</td>
                                <td class="tabular-nums">{{ money($row['expenses'], false) }}</td>
                                <td @class(['font-semibold tabular-nums', 'text-rose-600' => $row['net'] < 0])>{{ money($row['net'], false) }}</td>
                                <td class="tabular-nums">{{ money($row['outstanding'], false) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-6">
            <div class="card-header"><h2 class="card-title">{{ __('Faculty performance') }}</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('Teacher / trainer') }}</th><th>{{ __('Active classes') }}</th><th>{{ __('Students') }}</th><th>{{ __('Attendance') }}</th><th>{{ __('Exam average') }}</th><th>{{ __('Waiting to be graded') }}</th></tr></thead>
                    <tbody>
                        @forelse ($faculty as $row)
                            <tr>
                                <td><a href="{{ route('admin.teachers.show', $row['teacher']) }}" class="font-semibold text-navy hover:text-brand">{{ $row['teacher']->name }}</a><p class="text-xs text-muted">{{ $row['teacher']->teacherProfile?->tr('title') }}</p></td>
                                <td class="tabular-nums">{{ $row['batches'] }}</td>
                                <td class="tabular-nums">{{ $row['students'] }}</td>
                                <td class="tabular-nums">{{ pct($row['attendance']) }}</td>
                                <td class="tabular-nums">{{ pct($row['average']) }}</td>
                                <td @class(['tabular-nums', 'font-semibold text-amber-600' => $row['to_grade'] > 0])>{{ $row['to_grade'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-empty icon="presentation" :title="__('No teaching activity in this period')" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
