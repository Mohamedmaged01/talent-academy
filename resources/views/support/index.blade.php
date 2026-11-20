@extends('layouts.portal')

@section('title', __('Help & Support'))

@section('content')
    <x-page-header :title="__('Help & Support')" :subtitle="__('Ask the academy for help with your account, classes, fees or anything technical.')">
        <a href="{{ route('support.create') }}" class="btn btn-primary"><x-lucide-plus class="size-4" />{{ __('New ticket') }}</a>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">{{ __('My tickets') }}</h2></div>
            <ul class="divide-y divide-line">
                @forelse ($tickets as $ticket)
                    <li>
                        <a href="{{ route('support.show', $ticket) }}" class="flex flex-wrap items-center gap-3 px-5 py-4 hover:bg-brand-50/40">
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-navy">{{ $ticket->subject }}</p>
                                <p class="text-xs text-muted"><span class="font-latin" dir="ltr">{{ $ticket->number }}</span> · {{ __('ticket.'.$ticket->category) }} · {{ trans_choice(':count reply|:count replies', $ticket->replies_count, ['count' => $ticket->replies_count]) }}</p>
                            </div>
                            <span class="text-xs text-muted">{{ $ticket->last_reply_at?->diffForHumans() }}</span>
                            <x-status :value="$ticket->status" />
                        </a>
                    </li>
                @empty
                    <li><x-empty icon="life-buoy" :title="__('No tickets yet')" :message="__('Open a ticket and the administration will reply here.')" /></li>
                @endforelse
            </ul>
            {{ $tickets->links() }}
        </div>

        <div class="card h-fit">
            <div class="card-header"><h2 class="card-title">{{ __('Other ways to reach us') }}</h2></div>
            <ul class="space-y-3 p-5 text-sm">
                <li class="flex items-center gap-2"><x-lucide-phone class="size-4 text-brand" /><span dir="ltr">{{ \App\Models\Setting::get('contact_phone_1') }}</span></li>
                <li class="flex items-center gap-2"><x-lucide-message-circle class="size-4 text-emerald-600" /><a href="https://wa.me/{{ \App\Models\Setting::get('contact_whatsapp') }}" target="_blank" rel="noopener" class="hover:text-brand">WhatsApp</a></li>
                <li class="flex items-center gap-2"><x-lucide-mail class="size-4 text-brand" /><span dir="ltr">{{ \App\Models\Setting::get('contact_email') }}</span></li>
                <li class="flex items-center gap-2"><x-lucide-clock class="size-4 text-brand" />{{ \App\Models\Setting::tr('working_hours') }}</li>
            </ul>
        </div>
    </div>
@endsection
