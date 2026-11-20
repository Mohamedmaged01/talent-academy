@extends('layouts.portal')

@section('title', __('Delegations & Access'))

@section('content')
    <x-page-header :title="__('Delegations & Access')" :subtitle="__('Lend permissions temporarily, or ask for access you need for a limited time.')" />

    @php($me = auth()->user())

    @if ($requests->isNotEmpty())
        <div class="card mb-6">
            <div class="card-header"><h2 class="card-title">{{ __('Pending access requests') }}</h2><span class="badge bg-amber-50 text-amber-700">{{ $requests->count() }}</span></div>
            <ul class="divide-y divide-line">
                @foreach ($requests as $req)
                    <li class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center">
                        <div class="min-w-0 flex-1 text-sm">
                            <p class="font-semibold text-navy">{{ $req->grantee->name }} · <span class="font-normal text-muted">{{ trans_choice(':count day|:count days', $req->requested_days, ['count' => $req->requested_days]) }}</span></p>
                            <p class="text-muted">{{ $req->reason }}</p>
                            <div class="mt-1 flex flex-wrap gap-1">@foreach ($req->permissions as $perm)<span class="badge bg-slate-100 text-slate-600">{{ permission_label($perm) }}</span>@endforeach</div>
                        </div>
                        @if ($canManage && ! $req->grantee->is($me))
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('admin.delegations.approve', $req) }}">@csrf<button class="btn btn-primary btn-sm"><x-lucide-check class="size-4" />{{ __('Approve') }}</button></form>
                                <form method="POST" action="{{ route('admin.delegations.reject', $req) }}">@csrf<button class="btn btn-danger-soft btn-sm">{{ __('Reject') }}</button></form>
                            </div>
                        @else
                            <x-status value="pending" />
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-2">
        @if ($canManage)
            <form method="POST" action="{{ route('admin.delegations.store') }}" class="card">
                @csrf
                <div class="card-header"><h2 class="card-title">{{ __('Delegate permissions') }}</h2></div>
                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <x-select name="to_user_id" :label="__('To administrator')" required class="sm:col-span-2" :options="$colleagues->mapWithKeys(fn ($u) => [$u->id => $u->name.' — '.($u->adminRole?->tr('name'))])" :placeholder="__('Select…')"
                              :hint="__('Only administrators with a limited role are listed; full administrators already have everything.')" />
                    <x-input name="starts_at" :label="__('From')" type="datetime-local" :value="now()->format('Y-m-d\TH:i')" />
                    <x-input name="ends_at" :label="__('Until')" type="datetime-local" required :value="now()->addWeek()->format('Y-m-d\TH:i')" />
                    <x-input name="reason" :label="__('Reason')" required class="sm:col-span-2" :placeholder="__('e.g. covering the finance office during annual leave')" />
                    <div class="sm:col-span-2">
                        <p class="label">{{ __('Permissions') }}</p>
                        @include('admin.roles._matrix', ['selected' => old('permissions', []), 'holdable' => $mine])
                        @error('permissions')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <button class="btn btn-primary sm:col-span-2"><x-lucide-key-round class="size-4" />{{ __('Delegate') }}</button>
                </div>
            </form>
        @endif

        @unless ($me->isSuperAdmin())
            <form method="POST" action="{{ route('admin.delegations.request') }}" class="card h-fit">
                @csrf
                <div class="card-header"><h2 class="card-title">{{ __('Request access') }}</h2></div>
                <div class="grid gap-4 p-5">
                    <x-input name="requested_days" :label="__('For how many days?')" type="number" min="1" max="90" value="7" required />
                    <x-input name="reason" :label="__('Why do you need it?')" required />
                    <div>
                        <p class="label">{{ __('Permissions you need') }}</p>
                        @include('admin.roles._matrix', ['selected' => [], 'holdable' => array_values(array_diff(\App\Support\Permissions::all(), $mine))])
                    </div>
                    <button class="btn btn-outline"><x-lucide-send class="size-4 rtl:-scale-x-100" />{{ __('Send request') }}</button>
                </div>
            </form>
        @endunless
    </div>

    <div class="card mt-6">
        <div class="card-header"><h2 class="card-title">{{ __('Delegation history') }}</h2></div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('To') }}</th><th>{{ __('From') }}</th><th>{{ __('Permissions') }}</th><th>{{ __('Period') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse ($delegations as $delegation)
                        <tr>
                            <td class="font-semibold text-navy">{{ $delegation->grantee->name }}</td>
                            <td class="text-muted">{{ $delegation->grantor?->name ?? '—' }}<p class="max-w-xs text-xs">{{ $delegation->reason }}</p></td>
                            <td><div class="flex max-w-sm flex-wrap gap-1">@foreach ($delegation->permissions as $perm)<span class="badge bg-slate-100 text-slate-600">{{ permission_label($perm) }}</span>@endforeach</div></td>
                            <td class="text-xs whitespace-nowrap text-muted">{{ $delegation->starts_at?->translatedFormat('j M H:i') }} → {{ $delegation->ends_at?->translatedFormat('j M Y H:i') }}</td>
                            <td><x-status :value="$delegation->displayStatus()" /></td>
                            <td class="text-end">
                                @if (in_array($delegation->displayStatus(), ['active', 'scheduled'], true) && ($canManage || $delegation->from_user_id === $me->id))
                                    <form method="POST" action="{{ route('admin.delegations.revoke', $delegation) }}" onsubmit="return confirm(@js(__('Revoke this delegation now?')))">@csrf<button class="btn btn-danger-soft btn-sm">{{ __('Revoke') }}</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty icon="key-round" :title="__('No delegations yet')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $delegations->links() }}
    </div>
@endsection
