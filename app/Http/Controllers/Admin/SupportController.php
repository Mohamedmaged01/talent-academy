<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SupportTicket;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'active');

        return view('admin.support.index', [
            'tickets' => SupportTicket::with('user')->withCount('replies')
                ->when($status === 'active', fn ($q) => $q->open())
                ->when(in_array($status, SupportTicket::STATUSES, true), fn ($q) => $q->where('status', $status))
                ->when($request->query('priority'), fn ($q, $v) => $q->where('priority', $v))
                ->orderByRaw("case priority when 'urgent' then 0 when 'high' then 1 else 2 end")
                ->orderByDesc('last_reply_at')
                ->paginate(20)
                ->withQueryString(),
            'counts' => SupportTicket::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'status' => $status,
        ]);
    }

    public function show(SupportTicket $ticket): View
    {
        return view('admin.support.show', ['ticket' => $ticket->load(['user', 'replies.user'])]);
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'status' => ['required', Rule::in(SupportTicket::STATUSES)],
        ]);

        $ticket->replies()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);
        $ticket->update([
            'status' => $data['status'],
            'last_reply_at' => now(),
            'closed_at' => in_array($data['status'], ['resolved', 'closed'], true) ? now() : null,
        ]);

        Notify::users($ticket->user, 'The academy replied to your ticket :number', ['number' => $ticket->number], route('support.show', $ticket), 'life-buoy');

        return back()->with('success', __('Reply sent.'));
    }

    public function status(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(SupportTicket::STATUSES)]]);
        $ticket->update($data + ['closed_at' => in_array($data['status'], ['resolved', 'closed'], true) ? now() : null]);
        ActivityLog::record('support.status', "Ticket {$ticket->number} set to {$data['status']}", $ticket);

        Notify::users($ticket->user, 'Your ticket :number was updated', ['number' => $ticket->number], route('support.show', $ticket), 'life-buoy');

        return back()->with('success', __('Ticket updated.'));
    }
}
