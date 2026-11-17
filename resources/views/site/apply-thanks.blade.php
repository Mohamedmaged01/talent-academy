@extends('layouts.site')

@section('title', __('Application received'))

@section('content')
    <section class="mx-auto max-w-2xl px-6 py-20 text-center">
        <span class="mx-auto grid size-20 place-items-center rounded-full bg-emerald-50 text-emerald-600">
            <x-lucide-circle-check class="size-10" />
        </span>
        <h1 class="mt-6 text-3xl font-extrabold text-navy">{{ __('Application received') }}</h1>
        <p class="mt-3 text-muted">{{ __('Thank you, :name. Our admissions team will call you within one working day.', ['name' => $admission->full_name]) }}</p>
        <div class="card mx-auto mt-8 max-w-sm p-5">
            <p class="text-sm text-muted">{{ __('Your reference number') }}</p>
            <p class="mt-1 font-latin text-2xl font-extrabold tracking-wider text-brand" dir="ltr">{{ $admission->reference }}</p>
            <p class="mt-2 text-xs text-muted">{{ __('Keep this number for any follow-up.') }}</p>
        </div>
        <div class="mt-8 flex justify-center gap-3">
            <a href="{{ route('home') }}" class="btn btn-outline">{{ __('Back to home') }}</a>
            <a href="{{ route('courses.index') }}" class="btn btn-primary">{{ __('Browse programs') }}</a>
        </div>
    </section>
@endsection
