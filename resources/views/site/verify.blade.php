@extends('layouts.site')

@section('title', __('Verify Certificate'))

@section('content')
    @include('site._banner', ['title' => __('Certificate Verification'), 'subtitle' => __('Enter the serial number printed on the certificate to confirm it was issued by Talent Academy.')])

    <section class="mx-auto max-w-3xl px-6 py-12">
        <form method="GET" class="card flex flex-col gap-3 p-5 sm:flex-row">
            <label for="serial" class="sr-only">{{ __('Certificate serial number') }}</label>
            <input id="serial" name="serial" value="{{ $serial }}" required dir="ltr" placeholder="TA-CERT-2026-XXXXXX" class="input font-latin uppercase tracking-wider">
            <button class="btn btn-primary"><x-lucide-shield-check class="size-4" /> {{ __('Verify') }}</button>
        </form>

        @if ($serial !== '')
            @if ($certificate && $certificate->isValid())
                <div class="card mt-6 overflow-hidden border-emerald-200">
                    <div class="flex items-center gap-3 bg-emerald-50 px-6 py-4 text-emerald-800">
                        <x-lucide-badge-check class="size-7" />
                        <div>
                            <p class="font-bold">{{ __('Valid certificate') }}</p>
                            <p class="text-sm">{{ __('This certificate was issued by Talent Academy and is currently valid.') }}</p>
                        </div>
                    </div>
                    <dl class="grid gap-4 p-6 sm:grid-cols-2">
                        <div><dt class="text-xs text-muted">{{ __('Holder') }}</dt><dd class="font-bold text-navy">{{ $certificate->student->displayName() }}</dd></div>
                        <div><dt class="text-xs text-muted">{{ __('Serial') }}</dt><dd class="font-latin font-bold text-navy" dir="ltr">{{ $certificate->serial }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-xs text-muted">{{ __('Certificate') }}</dt><dd class="font-bold text-navy">{{ $certificate->tr('title') }}</dd></div>
                        <div><dt class="text-xs text-muted">{{ __('Issued on') }}</dt><dd class="font-semibold">{{ $certificate->issued_at->translatedFormat('j F Y') }}</dd></div>
                        @if ($certificate->grade)
                            <div><dt class="text-xs text-muted">{{ __('Grade') }}</dt><dd class="font-semibold">{{ __($certificate->grade) }}</dd></div>
                        @endif
                    </dl>
                </div>
            @elseif ($certificate)
                <div class="card mt-6 flex items-center gap-3 border-rose-200 bg-rose-50 px-6 py-5 text-rose-800">
                    <x-lucide-shield-x class="size-7 shrink-0" />
                    <div>
                        <p class="font-bold">{{ __('This certificate has been revoked') }}</p>
                        <p class="text-sm">{{ __('It is no longer valid. Contact the academy for details.') }}</p>
                    </div>
                </div>
            @else
                <div class="card mt-6 flex items-center gap-3 border-amber-200 bg-amber-50 px-6 py-5 text-amber-800">
                    <x-lucide-search-x class="size-7 shrink-0" />
                    <div>
                        <p class="font-bold">{{ __('No certificate found') }}</p>
                        <p class="text-sm">{{ __('Check the serial number and try again.') }}</p>
                    </div>
                </div>
            @endif
        @endif
    </section>
@endsection
