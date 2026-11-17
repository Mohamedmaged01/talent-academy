@extends('layouts.portal')

@section('title', __('Students & Trainees'))

@section('content')
    <x-page-header :title="__('Students & Trainees')" :subtitle="__('Every learner, academic and training, in one register.')">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline"><x-lucide-download class="size-4" />{{ __('Export CSV') }}</a>
        <a href="{{ route('admin.students.create') }}" class="btn btn-primary"><x-lucide-user-plus class="size-4" />{{ __('Add Student') }}</a>
    </x-page-header>

    <div class="card">
        <form method="GET" class="grid gap-3 border-b border-line p-4 md:grid-cols-5">
            <div class="relative md:col-span-2">
                <x-lucide-search class="pointer-events-none absolute top-1/2 start-3 size-4 -translate-y-1/2 text-muted" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Name, code or phone…') }}" class="input ps-9">
            </div>
            <select name="pathway" class="input" onchange="this.form.submit()">
                <option value="">{{ __('All pathways') }}</option>
                <option value="academic" @selected(request('pathway') === 'academic')>{{ __('Academic') }}</option>
                <option value="training" @selected(request('pathway') === 'training')>{{ __('Training') }}</option>
            </select>
            <select name="status" class="input" onchange="this.form.submit()">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (\App\Models\Student::STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ __(ucfirst($status)) }}</option>
                @endforeach
            </select>
            <select name="branch" class="input" onchange="this.form.submit()">
                <option value="">{{ __('All branches') }}</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) request('branch') === (string) $branch->id)>{{ $branch->tr('name') }}</option>
                @endforeach
            </select>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>{{ __('Student') }}</th><th>{{ __('Code') }}</th><th>{{ __('Pathway') }}</th><th>{{ __('Grade / Level') }}</th><th>{{ __('Branch') }}</th><th>{{ __('Status') }}</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr>
                            <td>
                                <a href="{{ route('admin.students.show', $student) }}" class="flex items-center gap-3">
                                    <span class="grid size-9 shrink-0 place-items-center rounded-full bg-brand-50 font-bold text-brand">{{ mb_substr($student->full_name, 0, 1) }}</span>
                                    <span>
                                        <span class="block font-semibold text-navy hover:text-brand">{{ $student->full_name }}</span>
                                        <span class="block text-xs text-muted" dir="ltr">{{ $student->phone }}</span>
                                    </span>
                                </a>
                            </td>
                            <td class="font-latin text-xs text-muted" dir="ltr">{{ $student->code }}</td>
                            <td><x-status :value="$student->pathway" /></td>
                            <td class="text-muted">{{ $student->grade_level ?? '—' }}</td>
                            <td class="text-muted">{{ $student->branch?->tr('name') }}</td>
                            <td><x-status :value="$student->status" /></td>
                            <td class="text-end">
                                <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-ghost btn-sm" title="{{ __('Edit') }}"><x-lucide-pencil class="size-4" /></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty icon="graduation-cap" :title="__('No students found')" :message="__('Try a different filter or add a new student.')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $students->links() }}
    </div>
@endsection
