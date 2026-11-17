@extends('layouts.portal')

@section('title', __('Payments'))

@section('content')
    <x-page-header :title="__('Payments')" :subtitle="__('Every receipt issued, newest first.')" />

    <div class="card">
        <form method="GET" class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row">
            <div class="relative flex-1">
                <x-lucide-search class="pointer-events-none absolute top-1/2 start-3 size-4 -translate-y-1/2 text-muted" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Receipt number or student…') }}" class="input ps-9">
            </div>
            <select name="method" class="input sm:w-52" onchange="this.form.submit()">
                <option value="">{{ __('All methods') }}</option>
                @foreach (\App\Models\Payment::METHODS as $method)
                    <option value="{{ $method }}" @selected(request('method') === $method)>{{ __(\Illuminate\Support\Str::headline($method)) }}</option>
                @endforeach
            </select>
        </form>
        @include('admin.payments._table', ['payments' => $payments])
        {{ $payments->links() }}
    </div>
@endsection
