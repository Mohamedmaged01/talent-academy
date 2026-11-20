<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PermissionDelegation;
use App\Models\User;
use App\Support\Notify;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Temporary permissions. An administrator can lend permissions they hold to a
 * colleague for a set period (e.g. while on leave), and any administrator can
 * ask for extra access, which someone who holds it approves or rejects.
 */
class DelegationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $canManage = $user->canAdmin('users', 'manage');
        // Without the accounts permission an administrator only sees what concerns them.
        $own = fn ($q) => $q->where(fn ($q) => $q->where('to_user_id', $user->id)->orWhere('from_user_id', $user->id));

        return view('admin.delegations.index', [
            'delegations' => PermissionDelegation::with(['grantor', 'grantee', 'reviewer'])->where('status', '!=', 'pending')
                ->when(! $canManage, $own)->latest()->paginate(20),
            'requests' => PermissionDelegation::with('grantee')->where('status', 'pending')->when(! $canManage, $own)->latest()->get(),
            'colleagues' => User::where('role', 'admin')->where('is_active', true)->whereKeyNot($user->id)->whereNotNull('admin_role_id')->orderBy('name')->get(),
            'mine' => $user->adminPermissions(),
            'canManage' => $canManage,
        ]);
    }

    /** Lend some of my permissions to a colleague. */
    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->canAdmin('users', 'manage'), 403);

        $data = $request->validate([
            'to_user_id' => ['required', Rule::exists('users', 'id')->where('role', 'admin')],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => [Rule::in(Permissions::all())],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['required', 'date', 'after:now', 'after:starts_at', 'before:'.now()->addDays(180)->toDateString()],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        if ((int) $data['to_user_id'] === $actor->id) {
            throw ValidationException::withMessages(['to_user_id' => __('You cannot delegate to yourself.')]);
        }

        $this->assertHolds($actor, $data['permissions']);

        $delegation = PermissionDelegation::create([
            'from_user_id' => $actor->id,
            'to_user_id' => $data['to_user_id'],
            'permissions' => Permissions::clean($data['permissions']),
            'reason' => $data['reason'],
            'status' => 'active',
            'starts_at' => $data['starts_at'] ?? now(),
            'ends_at' => $data['ends_at'],
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
        ]);

        ActivityLog::record('delegation.created', "Permissions delegated to {$delegation->grantee->email} until {$delegation->ends_at->toDateTimeString()}", $delegation);
        Notify::users($delegation->grantee, ':name gave you temporary permissions until :date', ['name' => $actor->name, 'date' => $delegation->ends_at->format('Y-m-d H:i')], route('admin.delegations.index'), 'key-round');

        return back()->with('success', __('Delegation created.'));
    }

    public function revoke(PermissionDelegation $delegation): RedirectResponse
    {
        $actor = auth()->user();
        abort_unless($actor->canAdmin('users', 'manage') || (int) $delegation->from_user_id === $actor->id, 403);

        $delegation->update(['status' => 'revoked', 'revoked_at' => now()]);
        ActivityLog::record('delegation.revoked', "Delegation #{$delegation->id} to {$delegation->grantee->email} revoked", $delegation);

        return back()->with('success', __('Delegation revoked.'));
    }

    /** Ask for extra permissions for a number of days. */
    public function request(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => [Rule::in(Permissions::all())],
            'requested_days' => ['required', 'integer', 'min:1', 'max:90'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $wanted = array_values(array_diff(Permissions::clean($data['permissions']), $request->user()->adminPermissions()));

        if ($wanted === []) {
            throw ValidationException::withMessages(['permissions' => __('You already have these permissions.')]);
        }

        PermissionDelegation::create([
            'to_user_id' => $request->user()->id,
            'permissions' => $wanted,
            'reason' => $data['reason'],
            'requested_days' => $data['requested_days'],
            'status' => 'pending',
        ]);

        Notify::admins(':name requested extra permissions', ['name' => $request->user()->name], route('admin.delegations.index'), 'key-round', 'users');

        return back()->with('success', __('Request sent. You will be notified when it is reviewed.'));
    }

    public function approve(PermissionDelegation $delegation): RedirectResponse
    {
        $actor = auth()->user();
        abort_unless($actor->canAdmin('users', 'manage') && $delegation->status === 'pending', 403);

        if ((int) $delegation->to_user_id === $actor->id) {
            return back()->with('error', __('You cannot approve your own request.'));
        }

        $this->assertHolds($actor, $delegation->permissions);

        $delegation->update([
            'from_user_id' => $actor->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addDays($delegation->requested_days ?: 7),
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
        ]);

        ActivityLog::record('delegation.approved', "Access request #{$delegation->id} from {$delegation->grantee->email} approved", $delegation);
        Notify::users($delegation->grantee, 'Your access request was approved until :date', ['date' => $delegation->ends_at->format('Y-m-d H:i')], route('admin.delegations.index'), 'key-round');

        return back()->with('success', __('Request approved.'));
    }

    public function reject(PermissionDelegation $delegation): RedirectResponse
    {
        abort_unless(auth()->user()->canAdmin('users', 'manage') && $delegation->status === 'pending', 403);

        $delegation->update(['status' => 'rejected', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        Notify::users($delegation->grantee, 'Your access request was not approved', [], route('admin.delegations.index'), 'key-round');

        return back()->with('success', __('Request rejected.'));
    }

    private function assertHolds(User $actor, array $permissions): void
    {
        if (array_diff(Permissions::clean($permissions), $actor->adminPermissions()) !== []) {
            throw ValidationException::withMessages(['permissions' => __('You can only delegate permissions you hold yourself.')]);
        }
    }
}
