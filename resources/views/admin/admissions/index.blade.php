@extends('layouts.portal')

@section('title', __('Admissions'))

@section('content')
    <x-page-header :title="__('Admissions')" :subtitle="__('Online applications from the public website.')" />

    <div class="mb-5 flex flex-wrap gap-2">
        <a href="{{ route('admin.admissions.index') }}" @class(['btn btn-sm', 'btn-primary' => ! request('status'), 'btn-outline' => request('status')])>{{ __('All') }} <span class="opacity-70">{{ $counts->sum() }}</span></a>
        @foreach (\App\Models\AdmissionRequest::STATUSES as $status)
            <a href="{{ route('admin.admissions.index', ['status' => $status]) }}" @class(['btn btn-sm', 'btn-primary' => request('status') === $status, 'btn-outline' => request('status') !== $status])>
                {{ __(ucfirst($status)) }} <span class="opacity-70">{{ $counts[$status] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <div class="card">
        <form method="GET" class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <div class="relative flex-1">
                <x-lucide-search class="pointer-events-none absolute top-1/2 start-3 size-4 -translate-y-1/2 text-muted" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Name, phone or reference…') }}" class="input ps-9">
            </div>
            <select name="pathway" class="input sm:w-48" onchange="this.form.submit()">
                <option value="">{{ __('All pathways') }}</option>
                <option value="academic" @selected(request('pathway') === 'academic')>{{ __('Academic') }}</option>
                <option value="training" @selected(request('pathway') === 'training')>{{ __('Training') }}</option>
            </select>
        </form>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('Applicant') }}</th><th>{{ __('Reference') }}</th><th>{{ __('Program') }}</th><th>{{ __('Received') }}</th><th>{{ __('Status') }}</th></tr></thead>
                <tbody>
                    @forelse ($admissions as $admission)
                        <tr class="cursor-pointer" onclick="window.location='{{ route('admin.admissions.show', $admission) }}'">
                            <td><p class="font-semibold text-navy">{{ $admission->full_name }}</p><p class="text-xs text-muted" dir="ltr">{{ $admission->phone }}</p></td>
                            <td class="font-latin text-xs text-muted" dir="ltr">{{ $admission->reference }}</td>
                            <td><x-status :value="$admission->pathway" /> <span class="ms-1 text-muted">{{ \Illuminate\Support\Str::limit($admission->course?->tr('title'), 40) }}</span></td>
                            <td class="text-xs text-muted">{{ $admission->created_at->diffForHumans() }}</td>
                            <td><x-status :value="$admission->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty icon="user-plus" :title="__('No applications found')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $admissions->links() }}
    </div>
@endsection
