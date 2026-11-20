@extends('layouts.portal')

@section('title', __('Reports'))

@section('content')
    <x-page-header :title="__('Class reports')" :subtitle="__('Attendance, exam results and homework for each of your classes.')" />

    <div class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($summaries as $summary)
            <a href="{{ route('teacher.reports', ['batch' => $summary['batch']->id]) }}" @class(['card block p-5 transition hover:-translate-y-0.5 hover:shadow-md', 'ring-2 ring-brand' => $batch?->id === $summary['batch']->id])>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-bold text-navy">{{ $summary['batch']->name }}</p>
                        <p class="truncate text-xs text-muted">{{ $summary['batch']->course->tr('title') }}</p>
                    </div>
                    <x-status :value="$summary['batch']->status" />
                </div>
                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-xs text-muted">{{ __('Attendance') }}</dt><dd class="font-bold tabular-nums">{{ pct($summary['attendance']) }}</dd></div>
                    <div><dt class="text-xs text-muted">{{ __('Exam average') }}</dt><dd class="font-bold tabular-nums">{{ pct($summary['average']) }}</dd></div>
                    <div><dt class="text-xs text-muted">{{ __('Homework submitted') }}</dt><dd class="font-bold tabular-nums">{{ pct($summary['submission_rate']) }}</dd></div>
                    <div><dt class="text-xs text-muted">{{ __('At risk') }}</dt><dd @class(['font-bold tabular-nums', 'text-rose-600' => $summary['at_risk'] > 0])>{{ $summary['at_risk'] }} / {{ $summary['students'] }}</dd></div>
                </dl>
            </a>
        @empty
            <div class="card md:col-span-2 xl:col-span-3"><x-empty icon="chart-column" :title="__('No classes yet')" /></div>
        @endforelse
    </div>

    @if ($batch)
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">{{ $batch->name }}</h2>
                <a href="{{ route('teacher.reports.export', $batch) }}" class="btn btn-outline btn-sm"><x-lucide-file-spreadsheet class="size-4" />{{ __('Export CSV') }}</a>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('Student') }}</th><th>{{ __('Attendance') }}</th><th>{{ __('Exam average') }}</th><th>{{ __('Assignments') }}</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td><a href="{{ route('teacher.students.show', $row['student']) }}" class="font-semibold text-navy hover:text-brand">{{ $row['student']->full_name }}</a></td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 w-20 rounded-full bg-line"><div @class(['h-1.5 rounded-full', 'bg-emerald-500' => ($row['attendance'] ?? 0) >= 80, 'bg-rose-500' => ($row['attendance'] ?? 0) < 80]) style="width: {{ (int) ($row['attendance'] ?? 0) }}%"></div></div>
                                        <span class="text-xs tabular-nums">{{ pct($row['attendance']) }}</span>
                                    </div>
                                </td>
                                <td><span @class(['font-semibold tabular-nums', 'text-rose-600' => $row['average'] !== null && $row['average'] < 70])>{{ pct($row['average']) }}</span></td>
                                <td class="tabular-nums">{{ $row['submitted'] }} / {{ $row['assignments'] }}</td>
                                <td>@if ($row['at_risk'])<span class="badge bg-rose-50 text-rose-700"><x-lucide-triangle-alert class="size-3" />{{ __('At risk') }}</span>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-empty icon="users" :title="__('No students enrolled yet')" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
