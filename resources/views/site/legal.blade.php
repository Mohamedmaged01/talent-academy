@extends('layouts.site')

@section('title', $type === 'privacy' ? __('Privacy Policy') : __('Terms & Conditions'))

@section('content')
    @include('site._banner', ['title' => $type === 'privacy' ? __('Privacy Policy') : __('Terms & Conditions')])

    <section class="mx-auto max-w-3xl px-6 py-12">
        <div class="card prose-body p-8 text-ink">
            @if ($type === 'privacy')
                <p>{{ __('Talent Academy collects only the information needed to run admissions, classes, attendance, assessments and fees: names, contact details, national ID where required, and academic and payment records.') }}</p>
                <p>{{ __('Student records are visible only to the student, their linked parents or guardians, the instructors of their classes and authorised academy staff. We never sell or share personal data with advertisers.') }}</p>
                <p>{{ __('You may ask the academy at any time to review or correct the information we hold about you or your child.') }}</p>
            @else
                <p>{{ __('Enrollment is confirmed once the admissions office accepts the application and the first fee installment is paid.') }}</p>
                <p>{{ __('Certificates are issued only to learners who reach at least 80% attendance and a 70% average score in assessments.') }}</p>
                <p>{{ __('Portal accounts are personal. Do not share your password; contact the academy immediately if you believe your account has been accessed by someone else.') }}</p>
            @endif
            <p class="text-sm text-muted">{{ __('Last updated') }}: {{ now()->translatedFormat('F Y') }}</p>
        </div>
    </section>
@endsection
