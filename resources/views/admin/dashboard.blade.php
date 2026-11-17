@extends('layouts.portal')

@section('title', __('Dashboard'))

@section('content')
    @php
        // Chart configs are built here and printed with json_encode(); Blade's @json
        // directive splits its argument on commas and cannot take a multi-line array.
        $revenueChart = [
            'type' => 'bar',
            'data' => [
                'labels' => $financeChart['labels'],
                'datasets' => [
                    ['label' => __('Revenue'), 'data' => $financeChart['revenue'], 'backgroundColor' => '#0874B9', 'borderRadius' => 6, 'maxBarThickness' => 26],
                    ['label' => __('Expenses'), 'data' => $financeChart['expenses'], 'backgroundColor' => '#F4D600', 'borderRadius' => 6, 'maxBarThickness' => 26],
                ],
            ],
            'options' => [
                'maintainAspectRatio' => false,
                'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'boxWidth' => 8]]],
                'scales' => ['x' => ['grid' => ['display' => false]], 'y' => ['grid' => ['color' => '#EEF4F9'], 'border' => ['display' => false]]],
            ],
        ];
        $attendanceChart = [
            'type' => 'doughnut',
            'data' => [
                'labels' => [__('Present'), __('Late'), __('Absent'), __('Excused')],
                'datasets' => [[
                    'data' => [$attendance['present'] ?? 0, $attendance['late'] ?? 0, $attendance['absent'] ?? 0, $attendance['excused'] ?? 0],
                    'backgroundColor' => ['#0874B9', '#38BDF8', '#F43F5E', '#F4D600'],
                    'borderWidth' => 0,
                ]],
            ],
            'options' => ['cutout' => '74%', 'plugins' => ['legend' => ['display' => false]]],
        ];
    @endphp

    <x-page-header :title="__('Admin Dashboard')" :subtitle="__('Welcome back, :name. Here is what is happening across the academy today.', ['name' => auth()->user()->name])" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-stat :label="__('Active Students')" :value="number_format($stats['students'])" icon="graduation-cap" :hint="__('+:count this month', ['count' => $stats['students_new']])" :href="route('admin.students.index')" />
        <x-stat :label="__('Teachers & Trainers')" :value="$stats['teachers']" icon="presentation" tone="emerald" :hint="__(':count active classes', ['count' => $stats['batches']])" :href="route('admin.teachers.index')" />
        <x-stat :label="__('New Applications')" :value="$stats['admissions_pending']" icon="user-plus" tone="violet" :hint="__('Waiting for review')" :href="route('admin.admissions.index', ['status' => 'pending'])" />
        <x-stat :label="__('Collected This Month')" :value="money($stats['collected_month'], false)" icon="wallet" tone="amber" :href="route('admin.finance')" />
        <x-stat :label="__('Outstanding Fees')" :value="money($stats['outstanding'], false)" icon="circle-alert" tone="rose" :href="route('admin.invoices.index', ['status' => 'overdue'])" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-12">
        <div class="space-y-6 xl:col-span-9">
            {{-- Quick actions --}}
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Quick Actions') }}</h2></div>
                <div class="grid grid-cols-3 gap-3 p-5 sm:grid-cols-6">
                    @foreach ([
                        ['user-plus', __('Add Student'), route('admin.students.create'), 'text-brand bg-brand-50'],
                        ['presentation', __('Add Teacher'), route('admin.teachers.create'), 'text-emerald-600 bg-emerald-50'],
                        ['users-round', __('Create Class'), route('admin.batches.create'), 'text-violet-600 bg-violet-50'],
                        ['file-plus', __('New Invoice'), route('admin.invoices.create'), 'text-amber-600 bg-amber-50'],
                        ['award', __('Issue Certificate'), route('admin.certificates.create'), 'text-sky-600 bg-sky-50'],
                        ['megaphone', __('Announce'), route('admin.announcements.index'), 'text-rose-600 bg-rose-50'],
                    ] as [$icon, $label, $href, $tone])
                        <a href="{{ $href }}" class="flex flex-col items-center gap-2 rounded-xl border border-line p-3 text-center transition hover:border-brand hover:shadow-sm">
                            <span class="grid size-10 place-items-center rounded-xl {{ $tone }}"><x-dynamic-component :component="'lucide-'.$icon" class="size-5" /></span>
                            <span class="text-xs font-semibold text-navy">{{ $label }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-5">
                <div class="card lg:col-span-3">
                    <div class="card-header">
                        <h2 class="card-title">{{ __('Revenue vs Expenses') }}</h2>
                        <a href="{{ route('admin.finance') }}" class="text-xs font-semibold text-brand">{{ __('Details') }}</a>
                    </div>
                    <div class="h-72 p-5">
                        <canvas aria-label="{{ __('Revenue vs Expenses') }}" role="img" data-chart="{{ json_encode($revenueChart) }}"></canvas>
                    </div>
                </div>

                <div class="card lg:col-span-2">
                    <div class="card-header">
                        <h2 class="card-title">{{ __('Attendance (30 days)') }}</h2>
                        <a href="{{ route('admin.attendance') }}" class="text-xs font-semibold text-brand">{{ __('Report') }}</a>
                    </div>
                    <div class="flex items-center gap-5 p-5">
                        <div class="relative size-36 shrink-0">
                            <canvas aria-label="{{ __('Attendance') }}" role="img" data-chart="{{ json_encode($attendanceChart) }}"></canvas>
                            <div class="pointer-events-none absolute inset-0 grid place-items-center text-center">
                                <div><p class="text-2xl font-extrabold text-navy">{{ $attendanceRate }}%</p><p class="text-[11px] text-muted">{{ __('Attended') }}</p></div>
                            </div>
                        </div>
                        <ul class="flex-1 space-y-2.5 text-sm">
                            @foreach (['present' => ['#0874B9', __('Present')], 'late' => ['#38BDF8', __('Late')], 'absent' => ['#F43F5E', __('Absent')], 'excused' => ['#F4D600', __('Excused')]] as $key => [$color, $label])
                                <li class="flex items-center justify-between gap-2">
                                    <span class="flex items-center gap-2"><span class="size-2.5 rounded-full" style="background: {{ $color }}"></span>{{ $label }}</span>
                                    <span class="font-semibold tabular-nums">{{ $attendance[$key] ?? 0 }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="grid grid-cols-2 border-t border-line text-center text-sm">
                        <div class="p-3"><p class="text-muted">{{ __('Academic') }}</p><p class="font-bold text-navy">{{ $pathways['academic'] ?? 0 }}</p></div>
                        <div class="border-s border-line p-3"><p class="text-muted">{{ __('Training') }}</p><p class="font-bold text-navy">{{ $pathways['training'] ?? 0 }}</p></div>
                    </div>
                </div>
            </div>

            {{-- Active classes --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">{{ __('Active Classes') }}</h2>
                    <a href="{{ route('admin.batches.index') }}" class="text-xs font-semibold text-brand">{{ __('View all') }}</a>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>{{ __('Class') }}</th><th>{{ __('Program') }}</th><th>{{ __('Instructor') }}</th><th>{{ __('Seats') }}</th></tr></thead>
                        <tbody>
                            @forelse ($batches as $batch)
                                <tr>
                                    <td><a href="{{ route('admin.batches.show', $batch) }}" class="font-semibold text-navy hover:text-brand">{{ $batch->name }}</a></td>
                                    <td class="text-muted">{{ \Illuminate\Support\Str::limit($batch->course->tr('title'), 40) }}</td>
                                    <td>{{ $batch->teacher?->name ?? '—' }}</td>
                                    <td class="w-40">
                                        @php $fill = $batch->capacity ? min(100, round($batch->enrollments_count / $batch->capacity * 100)) : 0; @endphp
                                        <div class="flex items-center gap-2">
                                            <div class="h-1.5 flex-1 rounded-full bg-line"><div class="h-1.5 rounded-full bg-brand" style="width: {{ $fill }}%"></div></div>
                                            <span class="text-xs tabular-nums text-muted">{{ $batch->enrollments_count }}/{{ $batch->capacity }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><x-empty icon="users-round" :title="__('No active classes')" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">{{ __('Latest Applications') }}</h2>
                        <a href="{{ route('admin.admissions.index') }}" class="text-xs font-semibold text-brand">{{ __('View all') }}</a>
                    </div>
                    <ul class="divide-y divide-line">
                        @forelse ($recentAdmissions as $admission)
                            <li>
                                <a href="{{ route('admin.admissions.show', $admission) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-canvas">
                                    <span class="grid size-9 place-items-center rounded-full bg-violet-50 text-sm font-bold text-violet-600">{{ mb_substr($admission->full_name, 0, 1) }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-navy">{{ $admission->full_name }}</span>
                                        <span class="block truncate text-xs text-muted">{{ $admission->course?->tr('title') ?? __(ucfirst($admission->pathway)) }}</span>
                                    </span>
                                    <x-status :value="$admission->status" />
                                </a>
                            </li>
                        @empty
                            <li><x-empty icon="user-plus" :title="__('No applications yet')" /></li>
                        @endforelse
                    </ul>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">{{ __('Overdue Fees') }}</h2>
                        <a href="{{ route('admin.invoices.index', ['status' => 'overdue']) }}" class="text-xs font-semibold text-brand">{{ __('View all') }}</a>
                    </div>
                    <ul class="divide-y divide-line">
                        @forelse ($overdueInvoices as $invoice)
                            <li>
                                <a href="{{ route('admin.invoices.show', $invoice) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-canvas">
                                    <span class="grid size-9 place-items-center rounded-full bg-rose-50 text-rose-600"><x-lucide-clock-alert class="size-4" /></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-navy">{{ $invoice->student->full_name }}</span>
                                        <span class="block text-xs text-muted">{{ __('Due') }} {{ $invoice->due_date->translatedFormat('j M') }} · {{ $invoice->number }}</span>
                                    </span>
                                    <span class="text-sm font-bold text-rose-600 tabular-nums">{{ money($invoice->balance(), false) }}</span>
                                </a>
                            </li>
                        @empty
                            <li><x-empty icon="circle-check" :title="__('No overdue fees')" /></li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="space-y-6 xl:col-span-3">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">{{ __("Today's Schedule") }}</h2>
                    <a href="{{ route('admin.timetable') }}" class="text-xs font-semibold text-brand">{{ __('Timetable') }}</a>
                </div>
                <ol class="space-y-3 p-5">
                    @forelse ($todaySchedule as $slot)
                        <li class="flex gap-3 rounded-xl border-s-4 border-brand bg-canvas p-3">
                            <span class="w-14 shrink-0 font-latin text-xs font-semibold text-muted" dir="ltr">{{ \Illuminate\Support\Str::substr($slot->starts_at, 0, 5) }}</span>
                            <span class="min-w-0">
                                <a href="{{ route('admin.batches.show', $slot->batch) }}" class="block truncate text-sm font-semibold text-navy hover:text-brand">{{ $slot->batch->name }}</a>
                                <span class="block truncate text-xs text-muted">{{ $slot->batch->teacher?->name }} · {{ $slot->batch->room }}</span>
                            </span>
                        </li>
                    @empty
                        <li class="text-center text-sm text-muted">{{ __('No classes scheduled today.') }}</li>
                    @endforelse
                </ol>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">{{ __('Recent Activity') }}</h2>
                    <a href="{{ route('admin.activity') }}" class="text-xs font-semibold text-brand">{{ __('View all') }}</a>
                </div>
                <ol class="space-y-4 p-5">
                    @forelse ($activity as $log)
                        <li class="flex gap-3">
                            <span class="mt-1.5 size-2 shrink-0 rounded-full bg-brand"></span>
                            <span class="min-w-0">
                                <span class="block text-sm leading-6 text-ink" dir="auto">{{ $log->description }}</span>
                                <span class="block text-xs text-muted">{{ $log->user?->name ?? __('System') }} · {{ $log->created_at?->diffForHumans() }}</span>
                            </span>
                        </li>
                    @empty
                        <li class="text-sm text-muted">{{ __('No activity yet.') }}</li>
                    @endforelse
                </ol>
            </div>

            @if ($stats['inquiries_new'])
                <a href="{{ route('admin.inquiries.index', ['status' => 'new']) }}" class="card flex items-center gap-3 border-gold bg-gold/10 p-4">
                    <x-lucide-inbox class="size-6 text-navy" />
                    <span class="text-sm font-semibold text-navy">{{ trans_choice(':count new contact message|:count new contact messages', $stats['inquiries_new']) }}</span>
                </a>
            @endif
        </div>
    </div>
@endsection
