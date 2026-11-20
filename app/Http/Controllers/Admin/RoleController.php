<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AdminRole;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Administrator roles. Nobody can create, edit or hand out a role with more
 * power than they hold themselves, and only full administrators can make
 * another full administrator.
 */
class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => AdminRole::withCount('users')->orderBy('name_en')->get(),
            'admins' => User::where('role', 'admin')->with(['adminRole', 'branch'])->orderBy('name')->get(),
            'assignable' => $this->assignableRoles(),
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.form', ['role' => new AdminRole(['permissions' => []])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $role = AdminRole::create($this->validated($request));
        ActivityLog::record('role.created', "Role \"{$role->name_en}\" created", $role);

        return redirect()->route('admin.roles.index')->with('success', __('Role created.'));
    }

    public function edit(AdminRole $role): View
    {
        return view('admin.roles.form', ['role' => $role]);
    }

    public function update(Request $request, AdminRole $role): RedirectResponse
    {
        $this->guardRole($request->user(), $role);
        $role->update($this->validated($request));
        ActivityLog::record('role.updated', "Role \"{$role->name_en}\" updated", $role);

        return redirect()->route('admin.roles.index')->with('success', __('Role updated.'));
    }

    public function destroy(AdminRole $role): RedirectResponse
    {
        $this->guardRole(auth()->user(), $role);

        if ($role->users()->exists()) {
            return back()->with('error', __('Move the administrators off this role before deleting it.'));
        }

        $role->delete();
        ActivityLog::record('role.deleted', "Role \"{$role->name_en}\" deleted");

        return back()->with('success', __('Role deleted.'));
    }

    /** Give an administrator account a role ("" = full administrator). */
    public function assign(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($user->role === 'admin', 404);

        if ($user->is($actor)) {
            return back()->with('error', __('You cannot change your own role.'));
        }

        $data = $request->validate([
            'admin_role_id' => ['nullable', 'exists:admin_roles,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);

        $roleId = $data['admin_role_id'] ?? null;

        if (! $actor->isSuperAdmin()) {
            if ($user->isSuperAdmin()) {
                return back()->with('error', __('Only a full administrator can change another full administrator.'));
            }

            if ($roleId === null || ! $this->assignableRoles()->contains('id', (int) $roleId)) {
                return back()->with('error', __('You can only assign roles whose permissions you hold yourself.'));
            }
        }

        $user->update(['admin_role_id' => $roleId, 'branch_id' => $data['branch_id'] ?? null]);
        ActivityLog::record('role.assigned', "{$user->email} set to role ".($user->adminRole?->name_en ?? 'Full administrator'), $user);

        return back()->with('success', __('Role updated for :name.', ['name' => $user->name]));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => [Rule::in(Permissions::all())],
            'branch_scoped' => ['boolean'],
        ]);

        $permissions = Permissions::clean($data['permissions'] ?? []);
        // "manage" implies "view"; store both so the matrix reads naturally.
        foreach ($permissions as $permission) {
            if (str_ends_with($permission, '.manage')) {
                $permissions[] = str_replace('.manage', '.view', $permission);
            }
        }
        $permissions = Permissions::clean(array_unique($permissions));

        $missing = array_diff($permissions, $request->user()->adminPermissions());
        if ($missing !== []) {
            throw ValidationException::withMessages(['permissions' => __('You cannot grant permissions you do not hold yourself.')]);
        }

        return [
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'],
            'description' => $data['description'] ?? null,
            'permissions' => $permissions,
            'branch_scoped' => $request->boolean('branch_scoped'),
        ];
    }

    /** Roles whose every permission the current user also holds. */
    private function assignableRoles()
    {
        $mine = auth()->user()->adminPermissions();

        return AdminRole::orderBy('name_en')->get()->filter(fn (AdminRole $r) => array_diff($r->permissions ?? [], $mine) === [])->values();
    }

    private function guardRole(User $actor, AdminRole $role): void
    {
        abort_if(array_diff($role->permissions ?? [], $actor->adminPermissions()) !== [], 403, __('This role holds permissions you do not have.'));
    }
}
