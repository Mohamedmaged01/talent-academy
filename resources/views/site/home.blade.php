@extends('layouts.site')

@section('content')
    {{-- HERO --}}
    <section class="relative overflow-hidden bg-brand-dark">
        <img src="{{ asset('images/students-lab.jpg') }}" alt="" class="absolute inset-0 size-full object-cover opacity-25">
        <div class="absolute inset-0 bg-gradient-to-l from-brand-dark/60 via-brand/80 to-brand-dark"></div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-10 px-6 pb-0 pt-12 lg:grid-cols-12 lg:pt-16">
            {{-- Pillars card --}}
            <div class="order-3 hidden lg:order-1 lg:col-span-3 lg:block lg:self-center lg:pb-16">
                <div class="space-y-1 rounded-2xl border border-white/10 bg-navy/60 p-3 backdrop-blur">
                    @foreach ([
                        ['graduation-cap', __('Academic Education'), __('American Diploma')],
                        ['monitor', __('Training Programs'), __('Specialized Courses')],
                        ['chart-column', __('Integrated Management'), __('Finance & Reports')],
                        ['brain', __('Smart Analytics'), __('Decision Making')],
                    ] as [$icon, $title, $sub])
                        <div class="flex items-center gap-3 rounded-xl p-3 text-white">
                            <span class="grid size-11 place-items-center rounded-xl bg-gold/15 text-gold ring-1 ring-gold/30">
                                <x-dynamic-component :component="'lucide-'.$icon" class="size-5" />
                            </span>
                            <span>
                                <span class="block text-sm font-bold">{{ $title }}</span>
                                <span class="block text-xs text-slate-300">{{ $sub }}</span>
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Headline --}}
            <div class="order-1 pb-6 text-white lg:order-2 lg:col-span-5 lg:pb-16">
                <h1 class="text-4xl font-extrabold leading-tight md:text-6xl">{{ __('Talent Academy') }}</h1>
                <p class="mt-3 text-2xl font-extrabold text-gold md:text-4xl">{{ __('Your Journey to a Brighter Future') }}</p>
                <p class="mt-5 text-lg leading-8 text-slate-100">
                    {{ __('Accredited Academic Education + Professional Training Programs') }}<br>
                    <span class="text-slate-300">{{ __('Under the supervision of expert faculty') }}</span>
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('admissions.create') }}" class="btn btn-gold px-6 py-3 text-base">
                        {{ __('Start Your Journey') }} <x-lucide-arrow-left class="size-5 ltr:rotate-180" />
                    </a>
                    <a href="{{ route('about') }}" class="btn border border-white/40 px-6 py-3 text-base text-white hover:bg-white/10">
                        <x-lucide-circle-play class="size-5" /> {{ __('Discover Our Academy') }}
                    </a>
                </div>
            </div>

            {{-- Director --}}
            <div class="relative order-2 pb-10 lg:order-3 lg:col-span-4 lg:self-center lg:pb-16">
                <figure class="relative mx-auto max-w-sm">
                    <div class="absolute -inset-3 rotate-3 rounded-[2rem] bg-gold/80"></div>
                    <img src="{{ asset('images/director-portrait.png') }}" alt="{{ \App\Models\Setting::tr('director_name') }}"
                         class="relative aspect-[238/212] w-full rounded-[1.75rem] object-cover shadow-2xl ring-4 ring-white/30">
                    <figcaption class="absolute -bottom-6 start-6 flex items-center gap-3 rounded-2xl bg-white px-5 py-3 shadow-xl">
                        <span class="grid size-10 place-items-center rounded-full bg-brand text-white"><x-lucide-user-round class="size-5" /></span>
                        <span class="leading-tight">
                            <span class="block font-bold text-navy">{{ \App\Models\Setting::tr('director_name') }}</span>
                            <span class="block text-xs text-muted">{{ __('Academy Director') }}</span>
                        </span>
                    </figcaption>
                </figure>
            </div>
        </div>
    </section>

    {{-- QUICK SHORTCUTS --}}
    <section class="border-b border-line bg-white">
        <div class="mx-auto grid max-w-7xl grid-cols-3 gap-3 px-6 py-8 sm:grid-cols-5 lg:grid-cols-9">
            @foreach ([
                ['users', __('Students & Trainees'), route('login')],
                ['presentation', __('Teachers & Trainers'), route('login')],
                ['book-open', __('Programs & Courses'), route('courses.index')],
                ['users-round', __('Groups & Classes'), route('courses.index')],
                ['file-check', __('Exams & Assessments'), route('login')],
                ['calendar-check', __('Attendance & Absence'), route('login')],
                ['award', __('Certificates'), route('certificates.verify')],
                ['wallet', __('Fees & Payments'), route('login')],
                ['chart-column', __('Reports & Analytics'), route('login')],
            ] as [$icon, $label, $href])
                <a href="{{ $href }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-line bg-white p-4 text-center transition hover:-translate-y-0.5 hover:border-brand hover:shadow-md">
                    <span class="grid size-12 place-items-center rounded-xl bg-brand-50 text-brand transition group-hover:bg-brand group-hover:text-white">
                        <x-dynamic-component :component="'lucide-'.$icon" class="size-6" />
                    </span>
                    <span class="text-xs font-semibold text-navy">{{ $label }}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- PATHWAYS --}}
    <section class="bg-canvas py-16">
        <div class="mx-auto max-w-7xl px-6">
            <h2 class="section-title text-center">{{ __('Choose Your Path .. Build Your Future') }}</h2>
            <div class="mt-10 grid gap-6 lg:grid-cols-2">
                @foreach ([
                    ['academic', 'graduation-cap', __('Academic Education'), __('High school students — American Diploma & foundation classes'), $academicCourses, 'images/pathway_academic.jpg', __('Browse Programs')],
                    ['training', 'monitor', __('Training Programs'), __('Specialized courses in computer science, AI, languages, development and administration'), $trainingCourses, 'images/pathway_training.jpg', __('Browse Courses')],
                ] as [$key, $icon, $title, $desc, $list, $image, $cta])
                    <article class="card grid overflow-hidden md:grid-cols-5">
                        <img src="{{ asset($image) }}" alt="" class="h-56 w-full object-cover md:col-span-2 md:h-full">
                        <div class="flex flex-col p-6 md:col-span-3">
                            <div class="flex items-center gap-3">
                                <span class="grid size-11 place-items-center rounded-xl bg-brand text-white"><x-dynamic-component :component="'lucide-'.$icon" class="size-5" /></span>
                                <h3 class="text-xl font-extrabold text-navy">{{ $title }}</h3>
                            </div>
                            <p class="mt-3 text-sm leading-7 text-muted">{{ $desc }}</p>
                            <ul class="mt-4 flex flex-wrap gap-2">
                                @foreach ($list as $course)
                                    <li><a href="{{ route('courses.show', $course) }}" class="inline-block rounded-lg border border-line px-3 py-1.5 text-xs font-medium text-navy hover:border-brand hover:text-brand">{{ \Illuminate\Support\Str::limit($course->tr('title'), 42) }}</a></li>
                                @endforeach
                            </ul>
                            <a href="{{ route('courses.index', ['pathway' => $key]) }}" class="btn btn-primary mt-auto self-start pt-2.5">
                                {{ $cta }} <x-lucide-arrow-left class="size-4 ltr:rotate-180" />
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- NEWS --}}
    <section class="py-16">
        <div class="mx-auto max-w-7xl px-6">
            <div class="flex items-end justify-between gap-4">
                <h2 class="section-title">{{ __('Latest News & Blog') }}</h2>
                <a href="{{ route('news.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-brand hover:underline">
                    {{ __('View All News') }} <x-lucide-chevron-left class="size-4 ltr:rotate-180" />
                </a>
            </div>
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @forelse ($news as $article)
                    @include('site.news._card', ['article' => $article])
                @empty
                    <p class="text-muted">{{ __('No news yet.') }}</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- STATS BAR --}}
    <section class="bg-navy">
        <div class="mx-auto grid max-w-7xl grid-cols-2 divide-white/10 px-6 py-8 text-white sm:grid-cols-3 lg:grid-cols-5 lg:divide-x lg:rtl:divide-x-reverse">
            @foreach ([
                ['users', 'stat_students', __('Students & Pupils')],
                ['monitor', 'stat_trainees', __('Trainees')],
                ['presentation', 'stat_teachers', __('Teachers & Trainers')],
                ['chart-column', 'stat_programs', __('Training Programs')],
                ['users-round', 'stat_groups', __('Groups & Classes')],
            ] as [$icon, $key, $label])
                <div class="flex items-center justify-center gap-3 px-4 py-3">
                    <x-dynamic-component :component="'lucide-'.$icon" class="size-8 text-gold" />
                    <div>
                        <p class="font-latin text-2xl font-extrabold" dir="ltr">{{ \App\Models\Setting::get($key) }}</p>
                        <p class="text-xs text-slate-300">{{ $label }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- CONTACT --}}
    <section id="contact" class="scroll-mt-24 bg-canvas py-16">
        <div class="mx-auto grid max-w-7xl gap-8 px-6 lg:grid-cols-2">
            <div>
                <h2 class="section-title">{{ __('Contact Us') }}</h2>
                <p class="mt-2 text-muted">{{ __('Have a question about a program or enrollment? Send us a message and our team will get back to you.') }}</p>

                @if (session('contact_success'))
                    <div class="mt-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                        <x-lucide-circle-check class="mt-0.5 size-4 shrink-0" /> {{ session('contact_success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.store') }}" class="card mt-6 grid gap-4 p-6 sm:grid-cols-2">
                    @csrf
                    <x-input name="name" :label="__('Full Name')" required bag="contact" />
                    <x-input name="phone" :label="__('Phone')" type="tel" bag="contact" dir="ltr" />
                    <x-input name="email" :label="__('Email')" type="email" bag="contact" class="sm:col-span-2" dir="ltr" />
                    <x-input name="subject" :label="__('Subject')" bag="contact" class="sm:col-span-2" />
                    <x-input name="message" :label="__('Message')" type="textarea" rows="6" required bag="contact" class="sm:col-span-2" />
                    <div class="sm:col-span-2">
                        <button class="btn btn-primary w-full sm:w-auto"><x-lucide-send class="size-4 rtl:-scale-x-100" /> {{ __('Send Message') }}</button>
                    </div>
                </form>
            </div>

            <div class="space-y-4">
                <div class="card grid gap-4 p-6 sm:grid-cols-2">
                    @foreach ([
                        ['map-pin', __('Address'), \App\Models\Setting::tr('contact_address')],
                        ['phone', __('Phone'), \App\Models\Setting::get('contact_phone_1').' · '.\App\Models\Setting::get('contact_phone_2')],
                        ['mail', __('Email'), \App\Models\Setting::get('contact_email')],
                        ['clock', __('Working Hours'), \App\Models\Setting::tr('working_hours')],
                    ] as [$icon, $label, $value])
                        <div class="flex gap-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand"><x-dynamic-component :component="'lucide-'.$icon" class="size-5" /></span>
                            <div class="min-w-0">
                                <p class="text-xs text-muted">{{ $label }}</p>
                                <p class="text-sm font-semibold text-navy break-words">{{ $value }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if ($map = \App\Models\Setting::get('map_embed_url'))
                    <iframe src="{{ $map }}" class="h-80 w-full rounded-2xl border border-line" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="{{ __('Map') }}"></iframe>
                @endif
            </div>
        </div>
    </section>
@endsection
