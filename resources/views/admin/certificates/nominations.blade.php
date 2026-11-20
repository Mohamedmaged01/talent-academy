@extends('layouts.portal')

@section('title', __('Certificate nominations'))

@section('content')
    <x-page-header :title="__('Certificate nominations')" :subtitle="__('Teachers recommend students who meet the rules. Approving issues the certificate.')" :back="route('admin.certificates.index')" />

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach (['pending', 'approved', 'rejected', 'all'] as $key)
            <a href="{{ route('admin.nominations.index', ['status' => $key]) }}" @class(['btn btn-sm', 'btn-primary' => $status === $key, 'btn-outline' => $status !== $key])>
                {{ __(\Illuminate\Support\Str::headline($key)) }} @isset($counts[$key])<span class="opacity-70">({{ $counts[$key] }})</span>@endisset
            </a>
        @endforeach
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('Student') }}</th><th>{{ __('Class') }}</th><th>{{ __('Nominated by') }}</th><th>{{ __('Note') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse ($nominations as $nomination)
                        <tr>
                            <td><a href="{{ route('admin.students.show', $nomination->student) }}" class="font-semibold text-navy hover:text-brand">{{ $nomination->student->full_name }}</a></td>
                            <td><p>{{ $nomination->batch->name }}</p><p class="text-xs text-muted">{{ $nomination->batch->course->tr('title') }}</p></td>
                            <td class="text-muted"><p>{{ $nomination->nominator?->name }}</p><p class="text-xs">{{ $nomination->created_at->translatedFormat('j M Y') }}</p></td>
                            <td class="max-w-xs text-xs text-muted">{{ $nomination->note }}</td>
                            <td>
                                <x-status :value="$nomination->status" />
                                @if ($nomination->review_note)<p class="text-xs text-muted">{{ $nomination->review_note }}</p>@endif
                                @if ($nomination->certificate)<a href="{{ route('admin.certificates.show', $nomination->certificate) }}" class="font-latin text-xs text-brand hover:underline" dir="ltr">{{ $nomination->certificate->serial }}</a>@endif
                            </td>
                            <td class="text-end">
                                @if ($nomination->status === 'pending' && auth()->user()->canAdmin('certificates', 'manage'))
                                    <div class="flex flex-col items-end gap-2" x-data="{ reject: false }">
                                        <div class="flex gap-2">
                                            <form method="POST" action="{{ route('admin.nominations.approve', $nomination) }}">@csrf<button class="btn btn-primary btn-sm"><x-lucide-check class="size-4" />{{ __('Approve & issue') }}</button></form>
                                            <button type="button" class="btn btn-danger-soft btn-sm" @click="reject = ! reject">{{ __('Reject') }}</button>
                                        </div>
                                        <form method="POST" action="{{ route('admin.nominations.reject', $nomination) }}" x-show="reject" x-cloak class="flex gap-2">
                                            @csrf
                                            <input name="review_note" required class="input py-1.5 text-xs" placeholder="{{ __('Reason') }}" aria-label="{{ __('Reason') }}">
                                            <button class="btn btn-danger btn-sm">{{ __('Send') }}</button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty icon="award" :title="__('No nominations here')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $nominations->links() }}
    </div>
@endsection
