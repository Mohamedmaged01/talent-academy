@extends('layouts.portal')

@section('title', __('General Ledger'))

@section('content')
    <x-page-header :title="__('General Ledger')" :subtitle="__('Double-entry journal. Invoices, payments and expenses post here automatically.')">
        @if (auth()->user()->canAdmin('ledger', 'manage'))
            <a href="{{ route('admin.ledger.create') }}" class="btn btn-primary"><x-lucide-plus class="size-4" />{{ __('Manual entry') }}</a>
        @endif
    </x-page-header>

    @include('admin.ledger._tabs')

    <form method="GET" class="card mb-6 grid gap-3 p-4 sm:grid-cols-5">
        <x-input name="from" :label="__('From')" type="date" :value="$from->toDateString()" />
        <x-input name="to" :label="__('To')" type="date" :value="$to->toDateString()" />
        <x-select name="account" :label="__('Account')" :value="request('account')" :options="$accounts->mapWithKeys(fn ($a) => [$a->id => $a->label()])" :placeholder="__('All accounts')" />
        <x-select name="kind" :label="__('Type')" :value="request('kind')" :options="collect(\App\Models\JournalEntry::KINDS)->mapWithKeys(fn ($k) => [$k => __('journal.'.$k)])" :placeholder="__('All')" />
        <div class="flex items-end"><button class="btn btn-primary w-full"><x-lucide-filter class="size-4" />{{ __('Apply') }}</button></div>
    </form>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('Entry') }}</th><th>{{ __('Date') }}</th><th>{{ __('Description') }}</th><th>{{ __('Account') }}</th><th class="text-end">{{ __('Debit') }}</th><th class="text-end">{{ __('Credit') }}</th><th></th></tr></thead>
                @forelse ($entries as $entry)
                    <tbody class="border-t-2 border-line">
                        @foreach ($entry->lines as $line)
                            <tr>
                                @if ($loop->first)
                                    <td rowspan="{{ $entry->lines->count() }}" class="align-top">
                                        <p class="font-latin text-xs font-semibold text-navy" dir="ltr">{{ $entry->number }}</p>
                                        <span @class(['badge mt-1', 'bg-slate-100 text-slate-600' => $entry->kind === 'auto', 'bg-amber-50 text-amber-700' => $entry->kind === 'reversal', 'bg-violet-50 text-violet-700' => $entry->kind === 'manual'])>{{ __('journal.'.$entry->kind) }}</span>
                                    </td>
                                    <td rowspan="{{ $entry->lines->count() }}" class="align-top text-xs whitespace-nowrap text-muted">{{ $entry->entry_date->translatedFormat('j M Y') }}</td>
                                    <td rowspan="{{ $entry->lines->count() }}" class="max-w-xs align-top text-sm">{{ $entry->description }}<p class="text-xs text-muted">{{ $entry->poster?->name }}</p></td>
                                @endif
                                <td class="text-sm @if ((float) $line->credit > 0) ps-8 @endif">
                                    <a href="{{ route('admin.ledger.accounts.show', $line->account) }}" class="hover:text-brand">{{ $line->account->label() }}</a>
                                    @if ($line->memo)<p class="text-xs text-muted">{{ $line->memo }}</p>@endif
                                </td>
                                <td class="text-end tabular-nums">{{ (float) $line->debit ? number_format($line->debit, 2) : '' }}</td>
                                <td class="text-end tabular-nums">{{ (float) $line->credit ? number_format($line->credit, 2) : '' }}</td>
                                @if ($loop->first)
                                    <td rowspan="{{ $entry->lines->count() }}" class="align-top text-end">
                                        @if ($entry->kind === 'manual' && auth()->user()->canAdmin('ledger', 'manage'))
                                            <x-confirm-delete :action="route('admin.ledger.reverse', $entry)" method="POST" icon="undo-2" :label="__('Reverse')" :message="__('Post a reversing entry that cancels this one?')" />
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                @empty
                    <tbody><tr><td colspan="7"><x-empty icon="book-open-check" :title="__('No journal entries in this period')" /></td></tr></tbody>
                @endforelse
            </table>
        </div>
        {{ $entries->links() }}
    </div>
@endsection
