@extends('layouts.portal')

@section('title', __('Attendance Reports'))

@section('content')
    <x-page-header :title="__('Attendance Reports')" :subtitle="__(':from to :to', ['from' => $from->translatedFormat('j M Y'), 'to' => $to->translatedFormat('j M Y')])" />

    <form method="GET" class="card mb-6 grid gap-3 p-4 sm:grid-cols-4">
        <x-input name="from" :label="__('From')" type="date" :value="$from->toDateString()" />
        <x-input name="to" :label="__('To')" type="date" :value="$to->toDateString()" />
        <x-select name="batch" :label="__('Class')" :value="request('batch')" :options="$batches->pluck('name', 'id')" :placeholder="__('All classes')" />
        <div class="flex items-end"><button class="btn btn-primary w-full"><x-lucide-filter class="size-4" />{{ __('Apply') }}</button></div>
    </form>

    @php $total = max(1, array_sum($totals)); @endphp
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat :label="__('Present')" :value="$totals['present']" icon="circle-check" tone="emerald" :hint="pct($totals['present'] / $total * 100)" />
        <x-stat :label="__('Late')" :value="$totals['late']" icon="clock" :hint="pct($totals['late'] / $total * 100)" />
        <x-stat :label="__('Absent')" :value="$totals['absent']" icon="circle-x" tone="rose" :hint="pct($totals['absent'] / $total * 100)" />
        <x-stat :label="__('Excused')" :value="$totals['excused']" icon="file-text" tone="amber" :hint="pct($totals['excused'] / $total * 100)" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">{{ __('By class (lowest attendance first)') }}</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('Class') }}</th><th>{{ __('Present') }}</th><th>{{ __('Late') }}</th><th>{{ __('Absent') }}</th><th>{{ __('Excused') }}</th><th>{{ __('Rate') }}</th></tr></thead>
                    <tbody>
                        @forelse ($byBatch as $row)
                            <tr>
                                <td><a href="{{ route('admin.batches.show', $row['batch']) }}" class="font-semibold text-navy hover:text-brand">{{ $row['batch']->name }}</a></td>
                                <td class="tabular-nums">{{ $row['present'] }}</td>
                                <td class="tabular-nums">{{ $row['late'] }}</td>
                                <td class="tabular-nums text-rose-600">{{ $row['absent'] }}</td>
                                <td class="tabular-nums">{{ $row['excused'] }}</td>
                                <td class="w-40">
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 flex-1 rounded-full bg-line"><div @class(['h-1.5 rounded-full', 'bg-emerald-500' => $row['rate'] >= 80, 'bg-amber-400' => $row['rate'] < 80 && $row['rate'] >= 60, 'bg-rose-500' => $row['rate'] < 60]) style="width: {{ $row['rate'] }}%"></div></div>
                                        <span class="text-xs font-semibold tabular-nums">{{ pct($row['rate']) }}</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-empty icon="clipboard-check" :title="__('No attendance recorded in this period')" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Most absences — follow up') }}</h2></div>
            <ul class="divide-y divide-line">
                @forelse ($absentees as $row)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span class="grid size-9 place-items-center rounded-full bg-rose-50 text-sm font-bold text-rose-600">{{ $row['count'] }}</span>
                        <span class="min-w-0 flex-1">
                            <a href="{{ route('admin.students.show', $row['student']) }}" class="block truncate text-sm font-semibold text-navy hover:text-brand">{{ $row['student']->full_name }}</a>
                            <span class="block truncate text-xs text-muted">{{ $row['batch']->name }}</span>
                        </span>
                        @if ($row['student']->phone)
                            <a href="tel:{{ $row['student']->phone }}" class="btn btn-ghost btn-sm" title="{{ __('Call') }}"><x-lucide-phone class="size-4" /></a>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-sm text-muted">{{ __('No absences in this period.') }}</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
