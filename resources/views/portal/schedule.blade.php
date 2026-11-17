@extends('layouts.portal')

@section('title', __('My Schedule'))

@section('content')
    <x-page-header :title="__('My Weekly Schedule')" :subtitle="__('Today is highlighted.')" />
    @include('portal._week', ['slots' => $slots, 'days' => $days, 'showTeacher' => $showTeacher])
@endsection
