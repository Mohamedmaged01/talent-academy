@extends('layouts.portal')

@section('title', __('Downloads Center'))

@section('content')
    <x-page-header :title="__('Downloads Center')" :subtitle="__('Forms, guides, schedules and brochures.')" />
    @include('downloads._list')
@endsection
