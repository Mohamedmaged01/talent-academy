<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(): View
    {
        return view('admin.branches.index', [
            'branches' => Branch::withCount([
                'students' => fn ($q) => $q->where('status', 'active'),
                'batches' => fn ($q) => $q->where('status', 'active'),
            ])->orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.branches.form', ['branch' => new Branch(['status' => 'active', 'capacity' => 300])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $branch = Branch::create($this->validated($request));
        ActivityLog::record('branch.created', "Branch {$branch->code} created", $branch);

        return redirect()->route('admin.branches.index')->with('success', __('Branch saved.'));
    }

    public function edit(Branch $branch): View
    {
        return view('admin.branches.form', compact('branch'));
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $branch->update($this->validated($request, $branch));
        ActivityLog::record('branch.updated', "Branch {$branch->code} updated", $branch);

        return redirect()->route('admin.branches.index')->with('success', __('Branch saved.'));
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        // Archival guard: a branch with people, classes or money attached is archived, never deleted.
        if ($branch->students()->exists() || $branch->batches()->exists() || $branch->invoices()->exists()) {
            $branch->update(['status' => 'archived']);
            ActivityLog::record('branch.archived', "Branch {$branch->code} archived (has linked records)", $branch);

            return back()->with('warning', __('This branch has linked records, so it was archived instead of deleted.'));
        }

        $branch->delete();
        ActivityLog::record('branch.deleted', "Branch {$branch->code} deleted");

        return back()->with('success', __('Branch deleted.'));
    }

    private function validated(Request $request, ?Branch $branch = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('branches')->ignore($branch?->id)],
            'name_ar' => ['required', 'string', 'max:120'],
            'name_en' => ['required', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'manager_name' => ['nullable', 'string', 'max:120'],
            'capacity' => ['required', 'integer', 'min:0', 'max:100000'],
            'status' => ['required', Rule::in(['active', 'inactive', 'archived'])],
        ]);
    }
}
