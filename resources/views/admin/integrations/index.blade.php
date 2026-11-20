@extends('layouts.portal')

@section('title', __('Integrations'))

@section('content')
    @php($manage = auth()->user()->canAdmin('settings', 'manage'))
    <x-page-header :title="__('Integrations')" :subtitle="__('Send platform events to other systems (accounting software, CRMs, WhatsApp gateways, Zapier, Make…) as signed webhooks.')" />

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-4 xl:col-span-2">
            @forelse ($webhooks as $webhook)
                <div class="card" x-data="{ edit: false, secret: false }">
                    <div class="flex flex-wrap items-start gap-3 p-5">
                        <span @class(['grid size-10 shrink-0 place-items-center rounded-xl', 'bg-emerald-50 text-emerald-600' => $webhook->is_active, 'bg-slate-100 text-slate-500' => ! $webhook->is_active])><x-lucide-webhook class="size-5" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-navy">{{ $webhook->name }} <x-status :value="$webhook->is_active ? 'active' : 'inactive'" /></p>
                            <p class="truncate font-latin text-xs text-muted" dir="ltr">{{ $webhook->url }}</p>
                            <div class="mt-2 flex flex-wrap gap-1">@foreach ($webhook->events as $event)<span class="badge bg-brand-50 font-latin text-brand" dir="ltr">{{ $event }}</span>@endforeach</div>
                            <p class="mt-2 text-xs text-muted">
                                {{ trans_choice(':count delivery|:count deliveries', $webhook->deliveries_count, ['count' => $webhook->deliveries_count]) }}
                                @if ($webhook->failed_count) · <span class="text-rose-600">{{ __(':count failed', ['count' => $webhook->failed_count]) }}</span>@endif
                                @if ($webhook->last_delivered_at) · {{ __('Last: :when (HTTP :status)', ['when' => $webhook->last_delivered_at->diffForHumans(), 'status' => $webhook->last_status ?? '—']) }}@endif
                            </p>
                        </div>
                        @if ($manage)
                            <div class="flex flex-wrap gap-1">
                                <form method="POST" action="{{ route('admin.integrations.test', $webhook) }}">@csrf<button class="btn btn-outline btn-sm"><x-lucide-send class="size-4 rtl:-scale-x-100" />{{ __('Send test') }}</button></form>
                                <button type="button" class="btn btn-ghost btn-sm" @click="secret = ! secret" title="{{ __('Signing secret') }}"><x-lucide-key-round class="size-4" /></button>
                                <button type="button" class="btn btn-ghost btn-sm" @click="edit = ! edit" title="{{ __('Edit') }}"><x-lucide-pencil class="size-4" /></button>
                                <x-confirm-delete :action="route('admin.integrations.destroy', $webhook)" />
                            </div>
                        @endif
                    </div>
                    @if ($manage)
                        <div x-show="secret" x-cloak class="flex flex-wrap items-center gap-3 border-t border-line bg-canvas px-5 py-3 text-xs">
                            <span class="text-muted">{{ __('Signing secret') }}:</span>
                            <code class="font-latin select-all" dir="ltr">{{ $webhook->secret }}</code>
                            <form method="POST" action="{{ route('admin.integrations.rotate', $webhook) }}" class="ms-auto" onsubmit="return confirm(@js(__('Generate a new secret? The receiving system must be updated.')))">@csrf<button class="btn btn-ghost btn-sm"><x-lucide-refresh-cw class="size-4" />{{ __('Rotate') }}</button></form>
                        </div>
                        <form method="POST" action="{{ route('admin.integrations.update', $webhook) }}" x-show="edit" x-cloak class="grid gap-3 border-t border-line p-5 sm:grid-cols-2">
                            @csrf @method('PUT')
                            <x-input name="name" :label="__('Name')" :value="$webhook->name" required />
                            <x-input name="url" label="URL" type="url" :value="$webhook->url" required dir="ltr" />
                            <div class="sm:col-span-2">
                                <p class="label">{{ __('Events') }}</p>
                                <div class="grid gap-1 sm:grid-cols-2">
                                    @foreach (\App\Models\Webhook::EVENTS as $event => $label)
                                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="events[]" value="{{ $event }}" @checked($webhook->listensTo($event))> {{ __($label) }}</label>
                                    @endforeach
                                </div>
                            </div>
                            <label class="flex items-center gap-2 text-sm"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($webhook->is_active)> {{ __('Active') }}</label>
                            <div class="flex justify-end"><button class="btn btn-primary btn-sm"><x-lucide-save class="size-4" />{{ __('Save') }}</button></div>
                        </form>
                    @endif
                </div>
            @empty
                <div class="card"><x-empty icon="webhook" :title="__('No webhooks yet')" :message="__('Add one to push new applications, payments or certificates to another system automatically.')" /></div>
            @endforelse

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Recent deliveries') }}</h2></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>{{ __('When') }}</th><th>{{ __('Webhook') }}</th><th>{{ __('Event') }}</th><th>{{ __('Result') }}</th><th>{{ __('Time') }}</th></tr></thead>
                        <tbody>
                            @forelse ($deliveries as $delivery)
                                <tr>
                                    <td class="text-xs whitespace-nowrap text-muted">{{ $delivery->created_at?->translatedFormat('j M H:i:s') }}</td>
                                    <td>{{ $delivery->webhook->name }}</td>
                                    <td class="font-latin text-xs" dir="ltr">{{ $delivery->event }}</td>
                                    <td>
                                        <x-status :value="$delivery->success ? 'success' : 'failed'" />
                                        <span class="font-latin text-xs text-muted" dir="ltr">{{ $delivery->status_code ? 'HTTP '.$delivery->status_code : \Illuminate\Support\Str::limit($delivery->response_excerpt, 40) }}</span>
                                    </td>
                                    <td class="text-xs tabular-nums text-muted">{{ $delivery->duration_ms }} ms</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-6 text-center text-sm text-muted">{{ __('Nothing sent yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            @if ($manage)
                <form method="POST" action="{{ route('admin.integrations.store') }}" class="card">
                    @csrf
                    <div class="card-header"><h2 class="card-title">{{ __('Add webhook') }}</h2></div>
                    <div class="grid gap-3 p-5">
                        <x-input name="name" :label="__('Name')" required :placeholder="__('e.g. Accounting system')" />
                        <x-input name="url" label="URL" type="url" required dir="ltr" placeholder="https://" />
                        <div>
                            <p class="label">{{ __('Events') }}</p>
                            @foreach (\App\Models\Webhook::EVENTS as $event => $label)
                                <label class="flex items-center gap-2 py-0.5 text-sm"><input type="checkbox" name="events[]" value="{{ $event }}" @checked(in_array($event, old('events', []), true))> {{ __($label) }}</label>
                            @endforeach
                            @error('events')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <button class="btn btn-primary"><x-lucide-plus class="size-4" />{{ __('Add webhook') }}</button>
                    </div>
                </form>
            @endif

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('For developers') }}</h2></div>
                <div class="space-y-2 p-5 text-xs leading-6 text-muted">
                    <p>{{ __('Each event is POSTed as JSON:') }}</p>
                    <pre class="overflow-x-auto rounded-lg bg-navy p-3 font-latin text-[11px] text-slate-100" dir="ltr">{"id": "…uuid…",
 "event": "payment.received",
 "occurred_at": "2026-11-20T10:00:00+02:00",
 "data": { … }}</pre>
                    <p>{{ __('Verify the X-Talent-Signature header: sha256= followed by the HMAC-SHA256 of the raw body, keyed with the signing secret.') }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
