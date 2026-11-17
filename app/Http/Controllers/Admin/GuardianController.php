<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GuardianController extends Controller
{
    public function index(Request $request): View
    {
        $guardians = User::where('role', 'parent')
            ->with('children')
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.guardians.index', compact('guardians'));
    }

    public function create(): View
    {
        return view('admin.guardians.form', [
            'guardian' => new User(['role' => 'parent', 'is_active' => true]),
            'students' => Student::orderBy('full_name')->get(['id', 'full_name', 'code']),
            'linked' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $guardian = DB::transaction(function () use ($data) {
            $guardian = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'role' => 'parent',
                'is_active' => true,
            ]);
            $guardian->children()->sync($this->childPivot($data));

            return $guardian;
        });

        ActivityLog::record('guardian.created', "Parent account {$guardian->name} created", $guardian);

        return redirect()->route('admin.guardians.index')->with('success', __('Parent account saved.'));
    }

    public function edit(User $guardian): View
    {
        abort_unless($guardian->role === 'parent', 404);

        return view('admin.guardians.form', [
            'guardian' => $guardian,
            'students' => Student::orderBy('full_name')->get(['id', 'full_name', 'code']),
            'linked' => $guardian->children()->pluck('students.id')->all(),
            'relationship' => $guardian->children()->first()?->pivot->relationship,
        ]);
    }

    public function update(Request $request, User $guardian): RedirectResponse
    {
        abort_unless($guardian->role === 'parent', 404);

        $data = $this->validated($request, $guardian);

        DB::transaction(function () use ($guardian, $data) {
            $guardian->update(array_filter([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'] ?? null,
            ], fn ($v) => $v !== null));
            $guardian->children()->sync($this->childPivot($data));
        });

        return redirect()->route('admin.guardians.index')->with('success', __('Parent account saved.'));
    }

    public function destroy(User $guardian): RedirectResponse
    {
        abort_unless($guardian->role === 'parent', 404);

        $guardian->delete();
        ActivityLog::record('guardian.deleted', "Parent account {$guardian->name} deleted");

        return back()->with('success', __('Parent account deleted.'));
    }

    private function validated(Request $request, ?User $guardian = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users')->ignore($guardian?->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => [$guardian ? 'nullable' : 'required', 'string', 'min:8'],
            'relationship' => ['required', Rule::in(['father', 'mother', 'guardian'])],
            'students' => ['array'],
            'students.*' => ['exists:students,id'],
        ]);
    }

    private function childPivot(array $data): array
    {
        return collect($data['students'] ?? [])
            ->mapWithKeys(fn ($id) => [$id => ['relationship' => $data['relationship']]])
            ->all();
    }
}
