@extends('layouts.portal')

@section('title', __('Certificates'))

@section('content')
    <x-page-header :title="__('My Certificates')" :subtitle="__('Certificates are issued when attendance is at least 80% and the exam average is at least 70%.')" />

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($certificates as $certificate)
            <div @class(['card flex flex-col p-6', 'opacity-60' => ! $certificate->isValid()])>
                <span class="grid size-14 place-items-center rounded-2xl bg-gold/20 text-navy"><x-lucide-award class="size-7" /></span>
                <p class="mt-4 font-bold leading-7 text-navy">{{ $certificate->tr('title') }}</p>
                <p class="mt-1 text-sm text-muted">{{ $certificate->issued_at->translatedFormat('j F Y') }} @if ($certificate->grade)· {{ __($certificate->grade) }}@endif</p>
                <p class="mt-2 font-latin text-xs font-semibold text-brand" dir="ltr">{{ $certificate->serial }}</p>
                <div class="mt-auto flex gap-2 pt-5">
                    @if ($certificate->isValid())
                        <a href="{{ route('student.certificates.show', $certificate) }}" target="_blank" class="btn btn-primary btn-sm flex-1"><x-lucide-printer class="size-4" />{{ __('View & print') }}</a>
                    @else
                        <x-status value="revoked" />
                    @endif
                </div>
            </div>
        @empty
            <x-empty icon="award" :title="__('No certificates yet')" :message="__('Keep up your attendance and grades — your certificate will appear here once issued.')" class="card md:col-span-2 xl:col-span-3" />
        @endforelse
    </div>
@endsection
