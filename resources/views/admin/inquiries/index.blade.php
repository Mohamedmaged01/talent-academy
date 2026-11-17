@extends('layouts.portal')

@section('title', __('Contact Messages'))

@section('content')
    <x-page-header :title="__('Contact Messages')" :subtitle="__('Messages sent from the website contact form.')">
        @foreach (['' => __('All'), 'new' => __('New'), 'read' => __('Read'), 'archived' => __('Archived')] as $value => $label)
            <a href="{{ route('admin.inquiries.index', array_filter(['status' => $value])) }}" @class(['btn btn-sm', 'btn-primary' => request('status', '') === $value, 'btn-outline' => request('status', '') !== $value])>{{ $label }}</a>
        @endforeach
    </x-page-header>

    <div class="space-y-4">
        @forelse ($inquiries as $inquiry)
            <article @class(['card p-5', 'border-s-4 border-s-gold' => $inquiry->status === 'new'])>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-bold text-navy">{{ $inquiry->name }} @if ($inquiry->subject)<span class="font-normal text-muted">— {{ $inquiry->subject }}</span>@endif</p>
                        <p class="mt-0.5 flex flex-wrap gap-x-4 text-xs text-muted">
                            @if ($inquiry->phone)<a href="tel:{{ $inquiry->phone }}" dir="ltr" class="hover:text-brand">{{ $inquiry->phone }}</a>@endif
                            @if ($inquiry->email)<a href="mailto:{{ $inquiry->email }}" dir="ltr" class="hover:text-brand">{{ $inquiry->email }}</a>@endif
                            <span>{{ $inquiry->created_at->diffForHumans() }}</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-status :value="$inquiry->status" />
                        @foreach (['read' => ['mail-open', __('Mark as read')], 'archived' => ['archive', __('Archive')]] as $status => [$icon, $label])
                            @if ($inquiry->status !== $status)
                                <form method="POST" action="{{ route('admin.inquiries.update', $inquiry) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $status }}">
                                    <button class="btn btn-ghost btn-sm" title="{{ $label }}"><x-dynamic-component :component="'lucide-'.$icon" class="size-4" /></button>
                                </form>
                            @endif
                        @endforeach
                        <x-confirm-delete :action="route('admin.inquiries.destroy', $inquiry)" />
                    </div>
                </div>
                <p class="mt-3 whitespace-pre-line text-sm leading-7 text-ink">{{ $inquiry->message }}</p>
            </article>
        @empty
            <div class="card"><x-empty icon="inbox" :title="__('No messages')" /></div>
        @endforelse
    </div>
    <div class="card mt-5">{{ $inquiries->links() }}</div>
@endsection
