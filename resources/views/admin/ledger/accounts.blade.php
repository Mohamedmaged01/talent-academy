@extends('layouts.portal')

@section('title', __('Chart of accounts'))

@section('content')
    <x-page-header :title="__('Chart of accounts')" :subtitle="__('System accounts receive the automatic postings. Add your own for manual entries.')" />

    @include('admin.ledger._tabs')

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('Code') }}</th><th>{{ __('Account') }}</th><th>{{ __('Type') }}</th><th class="text-end">{{ __('Balance') }}</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($accounts as $account)
                            @php
                                $t = $totals[$account->id] ?? null;
                                $balance = $t ? ($account->isDebitNormal() ? $t['balance'] : -$t['balance']) : 0;
                            @endphp
                            <tr @class(['opacity-50' => ! $account->is_active])>
                                <td class="font-latin font-semibold" dir="ltr">{{ $account->code }}</td>
                                <td>
                                    <a href="{{ route('admin.ledger.accounts.show', $account) }}" class="font-medium text-navy hover:text-brand">{{ $account->tr('name') }}</a>
                                    @if ($account->is_system)<span class="badge bg-slate-100 text-slate-600">{{ __('System') }}</span>@endif
                                </td>
                                <td class="text-xs text-muted">{{ __('account.'.$account->type) }}</td>
                                <td class="text-end tabular-nums">{{ number_format($balance, 2) }}</td>
                                <td class="text-end">
                                    @if (! $account->is_system && auth()->user()->canAdmin('ledger', 'manage'))
                                        <form method="POST" action="{{ route('admin.ledger.accounts.toggle', $account) }}">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-ghost btn-sm">{{ $account->is_active ? __('Deactivate') : __('Activate') }}</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if (auth()->user()->canAdmin('ledger', 'manage'))
            <form method="POST" action="{{ route('admin.ledger.accounts.store') }}" class="card h-fit">
                @csrf
                <div class="card-header"><h2 class="card-title">{{ __('Add account') }}</h2></div>
                <div class="grid gap-4 p-5">
                    <x-input name="code" :label="__('Code')" required dir="ltr" :hint="__('e.g. 1020 for a second bank account')" />
                    <x-input name="name_ar" :label="__('Name (Arabic)')" required />
                    <x-input name="name_en" :label="__('Name (English)')" required dir="ltr" />
                    <x-select name="type" :label="__('Type')" required :options="collect(\App\Models\Account::TYPES)->mapWithKeys(fn ($t) => [$t => __('account.'.$t)])" />
                    <button class="btn btn-primary"><x-lucide-plus class="size-4" />{{ __('Add account') }}</button>
                </div>
            </form>
        @endif
    </div>
@endsection
