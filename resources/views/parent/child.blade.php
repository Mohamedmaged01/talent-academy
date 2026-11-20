@extends('layouts.portal')

@section('title', $student->displayName())

@section('content')
    <x-page-header :title="$student->displayName()" :subtitle="$student->grade_level.' · '.$student->branch->tr('name')" :back="route('parent.dashboard')" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat :label="__('Attendance')" :value="pct($attendance)" icon="clipboard-check" :tone="$attendance !== null && $attendance < 80 ? 'rose' : 'emerald'" />
        <x-stat :label="__('Exam average')" :value="pct($average)" icon="chart-column" tone="violet" />
        <x-stat :label="__('Balance due')" :value="money($balance, false)" icon="wallet" :tone="$balance > 0 ? 'rose' : 'emerald'" />
        <x-stat :label="__('Certificates')" :value="$certificates->count()" icon="award" tone="amber" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Courses & teachers') }}</h2></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>{{ __('Course') }}</th><th>{{ __('Teacher') }}</th><th>{{ __('Schedule') }}</th><th>{{ __('Attendance') }}</th></tr></thead>
                        <tbody>
                            @forelse ($student->batches as $batch)
                                <tr>
                                    <td class="font-semibold text-navy">{{ $batch->course->tr('title') }}</td>
                                    <td>{{ $batch->teacher?->name ?? '—' }}<p class="text-xs text-muted" dir="ltr">{{ $batch->teacher?->phone }}</p></td>
                                    <td class="text-xs text-muted">{{ $batch->scheduleSummary() }}</td>
                                    <td class="tabular-nums">{{ pct($student->attendanceRate($batch->id)) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">{{ __('Not enrolled in any class yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="card">
                    <div class="card-header"><h2 class="card-title">{{ __('Exam results') }}</h2></div>
                    <ul class="space-y-4 p-5">
                        @forelse ($results as $result)
                            <li>
                                <div class="flex justify-between gap-3 text-sm"><span class="truncate font-medium">{{ $result->exam->title }}</span><span class="font-bold tabular-nums">{{ (float) $result->score }}/{{ $result->exam->max_score }}</span></div>
                                <div class="mt-1.5 h-2 rounded-full bg-line"><div @class(['h-2 rounded-full', 'bg-emerald-500' => $result->passed(), 'bg-rose-500' => ! $result->passed()]) style="width: {{ $result->percent() }}%"></div></div>
                            </li>
                        @empty
                            <li class="text-sm text-muted">{{ __('No published results yet.') }}</li>
                        @endforelse
                    </ul>
                </div>
                <div class="card">
                    <div class="card-header"><h2 class="card-title">{{ __('Recent attendance') }}</h2></div>
                    <ul class="divide-y divide-line text-sm">
                        @forelse ($recentAttendance as $record)
                            <li class="flex items-center justify-between gap-3 px-5 py-2.5">
                                <span><span class="block">{{ $record->date->translatedFormat('l j M') }}</span><span class="text-xs text-muted">{{ $record->batch->name }}</span></span>
                                <x-status :value="$record->status" />
                            </li>
                        @empty
                            <li class="px-5 py-4 text-muted">{{ __('No attendance recorded yet') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            @php($sharedNotes = $student->fileNotes()->where('visible_to_parent', true)->with('author')->latest()->take(10)->get())
            @if ($sharedNotes->isNotEmpty())
                <div class="card">
                    <div class="card-header"><h2 class="card-title">{{ __('Notes from teachers') }}</h2></div>
                    <ul class="divide-y divide-line">
                        @foreach ($sharedNotes as $note)
                            <li class="px-5 py-3 text-sm">
                                <p class="text-xs text-muted"><span class="font-semibold text-navy">{{ $note->author?->name }}</span> · {{ $note->created_at->translatedFormat('j M Y') }}</p>
                                <p class="mt-1 leading-7 whitespace-pre-line" dir="auto">{{ $note->body }}</p>
                                @if ($note->author && $note->author->role === 'teacher')
                                    <a href="{{ route('messages.create', ['to' => $note->author_id]) }}" class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-brand hover:underline"><x-lucide-mail class="size-3.5" />{{ __('Reply to the teacher') }}</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Fees') }}</h2></div>
                <ul class="divide-y divide-line">
                    @forelse ($invoices as $invoice)
                        <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                            <span class="min-w-0"><span class="block truncate font-medium">{{ $invoice->description }}</span><span class="text-xs text-muted">{{ __('Due') }} {{ $invoice->due_date->translatedFormat('j M Y') }}</span></span>
                            <span class="text-end"><span class="block font-semibold tabular-nums">{{ money($invoice->balance(), false) }}</span><x-status :value="$invoice->status" /></span>
                        </li>
                    @empty
                        <li class="px-5 py-4 text-sm text-muted">{{ __('No invoices') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Weekly schedule') }}</h2></div>
                <ul class="divide-y divide-line text-sm">
                    @forelse ($slots as $slot)
                        <li class="px-5 py-2.5">
                            <div class="flex justify-between"><span class="font-medium">{{ $slot->dayName() }}</span><span class="font-latin text-muted" dir="ltr">{{ $slot->timeRange() }}</span></div>
                            <p class="truncate text-xs text-muted">{{ $slot->batch->course->tr('title') }}</p>
                        </li>
                    @empty
                        <li class="px-5 py-4 text-muted">{{ __('No sessions') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection
