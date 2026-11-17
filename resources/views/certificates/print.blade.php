@extends('layouts.print')

@section('title', $certificate->serial)

@section('content')
    <div class="relative mx-auto aspect-[1.414/1] max-w-5xl overflow-hidden bg-white p-3 shadow-sm print:shadow-none">
        <div class="h-full border-[6px] border-double border-brand p-2">
            <div class="relative flex h-full flex-col items-center justify-between border border-gold px-12 py-10 text-center">
                @if (! $certificate->isValid())
                    <div class="pointer-events-none absolute inset-0 grid place-items-center">
                        <span class="-rotate-12 rounded-2xl border-8 border-rose-500/60 px-10 py-4 text-6xl font-black text-rose-500/60">{{ __('REVOKED') }}</span>
                    </div>
                @endif

                <div class="flex flex-col items-center">
                    <img src="{{ asset('images/logo.jpg') }}" alt="" class="size-24 object-contain">
                    <p class="mt-2 font-latin text-2xl font-extrabold tracking-[0.2em] text-navy" dir="ltr">TALENT ACADEMY</p>
                    <p class="font-latin text-xs tracking-widest text-brand" dir="ltr">FOR EDUCATIONAL SERVICES</p>
                </div>

                <div>
                    <p class="text-4xl font-extrabold text-brand md:text-5xl">{{ app()->getLocale() === 'ar' ? 'شهادة إتمام' : 'Certificate of Completion' }}</p>
                    <p class="mt-6 text-muted">{{ __('This is to certify that') }}</p>
                    <p class="mt-3 text-4xl font-extrabold text-navy" style="font-family: Amiri, Alexandria, serif">{{ $certificate->student->displayName() }}</p>
                    <p class="mt-5 text-muted">{{ __('has successfully completed') }}</p>
                    <p class="mx-auto mt-2 max-w-2xl text-xl font-bold text-navy">{{ $certificate->tr('title') }}</p>
                    @if ($certificate->grade)
                        <p class="mt-3 text-sm">{{ __('with a grade of') }} <span class="font-bold text-brand">{{ __($certificate->grade) }}</span></p>
                    @endif
                </div>

                <div class="grid w-full grid-cols-3 items-end text-sm">
                    <div class="text-start">
                        <p class="text-muted">{{ __('Issued on') }}</p>
                        <p class="font-semibold">{{ $certificate->issued_at->translatedFormat('j F Y') }}</p>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="size-20 [&>svg]:size-full" role="img" aria-label="{{ __('Verification QR code') }}">{!! $certificate->qrSvg() !!}</div>
                        <p class="mt-1 font-latin text-[11px] font-semibold tracking-wider text-navy" dir="ltr">{{ $certificate->serial }}</p>
                    </div>
                    <div class="text-end">
                        <p class="border-b border-ink pb-1 font-bold">{{ \App\Models\Setting::tr('director_name') }}</p>
                        <p class="mt-1 text-muted">{{ __('Academy Director') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <p class="no-print mt-4 text-center text-xs text-muted">{{ __('Verify at') }} <a href="{{ $certificate->verificationUrl() }}" class="text-brand" dir="ltr">{{ $certificate->verificationUrl() }}</a></p>
@endsection
