<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir ?? 'rtl' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ __('Talent Academy') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&family=Amiri:wght@700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            @page { margin: 10mm; }
        }
    </style>
</head>
<body class="bg-canvas py-8 print:py-0">
    <div class="no-print mx-auto mb-6 flex max-w-4xl justify-between px-4">
        <a href="{{ url()->previous() }}" class="btn btn-outline"><x-lucide-arrow-right class="size-4 ltr:rotate-180" />{{ __('Back') }}</a>
        <button onclick="window.print()" class="btn btn-primary"><x-lucide-printer class="size-4" />{{ __('Print') }}</button>
    </div>
    @yield('content')
</body>
</html>
