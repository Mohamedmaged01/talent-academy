@extends('layouts.portal')

@section('title', __('Certificates'))

@section('content')
    <x-page-header :title="__('Certificates')" :subtitle="__('Issued certificates. Each one can be verified publicly by its serial number.')">
        <a href="{{ route('certificates.verify') }}" target="_blank" class="btn btn-outline"><x-lucide-shield-check class="size-4" />{{ __('Public verification page') }}</a>
        <a href="{{ route('admin.certificates.create') }}" class="btn btn-primary"><x-lucide-award class="size-4" />{{ __('Issue Certificate') }}</a>
    </x-page-header>

    <div class="card">
        <form method="GET" class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row">
            <div class="relative flex-1">
                <x-lucide-search class="pointer-events-none absolute top-1/2 start-3 size-4 -translate-y-1/2 text-muted" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Serial or student…') }}" class="input ps-9">
            </div>
            <select name="status" class="input sm:w-44" onchange="this.form.submit()">
                <option value="">{{ __('All statuses') }}</option>
                <option value="valid" @selected(request('status') === 'valid')>{{ __('Valid') }}</option>
                <option value="revoked" @selected(request('status') === 'revoked')>{{ __('Revoked') }}</option>
            </select>
        </form>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('Serial') }}</th><th>{{ __('Holder') }}</th><th>{{ __('Certificate') }}</th><th>{{ __('Grade') }}</th><th>{{ __('Issued on') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse ($certificates as $certificate)
                        <tr>
                            <td class="font-latin text-xs font-semibold text-brand" dir="ltr">{{ $certificate->serial }}</td>
                            <td><a href="{{ route('admin.students.show', $certificate->student) }}" class="font-medium hover:text-brand">{{ $certificate->student->full_name }}</a></td>
                            <td class="max-w-72 truncate text-muted">{{ $certificate->tr('title') }}</td>
                            <td>{{ $certificate->grade ? __($certificate->grade) : '—' }}</td>
                            <td class="text-xs text-muted">{{ $certificate->issued_at->translatedFormat('j M Y') }}</td>
                            <td><x-status :value="$certificate->status" /></td>
                            <td class="whitespace-nowrap text-end" x-data="{ open: false }">
                                <a href="{{ route('admin.certificates.show', $certificate) }}" target="_blank" class="btn btn-ghost btn-sm" title="{{ __('Print') }}"><x-lucide-printer class="size-4" /></a>
                                @if ($certificate->isValid())
                                    <button type="button" class="btn btn-danger-soft btn-sm" @click="open = !open" title="{{ __('Revoke') }}"><x-lucide-ban class="size-4" /></button>
                                    <form x-show="open" x-cloak method="POST" action="{{ route('admin.certificates.revoke', $certificate) }}" class="mt-2 flex gap-2">
                                        @csrf
                                        <input name="revoked_reason" required placeholder="{{ __('Reason') }}" class="input py-1.5 text-xs">
                                        <button class="btn btn-danger btn-sm">{{ __('Revoke') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty icon="award" :title="__('No certificates issued yet')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $certificates->links() }}
    </div>

    <div class="card mt-6">
        <div class="card-header"><h2 class="card-title">{{ __('Issue for a whole class') }}</h2></div>
        <p class="px-5 pt-4 text-sm text-muted">{{ __('Open a class to see who meets the attendance and grade requirements, then issue all eligible certificates in one step.') }}</p>
        <div class="flex flex-wrap gap-2 p-5">
            @foreach ($batches as $batch)
                <a href="{{ route('admin.batches.show', $batch) }}" class="btn btn-outline btn-sm">{{ $batch->name }}</a>
            @endforeach
        </div>
    </div>
@endsection
