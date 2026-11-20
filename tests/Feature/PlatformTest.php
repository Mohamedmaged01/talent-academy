<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\Backup;
use App\Models\Branch;
use App\Models\Inquiry;
use App\Models\LoginEvent;
use App\Models\PermissionDelegation;
use App\Models\Student;
use App\Models\Webhook;
use App\Services\BackupService;
use App\Services\IntegrityService;
use App\Support\Webhooks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\BuildsFixtures;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use BuildsFixtures, RefreshDatabase;

    private function adminWithRole(array $permissions, bool $branchScoped = false, ?int $branchId = null)
    {
        $role = AdminRole::create(['name_ar' => 'دور', 'name_en' => 'Role '.uniqid(), 'permissions' => $permissions, 'branch_scoped' => $branchScoped]);

        return $this->makeUser('admin', ['admin_role_id' => $role->id, 'branch_id' => $branchId]);
    }

    public function test_role_permissions_gate_pages_and_actions(): void
    {
        $accountant = $this->adminWithRole(['finance.manage', 'finance.view', 'students.view']);

        $this->actingAs($accountant)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($accountant)->get(route('admin.invoices.index'))->assertOk();
        $this->actingAs($accountant)->get(route('admin.students.index'))->assertOk();
        $this->actingAs($accountant)->get(route('admin.students.create'))->assertOk(); // GET pages only need "view"
        $this->actingAs($accountant)->post(route('admin.students.store'), [])->assertForbidden(); // changes need "manage"
        $this->actingAs($accountant)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($accountant)->get(route('admin.backups.index'))->assertForbidden();

        // The sidebar hides what the role cannot open.
        $this->actingAs($accountant)->get(route('admin.dashboard'))->assertSee(route('admin.invoices.index'))->assertDontSee(route('admin.settings.edit'));
    }

    public function test_delegation_grants_temporary_access_that_expires_or_can_be_revoked(): void
    {
        $registrar = $this->adminWithRole(['students.view']);
        $super = $this->makeUser('admin');

        $this->actingAs($registrar)->get(route('admin.finance'))->assertForbidden();

        $this->actingAs($super)->post(route('admin.delegations.store'), [
            'to_user_id' => $registrar->id, 'permissions' => ['finance.view'], 'ends_at' => now()->addDay()->format('Y-m-d H:i'), 'reason' => 'Cover',
        ])->assertRedirect();

        $this->actingAs($registrar->fresh())->get(route('admin.finance'))->assertOk();

        $this->travel(2)->days();
        $this->actingAs($registrar->fresh())->get(route('admin.finance'))->assertForbidden();
        $this->travelBack();

        $delegation = PermissionDelegation::first();
        $this->actingAs($super)->post(route('admin.delegations.revoke', $delegation));
        $this->assertSame('revoked', $delegation->fresh()->status);
    }

    public function test_access_request_needs_an_approver_who_holds_the_permission(): void
    {
        $requester = $this->adminWithRole(['students.view']);
        $limitedApprover = $this->adminWithRole(['users.manage', 'users.view']);
        $super = $this->makeUser('admin');

        $this->actingAs($requester)->post(route('admin.delegations.request'), ['permissions' => ['ledger.view'], 'requested_days' => 3, 'reason' => 'Audit'])->assertRedirect();
        $request = PermissionDelegation::firstWhere('status', 'pending');

        $this->actingAs($requester)->post(route('admin.delegations.approve', $request))->assertForbidden();
        $this->actingAs($limitedApprover)->post(route('admin.delegations.approve', $request))->assertSessionHasErrors('permissions');
        $this->actingAs($super)->post(route('admin.delegations.approve', $request))->assertRedirect();

        $this->assertTrue($requester->fresh()->canAdmin('ledger'));
    }

    public function test_no_privilege_escalation_through_roles(): void
    {
        $manager = $this->adminWithRole(['users.manage', 'users.view', 'students.manage', 'students.view']);
        $powerful = AdminRole::create(['name_ar' => 'x', 'name_en' => 'Finance', 'permissions' => ['finance.manage', 'finance.view']]);
        $colleague = $this->adminWithRole(['students.view']);

        $this->actingAs($manager)->post(route('admin.roles.store'), ['name_ar' => 'ن', 'name_en' => 'Mine', 'permissions' => ['finance.view']])->assertSessionHasErrors('permissions');
        $this->actingAs($manager)->patch(route('admin.roles.assign', $colleague), ['admin_role_id' => $powerful->id])->assertSessionHas('error');
        $this->actingAs($manager)->patch(route('admin.roles.assign', $colleague), ['admin_role_id' => null])->assertSessionHas('error');
        $this->actingAs($manager)->patch(route('admin.roles.assign', $manager), ['admin_role_id' => null])->assertSessionHas('error');
        $this->assertNotNull($colleague->fresh()->admin_role_id);
    }

    public function test_branch_scoped_admin_only_sees_their_branch(): void
    {
        $home = $this->branch();
        $other = Branch::create(['code' => 'T-02', 'name_ar' => 'فرع 2', 'name_en' => 'Branch 2', 'status' => 'active']);
        $mine = Student::create(['branch_id' => $home->id, 'code' => Student::nextCode(), 'full_name' => 'طالب الفرع']);
        $theirs = Student::create(['branch_id' => $other->id, 'code' => Student::nextCode(), 'full_name' => 'طالب فرع آخر']);
        $manager = $this->adminWithRole(['students.view', 'students.manage'], true, $home->id);

        $this->actingAs($manager)->get(route('admin.students.index'))->assertOk()->assertSee('طالب الفرع')->assertDontSee('طالب فرع آخر');
        $this->actingAs($manager)->get(route('admin.students.show', $mine))->assertOk();
        $this->actingAs($manager)->get(route('admin.students.show', $theirs))->assertForbidden();
    }

    public function test_backup_round_trip_restores_deleted_data_and_detects_tampering(): void
    {
        Storage::fake('local');
        $admin = $this->makeUser('admin');
        Inquiry::create(['name' => 'Keep me', 'message' => 'Hello there, I have a question.']);

        $this->actingAs($admin)->post(route('admin.backups.store'), ['label' => 'Before'])->assertRedirect();
        $backup = Backup::firstOrFail();
        $this->assertTrue(app(BackupService::class)->verify($backup));
        $this->assertSame(1, $backup->summary['inquiries']);

        Inquiry::query()->delete();
        $this->actingAs($admin)->post(route('admin.backups.restore', $backup), ['confirm' => 'wrong'])->assertSessionHasErrors('confirm');
        $this->actingAs($admin)->post(route('admin.backups.restore', $backup), ['confirm' => $backup->code])->assertRedirect(route('admin.backups.index'));

        $this->assertDatabaseHas('inquiries', ['name' => 'Keep me']);
        $this->assertSame(1, Backup::where('kind', 'pre_restore')->count());

        Storage::disk('local')->append($backup->file_path, 'tampered');
        $this->assertFalse(app(BackupService::class)->verify($backup));
    }

    public function test_webhooks_are_signed_and_logged(): void
    {
        Http::fake(['hooks.example/*' => Http::response(['ok' => true], 200)]);
        $hook = Webhook::create(['name' => 'CRM', 'url' => 'https://hooks.example/talent', 'secret' => 'shh', 'events' => ['inquiry.received'], 'is_active' => true]);

        $delivery = Webhooks::deliver($hook, 'inquiry.received', Webhooks::payload('inquiry.received', ['name' => 'A']));

        $this->assertTrue($delivery->success);
        Http::assertSent(function (HttpRequest $request) {
            return $request->hasHeader('X-Talent-Event', 'inquiry.received')
                && $request->header('X-Talent-Signature')[0] === 'sha256='.hash_hmac('sha256', $request->body(), 'shh');
        });

        // Creating a record queues a delivery for the end of the request.
        $this->post(route('contact.store'), ['name' => 'Visitor', 'email' => 'v@example.com', 'message' => 'I would like information.']);
        $this->assertSame(2, $hook->deliveries()->count());
    }

    public function test_assistant_sends_aggregates_only_and_handles_missing_key(): void
    {
        $admin = $this->makeUser('admin');
        $this->makeStudent();

        config(['services.gemini.key' => null]);
        $this->actingAs($admin)->post(route('admin.assistant.ask'), ['message' => 'Hi'])->assertSessionHas('error');

        config(['services.gemini.key' => 'test-key']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Here is your summary.']]]]]])]);

        $this->actingAs($admin)->post(route('admin.assistant.ask'), ['message' => 'Summarise'])->assertRedirect();
        $this->actingAs($admin)->get(route('admin.assistant'))->assertSee('Here is your summary.');

        Http::assertSent(fn (HttpRequest $r) => $r->hasHeader('x-goog-api-key', 'test-key')
            && ! str_contains($r->body(), Student::first()->full_name)
            && ! str_contains($r->url(), 'test-key'));
    }

    public function test_sign_ins_and_certificate_checks_are_logged(): void
    {
        $user = $this->makeUser('teacher');

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertSame(1, LoginEvent::where('successful', false)->count());
        $this->assertSame(1, LoginEvent::where('successful', true)->where('event', 'login')->count());

        $this->get(route('certificates.verify', ['serial' => 'TA-CERT-NOPE']))->assertOk();
        $this->assertDatabaseHas('certificate_lookups', ['serial' => 'TA-CERT-NOPE', 'result' => 'not_found']);
    }

    public function test_integrity_checks_pass_on_the_demo_data(): void
    {
        $this->seed();

        $failing = collect(app(IntegrityService::class)->run())->where('ok', false)->pluck('key')->all();

        $this->assertSame([], $failing);
    }
}
