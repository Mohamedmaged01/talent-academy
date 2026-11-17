@extends('layouts.portal')

@section('title', __('Timetable'))

@section('content')
    <x-page-header :title="__('Academy Timetable')" :subtitle="__('Weekly sessions for all active and upcoming classes.')">
        <form method="GET">
            <select name="branch" class="input" onchange="this.form.submit()">
                <option value="">{{ __('All branches') }}</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) request('branch') === (string) $branch->id)>{{ $branch->tr('name') }}</option>
                @endforeach
            </select>
        </form>
    </x-page-header>

    @include('portal._week', ['slots' => $slots, 'days' => $days, 'showTeacher' => true, 'linkRoute' => 'admin.batches.show'])
@endsection
