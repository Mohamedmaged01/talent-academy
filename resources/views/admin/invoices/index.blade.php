@extends('layouts.portal')

@section('title', __('Invoices'))

@section('content')
    <x-page-header :title="__('Invoices')" :subtitle="__('Fees, installments and balances.')">
        <a href="{{ route('admin.invoices.create') }}" class="btn btn-primary"><x-lucide-file-plus class="size-4" />{{ __('New Invoice') }}</a>
    </x-page-header>

    <div class="mb-5 flex flex-wrap gap-2">
        <a href="{{ route('admin.invoices.index') }}" @class(['btn btn-sm', 'btn-primary' => ! request('status'), 'btn-outline' => request('status')])>{{ __('All') }}</a>
        @foreach (\App\Models\Invoice::STATUSES as $status)
            <a href="{{ route('admin.invoices.index', ['status' => $status]) }}" @class(['btn btn-sm', 'btn-primary' => request('status') === $status, 'btn-outline' => request('status') !== $status])>{{ __(ucfirst($status)) }}</a>
        @endforeach
    </div>

    <div class="card">
        <form method="GET" class="border-b border-line p-4">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <div class="relative max-w-md">
                <x-lucide-search class="pointer-events-none absolute top-1/2 start-3 size-4 -translate-y-1/2 text-muted" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Invoice number or student…') }}" class="input ps-9">
            </div>
        </form>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('Number') }}</th><th>{{ __('Student') }}</th><th>{{ __('Description') }}</th><th>{{ __('Due') }}</th><th>{{ __('Total') }}</th><th>{{ __('Paid') }}</th><th>{{ __('Status') }}</th></tr></thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr class="cursor-pointer" onclick="window.location='{{ route('admin.invoices.show', $invoice) }}'">
                            <td class="font-latin text-xs font-semibold text-brand" dir="ltr">{{ $invoice->number }}</td>
                            <td><p class="font-medium">{{ $invoice->student->full_name }}</p><p class="text-xs text-muted">{{ $invoice->branch->tr('name') }}</p></td>
                            <td class="max-w-64 truncate text-muted">{{ $invoice->description }}</td>
                            <td @class(['text-xs', 'text-rose-600 font-semibold' => $invoice->status === 'overdue', 'text-muted' => $invoice->status !== 'overdue'])>{{ $invoice->due_date->translatedFormat('j M Y') }}</td>
                            <td class="tabular-nums">{{ money($invoice->total(), false) }}</td>
                            <td class="tabular-nums text-emerald-700">{{ money($invoice->paid_amount, false) }}</td>
                            <td><x-status :value="$invoice->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty icon="file-text" :title="__('No invoices found')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $invoices->links() }}
    </div>
@endsection
