@extends('layouts.portal')

@section('title', __('Financial Overview'))

@section('content')
    <x-page-header :title="__('Financial Overview')" :subtitle="__(':from to :to', ['from' => $from->translatedFormat('j M Y'), 'to' => $to->translatedFormat('j M Y')])">
        <a href="{{ route('admin.invoices.create') }}" class="btn btn-primary"><x-lucide-file-plus class="size-4" />{{ __('New Invoice') }}</a>
    </x-page-header>

    <form method="GET" class="card mb-6 grid gap-3 p-4 sm:grid-cols-4">
        <x-input name="from" :label="__('From')" type="date" :value="$from->toDateString()" />
        <x-input name="to" :label="__('To')" type="date" :value="$to->toDateString()" />
        <x-select name="branch" :label="__('Branch')" :value="request('branch')" :options="$branches->mapWithKeys(fn ($b) => [$b->id => $b->tr('name')])" :placeholder="__('All branches')" />
        <div class="flex items-end"><button class="btn btn-primary w-full"><x-lucide-filter class="size-4" />{{ __('Apply') }}</button></div>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat :label="__('Revenue collected')" :value="money($summary['revenue'], false)" icon="trending-up" tone="emerald" />
        <x-stat :label="__('Expenses')" :value="money($summary['expenses'], false)" icon="trending-down" tone="amber" :href="route('admin.expenses.index')" />
        <x-stat :label="__('Net result')" :value="money($summary['net'], false)" icon="scale" :tone="$summary['net'] >= 0 ? 'brand' : 'rose'" />
        <x-stat :label="__('Outstanding (all open invoices)')" :value="money($summary['outstanding'], false)" icon="circle-alert" tone="rose"
                :hint="__(':amount overdue · :count open invoices', ['amount' => money($summary['overdue'], false), 'count' => $summary['invoices_open']])" :href="route('admin.invoices.index', ['status' => 'overdue'])" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Collections by method') }}</h2></div>
            <ul class="space-y-3 p-5 text-sm">
                @forelse ($byMethod as $method => $amount)
                    <li>
                        <div class="flex justify-between"><span>{{ __(\Illuminate\Support\Str::headline($method)) }}</span><span class="font-semibold tabular-nums">{{ money($amount, false) }}</span></div>
                        <div class="mt-1.5 h-1.5 rounded-full bg-line"><div class="h-1.5 rounded-full bg-brand" style="width: {{ $summary['revenue'] ? round($amount / $summary['revenue'] * 100) : 0 }}%"></div></div>
                    </li>
                @empty
                    <li class="text-muted">{{ __('No payments in this period.') }}</li>
                @endforelse
            </ul>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Expenses by category') }}</h2></div>
            <ul class="space-y-3 p-5 text-sm">
                @forelse ($byCategory as $category => $amount)
                    <li>
                        <div class="flex justify-between"><span>{{ __(ucfirst($category)) }}</span><span class="font-semibold tabular-nums">{{ money($amount, false) }}</span></div>
                        <div class="mt-1.5 h-1.5 rounded-full bg-line"><div class="h-1.5 rounded-full bg-gold" style="width: {{ $summary['expenses'] ? round($amount / $summary['expenses'] * 100) : 0 }}%"></div></div>
                    </li>
                @empty
                    <li class="text-muted">{{ __('No expenses in this period.') }}</li>
                @endforelse
            </ul>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('By branch') }}</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('Branch') }}</th><th>{{ __('Revenue') }}</th><th>{{ __('Expenses') }}</th></tr></thead>
                    <tbody>
                        @foreach ($byBranch as $row)
                            <tr>
                                <td class="font-medium">{{ $row['branch']->tr('name') }}</td>
                                <td class="tabular-nums text-emerald-700">{{ money($row['revenue'], false) }}</td>
                                <td class="tabular-nums text-amber-700">{{ money($row['expenses'], false) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-6">
        <div class="card-header">
            <h2 class="card-title">{{ __('Latest payments in period') }}</h2>
            <a href="{{ route('admin.payments.index') }}" class="text-xs font-semibold text-brand">{{ __('View all') }}</a>
        </div>
        @include('admin.payments._table', ['payments' => $recentPayments])
    </div>
@endsection
