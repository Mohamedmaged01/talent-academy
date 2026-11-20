<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MessageController;
use App\Models\ActivityLog;
use App\Models\Batch;
use App\Models\MessageTemplate;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Send the same private message to a group. Each recipient gets their own
 * conversation, so replies stay private between them and the administration.
 */
class BroadcastController extends Controller
{
    public const AUDIENCES = ['teachers', 'students', 'parents', 'batch_students', 'batch_parents', 'batch_all'];

    public function create(): View
    {
        return view('admin.broadcast', [
            'batches' => Batch::whereIn('status', ['active', 'upcoming'])->orderBy('name')->get(['id', 'name']),
            'templates' => MessageTemplate::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'audience' => ['required', Rule::in(self::AUDIENCES)],
            'batch_id' => ['nullable', 'required_if:audience,batch_students,batch_parents,batch_all', 'exists:batches,id'],
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'save_template' => ['nullable', 'boolean'],
            'template_name' => ['nullable', 'required_if:save_template,1', 'string', 'max:100'],
        ]);

        $recipients = $this->recipients($data['audience'], $data['batch_id'] ?? null)
            ->reject(fn (User $u) => $u->id === $request->user()->id);

        if ($recipients->isEmpty()) {
            throw ValidationException::withMessages(['audience' => __('Nobody with an active account matches this audience.')]);
        }

        foreach ($recipients as $recipient) {
            MessageController::startThread($request->user(), [$recipient], $data['subject'], $data['body']);
        }

        if ($request->boolean('save_template')) {
            MessageTemplate::create(['name' => $data['template_name'], 'subject' => $data['subject'], 'body' => $data['body'], 'created_by' => $request->user()->id]);
        }

        ActivityLog::record('message.broadcast', "Broadcast \"{$data['subject']}\" sent to {$recipients->count()} users ({$data['audience']})");

        return redirect()->route('messages.index')->with('success', trans_choice('Message sent to :count person.|Message sent to :count people.', $recipients->count(), ['count' => $recipients->count()]));
    }

    public function destroyTemplate(MessageTemplate $template): RedirectResponse
    {
        $template->delete();

        return back()->with('success', __('Template deleted.'));
    }

    /** @return Collection<int, User> */
    private function recipients(string $audience, ?int $batchId): Collection
    {
        $active = fn ($q) => $q->where('is_active', true);

        if (in_array($audience, ['teachers', 'students', 'parents'], true)) {
            return User::where('role', rtrim($audience, 's'))->where($active)->get();
        }

        $students = Batch::findOrFail($batchId)->activeStudents()->with(['user', 'guardians'])->get();
        $studentUsers = $students->map(fn (Student $s) => $s->user)->filter(fn ($u) => $u?->is_active);
        $parents = $students->flatMap(fn (Student $s) => $s->guardians)->filter(fn ($u) => $u->is_active);

        $recipients = match ($audience) {
            'batch_students' => $studentUsers,
            'batch_parents' => $parents,
            default => $studentUsers->concat($parents),
        };

        return $recipients->unique('id')->values();
    }
}
