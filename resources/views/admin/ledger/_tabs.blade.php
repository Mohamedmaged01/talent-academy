<div class="mb-6 flex flex-wrap gap-2">
    @foreach ([
        ['admin.ledger.index', 'book-open-check', __('Journal')],
        ['admin.ledger.trial-balance', 'scale', __('Trial balance')],
        ['admin.ledger.accounts', 'list-tree', __('Chart of accounts')],
    ] as [$route, $icon, $label])
        <a href="{{ route($route) }}" @class(['btn btn-sm', 'btn-primary' => request()->routeIs($route) || ($route === 'admin.ledger.accounts' && request()->routeIs('admin.ledger.accounts.*')), 'btn-outline' => ! (request()->routeIs($route) || ($route === 'admin.ledger.accounts' && request()->routeIs('admin.ledger.accounts.*')))])>
            <x-dynamic-component :component="'lucide-'.$icon" class="size-4" />{{ $label }}
        </a>
    @endforeach
</div>
