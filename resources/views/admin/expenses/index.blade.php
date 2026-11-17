@extends('layouts.portal')

@section('title', __('Expenses'))

@section('content')
    <x-page-header :title="__('Expenses')" :subtitle="__('Operating costs per branch.')" />

    <div class="grid gap-6 xl:grid-cols-3">
        <form method="POST" action="{{ route('admin.expenses.store') }}" class="card h-fit">
            @csrf
            <div class="card-header"><h2 class="card-title">{{ __('Record expense') }}</h2></div>
            <div class="grid gap-4 p-5">
                <x-select name="branch_id" :label="__('Branch')" required :options="$branches->mapWithKeys(fn ($b) => [$b->id => $b->tr('name')])" />
                <x-select name="category" :label="__('Category')" required :options="collect(\App\Models\Expense::CATEGORIES)->mapWithKeys(fn ($c) => [$c => __(ucfirst($c))])" />
                <x-input name="description" :label="__('Description')" required />
                <x-input name="amount" :label="__('Amount (EGP)')" type="number" step="0.01" min="0.01" required />
                <x-input name="spent_at" :label="__('Date')" type="date" :value="now()->toDateString()" required />
                <button class="btn btn-primary"><x-lucide-plus class="size-4" />{{ __('Save') }}</button>
            </div>
        </form>

        <div class="card xl:col-span-2">
            <form method="GET" class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row">
                <select name="category" class="input" onchange="this.form.submit()">
                    <option value="">{{ __('All categories') }}</option>
                    @foreach (\App\Models\Expense::CATEGORIES as $category)
                        <option value="{{ $category }}" @selected(request('category') === $category)>{{ __(ucfirst($category)) }}</option>
                    @endforeach
                </select>
                <select name="branch" class="input" onchange="this.form.submit()">
                    <option value="">{{ __('All branches') }}</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) request('branch') === (string) $branch->id)>{{ $branch->tr('name') }}</option>
                    @endforeach
                </select>
            </form>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Category') }}</th><th>{{ __('Description') }}</th><th>{{ __('Branch') }}</th><th>{{ __('Amount') }}</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($expenses as $expense)
                            <tr>
                                <td class="text-xs text-muted">{{ $expense->spent_at->translatedFormat('j M Y') }}</td>
                                <td><span class="badge bg-amber-50 text-amber-700">{{ __(ucfirst($expense->category)) }}</span></td>
                                <td>{{ $expense->description }}<p class="text-xs text-muted">{{ $expense->recorder?->name }}</p></td>
                                <td class="text-muted">{{ $expense->branch->tr('name') }}</td>
                                <td class="font-semibold tabular-nums">{{ money($expense->amount, false) }}</td>
                                <td class="text-end"><x-confirm-delete :action="route('admin.expenses.destroy', $expense)" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-empty icon="receipt" :title="__('No expenses recorded')" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $expenses->links() }}
        </div>
    </div>
@endsection
