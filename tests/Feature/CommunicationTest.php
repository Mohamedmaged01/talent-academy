<?php

namespace Tests\Feature;

use App\Models\MessageThread;
use App\Models\SupportTicket;
use App\Support\MessagingDirectory;
use App\Support\Notify;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsFixtures;
use Tests\TestCase;

class CommunicationTest extends TestCase
{
    use BuildsFixtures, RefreshDatabase;

    public function test_messaging_follows_the_class_relationships(): void
    {
        $teacher = $this->makeUser('teacher');
        $otherTeacher = $this->makeUser('teacher');
        $batch = $this->makeBatch($teacher);
        $student = $this->makeStudent([$batch]);
        $parent = $this->makeUser('parent');
        $parent->children()->attach($student->id, ['relationship' => 'father']);
        $admin = $this->makeUser('admin');
        $directory = app(MessagingDirectory::class);

        $this->assertTrue($directory->canMessage($student->user, $teacher));
        $this->assertTrue($directory->canMessage($student->user, $admin));
        $this->assertFalse($directory->canMessage($student->user, $otherTeacher));
        $this->assertTrue($directory->canMessage($parent, $teacher));
        $this->assertTrue($directory->canMessage($teacher, $parent));
        $this->assertFalse($directory->canMessage($parent, $otherTeacher));

        $this->actingAs($student->user)->post(route('messages.store'), ['recipient_id' => $otherTeacher->id, 'subject' => 'Hi', 'body' => 'Hello'])
            ->assertSessionHasErrors('recipient_id');
    }

    public function test_conversation_is_private_and_notifies_the_recipient(): void
    {
        $teacher = $this->makeUser('teacher');
        $student = $this->makeStudent([$this->makeBatch($teacher)]);
        $intruder = $this->makeUser('admin');

        $this->actingAs($student->user)->post(route('messages.store'), ['recipient_id' => $teacher->id, 'subject' => 'Homework', 'body' => 'Question about task 2'])->assertRedirect();
        $thread = MessageThread::firstWhere('subject', 'Homework');

        $this->assertSame(1, $teacher->unreadThreadsCount());
        $this->assertSame(1, $teacher->unreadNotifications()->count());

        $this->actingAs($teacher)->get(route('messages.show', $thread))->assertOk()->assertSee('Question about task 2');
        $this->assertSame(0, $teacher->fresh()->unreadThreadsCount());

        $this->actingAs($teacher)->post(route('messages.reply', $thread), ['body' => 'Answer'])->assertRedirect();
        $this->assertSame(1, $student->user->unreadThreadsCount());

        // Even an administrator cannot read a conversation they are not part of.
        $this->actingAs($intruder)->get(route('messages.show', $thread))->assertForbidden();
    }

    public function test_notification_link_marks_it_read_and_stays_on_site(): void
    {
        $teacher = $this->makeUser('teacher');
        $student = $this->makeStudent([$this->makeBatch($teacher)]);
        Notify::users($teacher, 'New message from :name: :subject', ['name' => 'X', 'subject' => 'Y'], 'https://evil.example/phish', 'mail');
        Notify::users($teacher, 'New message from :name: :subject', ['name' => 'X', 'subject' => 'Z'], route('messages.index'), 'mail');

        $all = $teacher->notifications()->get();
        $latest = $all->first(fn ($n) => $n->data['params']['subject'] === 'Z');
        $external = $all->first(fn ($n) => $n->data['params']['subject'] === 'Y');

        $this->actingAs($teacher)->get(route('notifications.open', $latest->id))->assertRedirect('/messages');
        $this->assertNotNull($latest->fresh()->read_at);

        // Stored links are reduced to a path, so a notification can never send people off-site.
        $this->actingAs($teacher)->get(route('notifications.open', $external->id))->assertRedirect('/phish');

        $this->actingAs($student->user)->get(route('notifications.open', $latest->id))->assertNotFound();
    }

    public function test_support_ticket_flow(): void
    {
        $parent = $this->makeUser('parent');
        $admin = $this->makeUser('admin');

        $this->actingAs($parent)->post(route('support.store'), ['subject' => 'Fees', 'category' => 'financial', 'priority' => 'normal', 'body' => 'Can I split the payment?'])->assertRedirect();
        $ticket = SupportTicket::first();
        $this->assertSame('open', $ticket->status);
        $this->assertSame(1, $admin->unreadNotifications()->count());

        $this->actingAs($admin)->post(route('admin.support.reply', $ticket), ['body' => 'Yes, two parts.', 'status' => 'resolved'])->assertRedirect();
        $this->assertSame('resolved', $ticket->fresh()->status);
        $this->assertSame(1, $parent->unreadNotifications()->count());

        // A follow-up from the requester re-opens it.
        $this->actingAs($parent)->post(route('support.reply', $ticket), ['body' => 'Thanks, one more question'])->assertRedirect();
        $this->assertSame('open', $ticket->fresh()->status);

        $this->actingAs($this->makeUser('parent'))->get(route('support.show', $ticket))->assertForbidden();
    }

    public function test_admin_broadcast_creates_one_private_conversation_per_recipient(): void
    {
        $admin = $this->makeUser('admin');
        $teachers = collect([$this->makeUser('teacher'), $this->makeUser('teacher'), $this->makeUser('teacher', ['is_active' => false])]);

        $this->actingAs($admin)->post(route('admin.broadcast.store'), [
            'audience' => 'teachers', 'subject' => 'Meeting', 'body' => 'Thursday 3pm', 'save_template' => 1, 'template_name' => 'Meeting',
        ])->assertRedirect(route('messages.index'));

        $this->assertSame(2, MessageThread::where('subject', 'Meeting')->count());
        $this->assertSame(1, $teachers[0]->messageThreads()->count());
        $this->assertSame(0, $teachers[2]->messageThreads()->count());
        $this->assertDatabaseHas('message_templates', ['name' => 'Meeting']);
    }
}
