<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Support tickets for teachers, students and parents. Each user sees only their own tickets. */
class SupportController extends Controller
{
    public function index(Request $request): View
    {
        return view('support.index', [
            'tickets' => $request->user()->supportTickets()->withCount('replies')->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('support.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(SupportTicket::CATEGORIES)],
            'priority' => ['required', Rule::in(SupportTicket::PRIORITIES)],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $ticket = $request->user()->supportTickets()->create($data + ['number' => SupportTicket::nextNumber(), 'status' => 'open', 'last_reply_at' => now()]);

        Notify::admins('New support ticket :number from :name: :subject', ['number' => $ticket->number, 'name' => $request->user()->name, 'subject' => $ticket->subject],
            route('admin.support.show', $ticket), 'life-buoy', 'support');

        return redirect()->route('support.show', $ticket)->with('success', __('Ticket :number opened. We will reply here.', ['number' => $ticket->number]));
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        $this->authorizeTicket($request, $ticket);

        return view('support.show', ['ticket' => $ticket->load('replies.user')]);
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorizeTicket($request, $ticket);

        if ($ticket->status === 'closed') {
            return back()->with('error', __('This ticket is closed. Open a new one if you still need help.'));
        }

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $ticket->replies()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);
        // A reply from the user re-opens a resolved ticket.
        $ticket->update(['last_reply_at' => now(), 'status' => $ticket->status === 'resolved' ? 'open' : $ticket->status]);

        Notify::admins('New reply on ticket :number', ['number' => $ticket->number], route('admin.support.show', $ticket), 'life-buoy', 'support');

        return back()->with('success', __('Reply sent.'));
    }

    public function close(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorizeTicket($request, $ticket);
        $ticket->update(['status' => 'closed', 'closed_at' => now()]);

        return back()->with('success', __('Ticket closed.'));
    }

    private function authorizeTicket(Request $request, SupportTicket $ticket): void
    {
        abort_unless((int) $ticket->user_id === (int) $request->user()->id, 403);
    }
}
