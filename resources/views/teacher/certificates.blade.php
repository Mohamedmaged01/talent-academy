@extends('layouts.portal')

@section('title', __('Certificates'))

@section('content')
    <x-page-header :title="__('Certificate nominations')" :subtitle="__('Students need :att% attendance and a :avg% exam average. Nominate the eligible ones; the administration issues the certificate.', ['att' => $minAttendance, 'avg' => $minAverage])" />

    @if ($batches->isEmpty())
        <div class="card"><x-empty icon="award" :title="__('No active or completed classes')" /></div>
    @else
        <div class="mb-4 flex flex-wrap gap-2">
            @foreach ($batches as $item)
                <a href="{{ route('teacher.certificates', ['batch' => $item->id]) }}" @class(['btn btn-sm', 'btn-primary' => $batch->id === $item->id, 'btn-outline' => $batch->id !== $item->id])>
                    {{ $item->name }} @if ($item->status === 'completed')<span class="opacity-70">({{ __('Completed') }})</span>@endif
                </a>
            @endforeach
        </div>

        <form method="POST" action="{{ route('teacher.certificates.nominate', $batch) }}" class="card">
            @csrf
            <div class="card-header">
                <div>
                    <h2 class="card-title">{{ $batch->name }}</h2>
                    <p class="text-xs text-muted">{{ $batch->course->tr('title') }}</p>
                </div>
                <span class="text-sm text-muted">{{ __(':count eligible', ['count' => $rows->where('eligible', true)->count()]) }} / {{ $rows->count() }}</span>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th class="w-10"></th><th>{{ __('Student') }}</th><th>{{ __('Attendance') }}</th><th>{{ __('Exam average') }}</th><th>{{ __('Status') }}</th></tr></thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php
                                $nomination = $nominations[$row['student']->id] ?? null;
                                $canNominate = $row['eligible'] && ! $row['certificate'] && (! $nomination || $nomination->status === 'rejected');
                            @endphp
                            <tr>
                                <td>@if ($canNominate)<input type="checkbox" name="students[]" value="{{ $row['student']->id }}" aria-label="{{ __('Nominate') }}">@endif</td>
                                <td class="font-semibold text-navy">{{ $row['student']->full_name }}</td>
                                <td><span @class(['font-semibold tabular-nums', 'text-emerald-700' => ($row['attendance'] ?? 0) >= $minAttendance, 'text-rose-600' => ($row['attendance'] ?? 0) < $minAttendance])>{{ pct($row['attendance']) }}</span></td>
                                <td><span @class(['font-semibold tabular-nums', 'text-emerald-700' => ($row['average'] ?? 0) >= $minAverage, 'text-rose-600' => ($row['average'] ?? 0) < $minAverage])>{{ pct($row['average']) }}</span></td>
                                <td>
                                    @if ($row['certificate'])
                                        <x-status value="issued" />
                                    @elseif ($nomination)
                                        <x-status :value="$nomination->status" />
                                        @if ($nomination->status === 'rejected' && $nomination->review_note)<p class="text-xs text-muted">{{ $nomination->review_note }}</p>@endif
                                    @elseif ($row['eligible'])
                                        <x-status value="eligible" />
                                    @else
                                        <span class="text-xs text-muted">{{ __('Not eligible yet') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-empty icon="users" :title="__('No students enrolled yet')" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col gap-3 border-t border-line px-5 py-4 sm:flex-row sm:items-end">
                <div class="flex-1"><x-input name="note" :label="__('Note to the administration (optional)')" /></div>
                <button class="btn btn-primary"><x-lucide-award class="size-4" />{{ __('Nominate selected') }}</button>
            </div>
            @error('students')<p class="field-error px-5 pb-4">{{ $message }}</p>@enderror
        </form>
    @endif
@endsection
