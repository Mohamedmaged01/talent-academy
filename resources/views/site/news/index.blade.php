@extends('layouts.site')

@section('title', __('News & Blog'))

@section('content')
    @include('site._banner', ['title' => __('Latest News & Blog'), 'subtitle' => __('Announcements, new programs and stories from Talent Academy.')])

    <section class="mx-auto max-w-7xl px-6 py-14">
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($articles as $article)
                @include('site.news._card', ['article' => $article])
            @empty
                <x-empty icon="newspaper" :title="__('No news yet.')" class="sm:col-span-3" />
            @endforelse
        </div>
        <div class="card mt-8">{{ $articles->links() }}</div>
    </section>
@endsection
