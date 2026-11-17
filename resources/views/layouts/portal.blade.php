<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir ?? 'rtl' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') — @endif{{ __('Talent Academy') }}</title>
    <link rel="icon" href="{{ asset('images/logo.jpg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-canvas text-ink" x-data="{ sidebar: false }">
    @php
        $user = auth()->user();
        $otherLocale = app()->getLocale() === 'ar' ? 'en' : 'ar';
        $roleLabel = [
            'admin' => __('Administrator'),
            'teacher' => __('Teacher / Trainer'),
            'student' => __('Student / Trainee'),
            'parent' => __('Parent'),
        ][$user->role];
    @endphp

    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <div x-show="sidebar" x-transition.opacity x-cloak class="fixed inset-0 z-40 bg-navy/50 lg:hidden" @click="sidebar = false"></div>
        <aside :class="sidebar && 'max-lg:translate-x-0!'"
               class="fixed inset-y-0 start-0 z-50 flex w-72 shrink-0 flex-col bg-navy transition-transform duration-200 max-lg:ltr:-translate-x-full max-lg:rtl:translate-x-full lg:sticky lg:top-0 lg:h-screen">
            <a href="{{ route('home') }}" class="flex items-center gap-3 border-b border-white/10 px-5 py-5">
                <img src="{{ asset('images/logo.jpg') }}" alt="" class="size-11 rounded-xl bg-white object-contain p-0.5">
                <span dir="ltr" class="leading-tight">
                    <span class="block font-latin text-base font-extrabold tracking-wide text-white">TALENT ACADEMY</span>
                    <span class="block text-[11px] text-gold" dir="auto">{{ __('Portal') }} · {{ $roleLabel }}</span>
                </span>
            </a>

            <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 py-4" aria-label="{{ __('Portal') }}">
                @include('partials.nav-'.$user->role)
            </nav>

            <div class="border-t border-white/10 p-3">
                <a href="{{ route('profile.edit') }}" @class(['nav-link', 'active' => request()->routeIs('profile.*')])>
                    <x-lucide-user-cog class="size-5" />{{ __('My Profile') }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="nav-link w-full hover:!bg-rose-500/15 hover:!text-rose-200">
                        <x-lucide-log-out class="size-5 rtl:rotate-180" />{{ __('Sign Out') }}
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main column --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-30 border-b border-line bg-white/90 backdrop-blur">
                <div class="flex items-center gap-3 px-4 py-3 md:px-8">
                    <button type="button" class="btn btn-ghost -ms-2 lg:hidden" @click="sidebar = true" aria-label="{{ __('Menu') }}">
                        <x-lucide-menu class="size-6" />
                    </button>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm text-muted">{{ now()->translatedFormat('l، j F Y') }}</p>
                    </div>
                    <a href="{{ route('locale.switch', $otherLocale) }}" class="btn btn-ghost btn-sm" title="{{ __('Language') }}">
                        <x-lucide-languages class="size-4" /><span class="hidden sm:inline">{{ $otherLocale === 'en' ? 'English' : 'العربية' }}</span>
                    </a>
                    <a href="{{ route('home') }}" class="btn btn-ghost btn-sm hidden sm:inline-flex" title="{{ __('Public website') }}">
                        <x-lucide-globe class="size-4" />
                    </a>
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-xl px-2 py-1 hover:bg-canvas">
                        <img src="{{ $user->avatarUrl() }}" alt="" class="size-9 rounded-full object-cover ring-2 ring-brand-100">
                        <span class="hidden text-start leading-tight sm:block">
                            <span class="block text-sm font-semibold text-navy">{{ $user->name }}</span>
                            <span class="block text-xs text-muted">{{ $roleLabel }}</span>
                        </span>
                    </a>
                </div>
            </header>

            <main class="flex-1 px-4 py-6 md:px-8 md:py-8">
                <x-flash />
                @yield('content')
            </main>

            <footer class="px-8 py-4 text-center text-xs text-muted">
                © {{ now()->year }} Talent Academy · {{ __('Beni Mazar - Minya') }}
            </footer>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
