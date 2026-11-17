@extends('layouts.portal')

@section('title', __('Announcements'))

@section('content')
    <x-page-header :title="__('Announcements')" />

    <div class="mx-auto max-w-3xl space-y-4">
        @forelse ($announcements as $announcement)
            @include('portal._announcement', ['announcement' => $announcement])
        @empty
            <div class="card"><x-empty icon="megaphone" :title="__('No announcements yet')" /></div>
        @endforelse
        <div class="card">{{ $announcements->links() }}</div>
    </div>
@endsection
