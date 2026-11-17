<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir ?? 'rtl' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Sign In') }} — {{ __('Talent Academy') }}</title>
    <link rel="icon" href="{{ asset('images/logo.jpg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-canvas">
    <div class="grid min-h-screen lg:grid-cols-2">
        <div class="relative hidden overflow-hidden bg-brand-dark lg:block">
            <img src="{{ asset('images/login-hero.jpg') }}" alt="" class="absolute inset-0 size-full object-cover opacity-40">
            <div class="absolute inset-0 bg-gradient-to-t from-navy via-brand-dark/70 to-brand/40"></div>
            <div class="relative flex h-full flex-col justify-between p-12 text-white">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <img src="{{ asset('images/logo.jpg') }}" alt="" class="size-14 rounded-xl bg-white object-contain p-1">
                    <span dir="ltr"><span class="block font-latin text-xl font-extrabold">TALENT ACADEMY</span><span class="block font-latin text-sm text-gold">For Educational Services</span></span>
                </a>
                <div>
                    <p class="text-4xl font-extrabold leading-tight">{{ __('One platform for students, parents, teachers and administration.') }}</p>
                    <ul class="mt-8 space-y-3 text-slate-200">
                        @foreach ([__('Schedules, attendance and grades in one place'), __('Fees, invoices and receipts with full history'), __('Verifiable certificates with a unique serial')] as $point)
                            <li class="flex items-center gap-3"><x-lucide-circle-check class="size-5 text-gold" />{{ $point }}</li>
                        @endforeach
                    </ul>
                </div>
                <p class="text-sm text-slate-300">© {{ now()->year }} Talent Academy</p>
            </div>
        </div>

        <div class="flex flex-col">
            <div class="flex items-center justify-between p-6">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-brand"><x-lucide-arrow-right class="size-4 ltr:rotate-180" />{{ __('Back to website') }}</a>
                <a href="{{ route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}" class="btn btn-ghost btn-sm"><x-lucide-languages class="size-4" />{{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}</a>
            </div>

            <div class="flex flex-1 items-center justify-center px-6 pb-12">
                <div class="w-full max-w-md">
                    <img src="{{ asset('images/logo.jpg') }}" alt="" class="mx-auto mb-6 size-16 rounded-2xl object-contain lg:hidden">
                    <h1 class="text-3xl font-extrabold text-navy">{{ __('Welcome back') }}</h1>
                    <p class="mt-2 text-muted">{{ __('Sign in to your Talent Academy portal.') }}</p>

                    <form method="POST" action="{{ route('login.attempt') }}" class="mt-8 space-y-5">
                        @csrf
                        <x-input name="email" :label="__('Email')" type="email" required autofocus autocomplete="username" dir="ltr" />
                        <div x-data="{ show: false }">
                            <label for="password" class="label">{{ __('Password') }}</label>
                            <div class="relative">
                                <input id="password" name="password" :type="show ? 'text' : 'password'" type="password" required autocomplete="current-password" class="input pe-11" dir="ltr">
                                <button type="button" @click="show = !show" class="absolute inset-y-0 end-0 grid w-11 place-items-center text-muted hover:text-brand" :aria-label="show ? @js(__('Hide password')) : @js(__('Show password'))">
                                    <x-lucide-eye class="size-4" x-show="!show" />
                                    <x-lucide-eye-off class="size-4" x-show="show" x-cloak />
                                </button>
                            </div>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-muted">
                            <input type="checkbox" name="remember" value="1"> {{ __('Remember me') }}
                        </label>
                        <button class="btn btn-primary w-full py-3 text-base"><x-lucide-log-in class="size-5 rtl:rotate-180" />{{ __('Sign In') }}</button>
                    </form>

                    @if (app()->environment('local'))
                        <div class="mt-8 rounded-2xl border border-dashed border-brand/40 bg-brand-50/60 p-4" x-data>
                            <p class="text-xs font-bold text-brand">{{ __('Demo accounts (local only) — password: password') }}</p>
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                @foreach (['admin@talent.test' => __('Administrator'), 'teacher@talent.test' => __('Teacher'), 'student@talent.test' => __('Student'), 'parent@talent.test' => __('Parent')] as $email => $role)
                                    <button type="button" class="btn btn-outline btn-sm justify-start"
                                            @click="document.getElementById('email').value = @js($email); document.getElementById('password').value = 'password'">
                                        <x-lucide-user-round class="size-3.5" />{{ $role }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>
