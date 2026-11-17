@extends('layouts.portal')

@section('title', $student->full_name)

@section('content')
    <x-page-header :title="$student->full_name" :subtitle="$student->code.' · '.$student->branch?->tr('name')" :back="route('admin.students.index')">
        <a href="{{ route('admin.invoices.create', ['student' => $student->id]) }}" class="btn btn-outline"><x-lucide-file-plus class="size-4" />{{ __('New Invoice') }}</a>
        <a href="{{ route('admin.certificates.create', ['student' => $student->id]) }}" class="btn btn-outline"><x-lucide-award class="size-4" />{{ __('Issue Certificate') }}</a>
        <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-primary"><x-lucide-pencil class="size-4" />{{ __('Edit') }}</a>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat :label="__('Attendance')" :value="pct($attendanceRate)" icon="clipboard-check" />
        <x-stat :label="__('Exam average')" :value="pct($average)" icon="chart-column" tone="violet" />
        <x-stat :label="__('Outstanding balance')" :value="money($balance, false)" icon="wallet" :tone="$balance > 0 ? 'rose' : 'emerald'" />
        <x-stat :label="__('Certificates')" :value="$student->certificates->where('status', 'valid')->count()" icon="award" tone="amber" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Profile') }}</h2><x-status :value="$student->status" /></div>
                <dl class="divide-y divide-line text-sm">
                    @foreach ([
                        __('Pathway') => __(ucfirst($student->pathway)),
                        __('Grade / Level') => $student->grade_level,
                        __('Name (English)') => $student->full_name_en,
                        __('Phone') => $student->phone,
                        __('Email') => $student->email,
                        __('National ID') => $student->national_id,
                        __('Date of birth') => $student->birth_date?->translatedFormat('j F Y'),
                        __('School / university') => $student->school_name,
                        __('Enrollment date') => $student->enrolled_at?->translatedFormat('j F Y'),
                        __('Portal login') => $student->user?->email ?? __('No account'),
                    ] as $label => $value)
                        <div class="flex justify-between gap-3 px-5 py-2.5"><dt class="text-muted">{{ $label }}</dt><dd class="text-end font-medium">{{ $value ?: '—' }}</dd></div>
                    @endforeach
                </dl>
                @if ($student->notes)
                    <p class="border-t border-line px-5 py-3 text-sm whitespace-pre-line text-muted">{{ $student->notes }}</p>
                @endif
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Parents / guardians') }}</h2></div>
                <ul class="divide-y divide-line">
                    @forelse ($student->guardians as $guardian)
                        <li class="flex items-center justify-between px-5 py-3 text-sm">
                            <span><span class="font-semibold text-navy">{{ $guardian->name }}</span> <span class="text-muted">({{ __(ucfirst($guardian->pivot->relationship)) }})</span></span>
                            <span class="text-muted" dir="ltr">{{ $guardian->phone }}</span>
                        </li>
                    @empty
                        <li class="px-5 py-4 text-sm text-muted">{{ __('No parent account linked.') }} <a href="{{ route('admin.guardians.create') }}" class="font-semibold text-brand">{{ __('Add parent') }}</a></li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="space-y-6 xl:col-span-2">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Classes') }}</h2></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>{{ __('Class') }}</th><th>{{ __('Instructor') }}</th><th>{{ __('Attendance') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($student->batches as $batch)
                                <tr>
                                    <td><a href="{{ route('admin.batches.show', $batch) }}" class="font-semibold text-navy hover:text-brand">{{ $batch->name }}</a><p class="text-xs text-muted">{{ $batch->course->tr('title') }}</p></td>
                                    <td>{{ $batch->teacher?->name ?? '—' }}</td>
                                    <td class="tabular-nums">{{ pct($student->attendanceRate($batch->id)) }}</td>
                                    <td><x-status :value="$batch->pivot->status" /></td>
                                    <td class="text-end">
                                        @if ($batch->pivot->status === 'active')
                                            <x-confirm-delete :action="route('admin.batches.unenroll', [$batch, $student])" icon="user-minus" :message="__('Remove this student from the class?')" />
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">{{ __('Not enrolled in any class yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($availableBatches->isNotEmpty())
                    <form method="POST" x-data="{ batch: '' }" :action="batch ? '{{ url('admin/batches') }}/' + batch + '/enroll' : '#'" class="flex flex-col gap-2 border-t border-line p-4 sm:flex-row">
                        @csrf
                        <input type="hidden" name="student_ids[]" value="{{ $student->id }}">
                        <select x-model="batch" class="input" required>
                            <option value="">{{ __('Enroll in a class…') }}</option>
                            @foreach ($availableBatches as $batch)
                                <option value="{{ $batch->id }}">{{ $batch->name }} — {{ \Illuminate\Support\Str::limit($batch->course->tr('title'), 40) }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-primary" :disabled="!batch"><x-lucide-plus class="size-4" />{{ __('Enroll') }}</button>
                    </form>
                @endif
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Invoices') }}</h2></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>{{ __('Number') }}</th><th>{{ __('Description') }}</th><th>{{ __('Due') }}</th><th>{{ __('Total') }}</th><th>{{ __('Balance') }}</th><th>{{ __('Status') }}</th></tr></thead>
                        <tbody>
                            @forelse ($student->invoices as $invoice)
                                <tr>
                                    <td><a href="{{ route('admin.invoices.show', $invoice) }}" class="font-latin text-xs font-semibold text-brand" dir="ltr">{{ $invoice->number }}</a></td>
                                    <td class="max-w-56 truncate">{{ $invoice->description }}</td>
                                    <td class="text-muted">{{ $invoice->due_date->translatedFormat('j M Y') }}</td>
                                    <td class="tabular-nums">{{ money($invoice->total(), false) }}</td>
                                    <td class="font-semibold tabular-nums">{{ money($invoice->balance(), false) }}</td>
                                    <td><x-status :value="$invoice->status" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">{{ __('No invoices.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="card">
                    <div class="card-header"><h2 class="card-title">{{ __('Exam results') }}</h2></div>
                    <ul class="divide-y divide-line text-sm">
                        @forelse ($student->examResults->take(8) as $result)
                            <li class="flex items-center justify-between gap-3 px-5 py-2.5">
                                <span class="min-w-0"><span class="block truncate font-medium">{{ $result->exam->title }}</span><span class="text-xs text-muted">{{ $result->exam->batch->name }}</span></span>
                                <span @class(['font-bold tabular-nums', 'text-emerald-600' => $result->passed(), 'text-rose-600' => ! $result->passed()])>{{ (float) $result->score }}/{{ $result->exam->max_score }}</span>
                            </li>
                        @empty
                            <li class="px-5 py-4 text-muted">{{ __('No results yet.') }}</li>
                        @endforelse
                    </ul>
                </div>
                <div class="card">
                    <div class="card-header"><h2 class="card-title">{{ __('Recent attendance') }}</h2></div>
                    <ul class="divide-y divide-line text-sm">
                        @forelse ($student->attendance->take(8) as $record)
                            <li class="flex items-center justify-between gap-3 px-5 py-2.5">
                                <span><span class="block font-medium">{{ $record->date->translatedFormat('l j M') }}</span><span class="text-xs text-muted">{{ $record->batch->name }}</span></span>
                                <x-status :value="$record->status" />
                            </li>
                        @empty
                            <li class="px-5 py-4 text-muted">{{ __('No attendance recorded.') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-8 flex justify-end">
        <x-confirm-delete :action="route('admin.students.destroy', $student)" :label="__('Delete student')" :message="__('Students with invoices are marked withdrawn instead of deleted. Continue?')" />
    </div>
@endsection
