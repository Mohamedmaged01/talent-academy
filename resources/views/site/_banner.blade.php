<section class="relative overflow-hidden bg-gradient-to-l from-brand to-brand-dark">
    <div class="absolute -top-24 end-0 size-72 rounded-full bg-gold/10 blur-3xl"></div>
    <div class="relative mx-auto max-w-7xl px-6 py-14 text-white">
        <h1 class="text-3xl font-extrabold md:text-4xl">{{ $title }}</h1>
        @isset($subtitle)
            <p class="mt-3 max-w-2xl text-slate-200">{{ $subtitle }}</p>
        @endisset
    </div>
</section>
