@extends('layouts.site')

@section('title', __('Downloads Center'))

@section('content')
    @include('site._banner', ['title' => __('Downloads Center'), 'subtitle' => __('Application forms, program guides, schedules and brochures.')])

    <section class="mx-auto max-w-5xl px-6 py-12">
        @include('downloads._list')
    </section>
@endsection
