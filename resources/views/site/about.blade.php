@extends('layouts.site')

@section('title', __('About Us'))

@section('content')
    @include('site._banner', ['title' => __('About Talent Academy'), 'subtitle' => __('An educational and training institution in Beni Mazar, Minya — combining academic excellence with job-ready skills.')])

    <section class="mx-auto grid max-w-7xl gap-10 px-6 py-14 lg:grid-cols-2 lg:items-center">
        <div>
            <h2 class="section-title">{{ __('Two paths, one academy') }}</h2>
            <div class="prose-body mt-5 text-ink">
                <p>{{ __('Talent Academy for Educational Services runs two clearly separated tracks under a single identity: academic education for school students (American Diploma, high school and foundation classes) and professional training for young people and graduates (artificial intelligence, cyber security, software development, languages and administration).') }}</p>
                <p>{{ __('Every learner has one unified profile, so a student can join an academic class and a training course at the same time with one record, one account and one financial statement.') }}</p>
                <p>{{ __('Certificates are issued only when attendance reaches 80% and the average assessment score reaches 70%, and every certificate can be verified online by its serial number.') }}</p>
            </div>
            <div class="mt-6 flex gap-3">
                <a href="{{ route('courses.index') }}" class="btn btn-primary">{{ __('Explore programs') }}</a>
                <a href="{{ route('admissions.create') }}" class="btn btn-outline">{{ __('Apply Now') }}</a>
            </div>
        </div>
        <div class="relative">
            <img src="{{ asset('images/students-lab.jpg') }}" alt="" class="aspect-[4/3] w-full rounded-3xl object-cover shadow-xl">
            <div class="absolute -bottom-6 start-6 flex items-center gap-3 rounded-2xl bg-white p-4 shadow-xl">
                <img src="{{ asset('images/director-portrait.png') }}" alt="" class="size-14 rounded-full bg-brand-50 object-cover object-top">
                <div>
                    <p class="font-bold text-navy">{{ \App\Models\Setting::tr('director_name') }}</p>
                    <p class="text-xs text-muted">{{ __('Academy Director') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-canvas py-14">
        <div class="mx-auto grid max-w-7xl gap-5 px-6 md:grid-cols-3">
            @foreach ([
                ['target', __('Our mission'), __('Deliver accredited education and practical training that open real doors for students in Upper Egypt.')],
                ['eye', __('Our vision'), __('To be the most trusted learning institution in Minya for academic and professional growth.')],
                ['handshake', __('Our promise'), __('Small classes, qualified instructors, transparent fees and verifiable certificates.')],
            ] as [$icon, $title, $text])
                <div class="card p-6">
                    <span class="grid size-12 place-items-center rounded-xl bg-brand text-white"><x-dynamic-component :component="'lucide-'.$icon" class="size-6" /></span>
                    <h3 class="mt-4 text-lg font-bold text-navy">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-7 text-muted">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>
@endsection
