<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir ?? 'rtl' }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') — @endif{{ __('Talent Academy') }}</title>
    <meta name="description" content="@yield('description', __('Accredited academic education and professional training programs in Beni Mazar, Minya.'))">
    <link rel="icon" href="{{ asset('images/logo.jpg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-ink">
    @php
        $navLinks = [
            ['route' => 'home', 'label' => __('Home')],
            ['route' => 'about', 'label' => __('About Us')],
            ['url' => route('courses.index', ['pathway' => 'academic']), 'label' => __('Academic Programs'), 'match' => 'courses.*'],
            ['url' => route('courses.index', ['pathway' => 'training']), 'label' => __('Training Programs'), 'match' => 'courses.*'],
            ['route' => 'news.index', 'label' => __('News'), 'match' => 'news.*'],
            ['route' => 'certificates.verify', 'label' => __('Verify Certificate')],
            ['url' => route('home').'#contact', 'label' => __('Contact')],
        ];
        $otherLocale = app()->getLocale() === 'ar' ? 'en' : 'ar';
    @endphp

    @if ($notice = \App\Models\Setting::tr('notice_bar'))
        <div class="bg-gold px-6 py-2 text-center text-sm font-semibold text-navy" role="note">{{ $notice }}</div>
    @endif

    {{-- Top utility bar --}}
    <div class="hidden border-b border-line bg-white text-xs text-muted md:block">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-2">
            <div class="flex items-center gap-5">
                <span class="inline-flex items-center gap-1.5"><x-lucide-map-pin class="size-3.5 text-brand" />{{ __('Beni Mazar - Minya') }}</span>
                <a href="tel:{{ \App\Models\Setting::get('contact_phone_1') }}" class="inline-flex items-center gap-1.5 hover:text-brand" dir="ltr">
                    <x-lucide-phone class="size-3.5 text-brand" />{{ \App\Models\Setting::get('contact_phone_1') }} · {{ \App\Models\Setting::get('contact_phone_2') }}
                </a>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ \App\Models\Setting::get('social_facebook') }}" target="_blank" rel="noopener" class="hover:text-brand" aria-label="Facebook"><x-lucide-facebook class="size-4" /></a>
                <a href="{{ \App\Models\Setting::get('social_youtube') }}" target="_blank" rel="noopener" class="hover:text-brand" aria-label="YouTube"><x-lucide-youtube class="size-4" /></a>
                <a href="{{ \App\Models\Setting::get('social_instagram') }}" target="_blank" rel="noopener" class="hover:text-brand" aria-label="Instagram"><x-lucide-instagram class="size-4" /></a>
                <span class="h-4 w-px bg-line"></span>
                <a href="{{ route('locale.switch', $otherLocale) }}" class="inline-flex items-center gap-1 font-semibold text-navy hover:text-brand">
                    <x-lucide-languages class="size-4" />{{ $otherLocale === 'en' ? 'English' : 'العربية' }}
                </a>
            </div>
        </div>
    </div>

    {{-- Main header --}}
    <header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-line bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 md:px-6">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-3">
                <img src="{{ asset('images/logo.jpg') }}" alt="" class="size-12 rounded-lg object-contain">
                <span class="leading-tight" dir="ltr">
                    <span class="block font-latin text-lg font-extrabold tracking-wide text-navy">TALENT ACADEMY</span>
                    <span class="block font-latin text-xs text-brand">For Educational Services</span>
                </span>
            </a>

            <nav class="hidden items-center gap-1 lg:flex" aria-label="{{ __('Main') }}">
                @foreach ($navLinks as $link)
                    @php
                        $href = $link['url'] ?? route($link['route']);
                        $active = isset($link['route']) && request()->routeIs($link['route']);
                    @endphp
                    <a href="{{ $href }}" @class([
                        'rounded-lg px-3 py-2 text-sm font-medium transition',
                        'text-brand bg-brand-50' => $active,
                        'text-ink hover:text-brand' => ! $active,
                    ])>{{ $link['label'] }}</a>
                @endforeach
            </nav>

            <div class="hidden items-center gap-2 lg:flex">
                @auth
                    <a href="{{ auth()->user()->homeRoute() }}" class="btn btn-primary"><x-lucide-layout-dashboard class="size-4" />{{ __('My Dashboard') }}</a>
                @else
                    <a href="{{ route('admissions.create') }}" class="btn btn-outline">{{ __('Apply Now') }}</a>
                    <a href="{{ route('login') }}" class="btn btn-primary"><x-lucide-log-in class="size-4" />{{ __('Login') }}</a>
                @endauth
            </div>

            <button type="button" class="btn btn-ghost lg:hidden" @click="open = !open" :aria-expanded="open" aria-label="{{ __('Menu') }}">
                <x-lucide-menu class="size-6" x-show="!open" />
                <x-lucide-x class="size-6" x-show="open" x-cloak />
            </button>
        </div>

        <div x-show="open" x-collapse x-cloak class="border-t border-line lg:hidden">
            <nav class="flex flex-col gap-1 px-4 py-3">
                @foreach ($navLinks as $link)
                    <a href="{{ $link['url'] ?? route($link['route']) }}" class="rounded-lg px-3 py-2.5 text-sm font-medium hover:bg-brand-50 hover:text-brand">{{ $link['label'] }}</a>
                @endforeach
                <a href="{{ route('locale.switch', $otherLocale) }}" class="rounded-lg px-3 py-2.5 text-sm font-semibold text-brand">{{ $otherLocale === 'en' ? 'English' : 'العربية' }}</a>
                <div class="mt-2 grid grid-cols-2 gap-2">
                    @auth
                        <a href="{{ auth()->user()->homeRoute() }}" class="btn btn-primary col-span-2">{{ __('My Dashboard') }}</a>
                    @else
                        <a href="{{ route('admissions.create') }}" class="btn btn-outline">{{ __('Apply Now') }}</a>
                        <a href="{{ route('login') }}" class="btn btn-primary">{{ __('Login') }}</a>
                    @endauth
                </div>
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-navy text-slate-300">
        <div class="mx-auto grid max-w-7xl gap-10 px-6 py-14 md:grid-cols-4">
            <div class="md:col-span-2">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo.jpg') }}" alt="" class="size-14 rounded-xl bg-white object-contain p-1">
                    <div dir="ltr">
                        <p class="font-latin text-xl font-extrabold text-white">TALENT ACADEMY</p>
                        <p class="font-latin text-sm text-gold">For Educational Services</p>
                    </div>
                </div>
                <p class="mt-5 max-w-md text-sm leading-7">{{ __('Accredited academic education and professional training under the supervision of expert faculty. Together, we build the future.') }}</p>
            </div>
            <div>
                <p class="mb-4 font-bold text-white">{{ __('Quick Links') }}</p>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ route('courses.index') }}" class="hover:text-gold">{{ __('All Programs') }}</a></li>
                    <li><a href="{{ route('admissions.create') }}" class="hover:text-gold">{{ __('Apply Now') }}</a></li>
                    <li><a href="{{ route('certificates.verify') }}" class="hover:text-gold">{{ __('Verify Certificate') }}</a></li>
                    <li><a href="{{ route('downloads.public') }}" class="hover:text-gold">{{ __('Downloads Center') }}</a></li>
                    <li><a href="{{ route('privacy') }}" class="hover:text-gold">{{ __('Privacy Policy') }}</a></li>
                    <li><a href="{{ route('terms') }}" class="hover:text-gold">{{ __('Terms & Conditions') }}</a></li>
                </ul>
            </div>
            <div>
                <p class="mb-4 font-bold text-white">{{ __('Contact Us') }}</p>
                <ul class="space-y-3 text-sm">
                    <li class="flex gap-2"><x-lucide-map-pin class="mt-0.5 size-4 shrink-0 text-gold" />{{ \App\Models\Setting::tr('contact_address') }}</li>
                    <li class="flex gap-2"><x-lucide-phone class="mt-0.5 size-4 shrink-0 text-gold" /><span dir="ltr">{{ \App\Models\Setting::get('contact_phone_1') }} · {{ \App\Models\Setting::get('contact_phone_2') }}</span></li>
                    <li class="flex gap-2"><x-lucide-mail class="mt-0.5 size-4 shrink-0 text-gold" />{{ \App\Models\Setting::get('contact_email') }}</li>
                    <li class="flex gap-2"><x-lucide-clock class="mt-0.5 size-4 shrink-0 text-gold" />{{ \App\Models\Setting::tr('working_hours') }}</li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-6 py-5 text-xs md:flex-row">
                <p>© {{ now()->year }} {{ __('Talent Academy for Educational Services. All rights reserved.') }}</p>
                <p class="text-gold">{{ __('Together .. We Build the Future') }}</p>
            </div>
        </div>
    </footer>

    @php $wa = \App\Models\Setting::get('contact_whatsapp'); @endphp
    @if ($wa)
        <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" aria-label="WhatsApp"
           class="fixed bottom-5 end-5 z-40 grid size-14 place-items-center rounded-full bg-emerald-500 text-white shadow-lg shadow-emerald-500/30 transition hover:scale-105">
            <x-lucide-message-circle class="size-7" />
        </a>
    @endif
</body>
</html>
