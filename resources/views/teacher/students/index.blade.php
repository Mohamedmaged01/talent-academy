@extends('layouts.portal')

@section('title', __('My Students'))

@section('content')
    <x-page-header :title="__('My Students')" :subtitle="__('Everyone enrolled in your classes, with their progress.')" />

    <div class="card">
        <form method="GET" class="grid gap-3 border-b border-line p-4 sm:grid-cols-3">
            <input type="search" name="q" value="{{ request('q') }}" class="input sm:col-span-2" placeholder="{{ __('Search by name, code or phone…') }}">
            <select name="batch" class="input" onchange="this.form.submit()" aria-label="{{ __('Class') }}">
                <option value="">{{ __('All my classes') }}</option>
                @foreach ($batches as $batch)
                    <option value="{{ $batch->id }}" @selected((string) request('batch') === (string) $batch->id)>{{ $batch->name }}</option>
                @endforeach
            </select>
        </form>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('Student') }}</th><th>{{ __('Classes') }}</th><th>{{ __('Attendance') }}</th><th>{{ __('Exam average') }}</th><th>{{ __('My notes') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse ($students as $student)
                        @php($s = $stats[$student->id])
                        <tr>
                            <td>
                                <a href="{{ route('teacher.students.show', $student) }}" class="font-semibold text-navy hover:text-brand">{{ $student->full_name }}</a>
                                <p class="font-latin text-xs text-muted" dir="ltr">{{ $student->code }}</p>
                            </td>
                            <td class="text-xs text-muted">{{ $student->batches->pluck('name')->implode('، ') }}</td>
                            <td><span @class(['font-semibold tabular-nums', 'text-rose-600' => $s['attendance'] !== null && $s['attendance'] < 80])>{{ pct($s['attendance']) }}</span></td>
                            <td><span @class(['font-semibold tabular-nums', 'text-rose-600' => $s['average'] !== null && $s['average'] < 70])>{{ pct($s['average']) }}</span></td>
                            <td class="tabular-nums text-muted">{{ $student->notes_count }}</td>
                            <td class="text-end whitespace-nowrap">
                                @if ($student->user_id)
                                    <a href="{{ route('messages.create', ['to' => $student->user_id]) }}" class="btn btn-ghost btn-sm" title="{{ __('Message') }}"><x-lucide-mail class="size-4" /></a>
                                @endif
                                <a href="{{ route('teacher.students.show', $student) }}" class="btn btn-ghost btn-sm" title="{{ __('Open') }}"><x-lucide-chevron-left class="size-4 ltr:rotate-180" /></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty icon="graduation-cap" :title="__('No students found')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $students->links() }}
    </div>
@endsection
