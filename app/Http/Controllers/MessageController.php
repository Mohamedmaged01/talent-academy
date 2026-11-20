<?php

namespace App\Http\Controllers;

use App\Models\MessageThread;
use App\Models\User;
use App\Support\MessagingDirectory;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Private messaging for every role. A conversation is visible only to its participants. */
class MessageController extends Controller
{
    public function __construct(private MessagingDirectory $directory) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('messages.index', [
            'threads' => $user->messageThreads()
                ->with(['participants', 'latestMessage.sender'])
                ->when($request->query('q'), fn ($q, $term) => $q->where('subject', 'like', "%{$term}%"))
                ->orderByDesc('last_message_at')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('messages.create', [
            'recipients' => $this->directory->recipientsFor($request->user()),
            'selected' => $request->query('to'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $sender = $request->user();
        $recipient = User::findOrFail($data['recipient_id']);

        if (! $this->directory->canMessage($sender, $recipient)) {
            throw ValidationException::withMessages(['recipient_id' => __('You cannot message this person.')]);
        }

        $thread = $this->startThread($sender, [$recipient], $data['subject'], $data['body']);

        return redirect()->route('messages.show', $thread)->with('success', __('Message sent.'));
    }

    public function show(Request $request, MessageThread $thread): View
    {
        $user = $request->user();
        abort_unless($thread->hasParticipant($user), 403);

        $thread->markReadFor($user);

        return view('messages.show', [
            'thread' => $thread->load(['participants', 'messages.sender']),
            'others' => $thread->otherParticipants($user),
        ]);
    }

    public function reply(Request $request, MessageThread $thread): RedirectResponse
    {
        $user = $request->user();
        abort_unless($thread->hasParticipant($user), 403);

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $thread->post($user, $data['body']);

        Notify::users($thread->otherParticipants($user), 'New message from :name: :subject', ['name' => $user->name, 'subject' => $thread->subject], route('messages.show', $thread), 'mail');

        return redirect()->to(route('messages.show', $thread).'#latest');
    }

    /** Create a conversation and notify the recipients. Also used by administrator broadcasts. */
    public static function startThread(User $sender, iterable $recipients, string $subject, string $body): MessageThread
    {
        return DB::transaction(function () use ($sender, $recipients, $subject, $body) {
            $thread = MessageThread::create(['subject' => $subject, 'created_by' => $sender->id]);
            $ids = collect($recipients)->pluck('id')->push($sender->id)->unique()->all();
            $thread->participants()->attach($ids);
            $thread->post($sender, $body);

            Notify::users($recipients, 'New message from :name: :subject', ['name' => $sender->name, 'subject' => $subject], route('messages.show', $thread), 'mail');

            return $thread;
        });
    }
}
