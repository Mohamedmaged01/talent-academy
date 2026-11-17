@extends('layouts.portal')

@section('title', __('Attendance'))

@section('content')
    <x-page-header :title="__('My Attendance')" :subtitle="__('Overall attendance: :rate', ['rate' => pct($rate)])" />

    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        <x-stat :label="__('Present')" :value="$counts['present'] ?? 0" icon="circle-check" tone="emerald" />
        <x-stat :label="__('Late')" :value="$counts['late'] ?? 0" icon="clock" />
        <x-stat :label="__('Absent')" :value="$counts['absent'] ?? 0" icon="circle-x" tone="rose" />
        <x-stat :label="__('Excused')" :value="$counts['excused'] ?? 0" icon="file-text" tone="amber" />
    </div>

    @if ($rate !== null && $rate < 80)
        <div class="mb-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <x-lucide-triangle-alert class="mt-0.5 size-4 shrink-0" />{{ __('Your attendance is below 80%. You need at least 80% to receive a certificate.') }}
        </div>
    @endif

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Course') }}</th><th>{{ __('Status') }}</th><th>{{ __('Note') }}</th></tr></thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td>{{ $record->date->translatedFormat('l j M Y') }}</td>
                            <td class="text-muted">{{ \Illuminate\Support\Str::limit($record->batch->course->tr('title'), 45) }}</td>
                            <td><x-status :value="$record->status" /></td>
                            <td class="text-xs text-muted">{{ $record->note }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-empty icon="clipboard-check" :title="__('No attendance recorded yet')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
