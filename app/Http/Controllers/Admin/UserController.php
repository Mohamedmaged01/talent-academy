<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.users.index', [
            'users' => User::with('branch')
                ->when($request->query('role'), fn ($q, $v) => $q->where('role', $v))
                ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($q) => $q
                    ->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")))
                ->orderBy('role')->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'counts' => User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role'),
            'branches' => Branch::active()->get(),
        ]);
    }

    /** Create an additional administrator account. Other roles are created from their own modules. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'password' => ['required', Password::min(8)],
        ]);

        $user = User::create($data + ['role' => 'admin', 'is_active' => true]);
        ActivityLog::record('user.created', "Administrator account {$user->email} created", $user);

        return back()->with('success', __('Administrator account created.'));
    }

    public function toggle(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return back()->with('error', __('You cannot deactivate your own account.'));
        }

        $user->update(['is_active' => ! $user->is_active]);

        if (! $user->is_active) {
            // Sign the user out everywhere.
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        ActivityLog::record('user.toggled', "Account {$user->email} ".($user->is_active ? 'activated' : 'deactivated'), $user);

        return back()->with('success', $user->is_active ? __('Account activated.') : __('Account deactivated.'));
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['password' => ['required', Password::min(8)]]);

        $user->update(['password' => $data['password']]);
        DB::table('sessions')->where('user_id', $user->id)->delete();
        ActivityLog::record('user.password_reset', "Password reset for {$user->email}", $user);

        return back()->with('success', __('Password updated.'));
    }
}
