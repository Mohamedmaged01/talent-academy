@extends('layouts.portal')

@section('title', $student->full_name)

@section('content')
    <x-page-header :title="$student->full_name" :subtitle="$student->code.' · '.__($student->pathway === 'academic' ? 'Academic' : 'Training')" :back="route('teacher.students.index')">
        @if ($student->user_id)
            <a href="{{ route('messages.create', ['to' => $student->user_id]) }}" class="btn btn-outline"><x-lucide-mail class="size-4" />{{ __('Message student') }}</a>
        @endif
        @foreach ($student->guardians->where('is_active', true) as $guardian)
            <a href="{{ route('messages.create', ['to' => $guardian->id]) }}" class="btn btn-outline"><x-lucide-users class="size-4" />{{ __('Message :name', ['name' => $guardian->name]) }}</a>
        @endforeach
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Progress in my classes') }}</h2></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>{{ __('Class') }}</th><th>{{ __('Attendance') }}</th><th>{{ __('Exam average') }}</th><th>{{ __('Assignments') }}</th></tr></thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td><a href="{{ route('teacher.batches.show', $row['batch']) }}" class="font-semibold text-navy hover:text-brand">{{ $row['batch']->name }}</a><p class="text-xs text-muted">{{ $row['batch']->course->tr('title') }}</p></td>
                                    <td><span @class(['font-semibold tabular-nums', 'text-rose-600' => $row['attendance'] !== null && $row['attendance'] < 80])>{{ pct($row['attendance']) }}</span></td>
                                    <td><span @class(['font-semibold tabular-nums', 'text-rose-600' => $row['average'] !== null && $row['average'] < 70])>{{ pct($row['average']) }}</span></td>
                                    <td class="tabular-nums">{{ $row['submitted'] }} / {{ $row['assignments'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Notes on the student file') }}</h2><span class="text-sm text-muted">{{ $notes->count() }}</span></div>
                <ul class="divide-y divide-line">
                    @forelse ($notes as $note)
                        <li class="flex items-start gap-3 px-5 py-4 text-sm">
                            <img src="{{ $note->author?->avatarUrl() }}" alt="" class="size-8 shrink-0 rounded-full object-cover">
                            <div class="min-w-0 flex-1">
                                <p class="text-xs text-muted">
                                    <span class="font-semibold text-navy">{{ $note->author?->name }}</span> · {{ $note->created_at->translatedFormat('j M Y') }}
                                    @if ($note->batch) · {{ $note->batch->name }}@endif
                                    @if ($note->visible_to_parent)<span class="badge bg-emerald-50 text-emerald-700">{{ __('Shared with parents') }}</span>@endif
                                </p>
                                <p class="mt-1 leading-7 whitespace-pre-line" dir="auto">{{ $note->body }}</p>
                            </div>
                            @if ($note->author_id === auth()->id())
                                <x-confirm-delete :action="route('teacher.notes.destroy', $note)" />
                            @endif
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-muted">{{ __('No notes yet.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="space-y-6">
            <form method="POST" action="{{ route('teacher.students.notes.store', $student) }}" class="card">
                @csrf
                <div class="card-header"><h2 class="card-title">{{ __('Add a note') }}</h2></div>
                <div class="grid gap-3 p-5">
                    <x-select name="batch_id" :label="__('Class')" :options="$batches->pluck('name', 'id')" :placeholder="__('General')" />
                    <x-input name="body" :label="__('Note')" type="textarea" rows="4" required dir="auto" />
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="visible_to_parent" value="1"> {{ __('Share with the parents') }}
                    </label>
                    <button class="btn btn-primary"><x-lucide-save class="size-4" />{{ __('Save note') }}</button>
                </div>
            </form>

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Contact') }}</h2></div>
                <dl class="space-y-2 p-5 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-muted">{{ __('Phone') }}</dt><dd dir="ltr">{{ $student->phone ?? '—' }}</dd></div>
                    @foreach ($student->guardians as $guardian)
                        <div class="flex justify-between gap-3"><dt class="text-muted">{{ __('Parent') }}</dt><dd class="text-end">{{ $guardian->name }}<br><span class="text-xs text-muted" dir="ltr">{{ $guardian->phone }}</span></dd></div>
                    @endforeach
                </dl>
            </div>
        </div>
    </div>
@endsection
