<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(Request $request): View
    {
        $teachers = User::where('role', 'teacher')
            ->with(['teacherProfile', 'branch'])
            ->withCount(['taughtBatches' => fn ($q) => $q->where('status', 'active')])
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%")))
            ->when($request->query('type'), fn ($q, $type) => $q->whereHas('teacherProfile', fn ($q) => $q->where('instructor_type', $type)))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.teachers.index', compact('teachers'));
    }

    public function create(): View
    {
        return view('admin.teachers.form', [
            'teacher' => new User(['role' => 'teacher', 'is_active' => true]),
            'profile' => new TeacherProfile(['instructor_type' => 'academic_teacher', 'hire_date' => now()]),
            'branches' => Branch::active()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$userData, $profileData] = $this->validated($request);

        $teacher = DB::transaction(function () use ($userData, $profileData) {
            $teacher = User::create($userData + ['role' => 'teacher']);
            $teacher->teacherProfile()->create($profileData);

            return $teacher;
        });

        ActivityLog::record('teacher.created', "Instructor {$teacher->name} added", $teacher);

        return redirect()->route('admin.teachers.show', $teacher)->with('success', __('Instructor saved.'));
    }

    public function show(User $teacher): View
    {
        abort_unless($teacher->role === 'teacher', 404);

        $teacher->load(['teacherProfile', 'branch', 'taughtBatches' => fn ($q) => $q->with(['course', 'schedules'])->withCount('enrollments')]);

        return view('admin.teachers.show', compact('teacher'));
    }

    public function edit(User $teacher): View
    {
        abort_unless($teacher->role === 'teacher', 404);

        return view('admin.teachers.form', [
            'teacher' => $teacher,
            'profile' => $teacher->teacherProfile ?? new TeacherProfile(),
            'branches' => Branch::where('status', '!=', 'archived')->get(),
        ]);
    }

    public function update(Request $request, User $teacher): RedirectResponse
    {
        abort_unless($teacher->role === 'teacher', 404);

        [$userData, $profileData] = $this->validated($request, $teacher);

        DB::transaction(function () use ($teacher, $userData, $profileData) {
            $teacher->update(array_filter($userData, fn ($v, $k) => $k !== 'password' || filled($v), ARRAY_FILTER_USE_BOTH));
            $teacher->teacherProfile()->updateOrCreate(['user_id' => $teacher->id], $profileData);
        });

        ActivityLog::record('teacher.updated', "Instructor {$teacher->name} updated", $teacher);

        return redirect()->route('admin.teachers.show', $teacher)->with('success', __('Instructor saved.'));
    }

    public function destroy(User $teacher): RedirectResponse
    {
        abort_unless($teacher->role === 'teacher', 404);

        if ($teacher->taughtBatches()->exists()) {
            $teacher->update(['is_active' => false]);

            return back()->with('warning', __('This instructor is assigned to classes, so the account was deactivated instead of deleted.'));
        }

        $teacher->delete();
        ActivityLog::record('teacher.deleted', "Instructor {$teacher->name} deleted");

        return redirect()->route('admin.teachers.index')->with('success', __('Instructor deleted.'));
    }

    private function validated(Request $request, ?User $teacher = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users')->ignore($teacher?->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'password' => [$teacher ? 'nullable' : 'required', 'string', 'min:8'],
            'is_active' => ['boolean'],
            'instructor_type' => ['required', Rule::in(TeacherProfile::TYPES)],
            'title_ar' => ['nullable', 'string', 'max:150'],
            'title_en' => ['nullable', 'string', 'max:150'],
            'specialization_ar' => ['nullable', 'string', 'max:200'],
            'specialization_en' => ['nullable', 'string', 'max:200'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'national_id' => ['nullable', 'digits:14'],
            'hire_date' => ['nullable', 'date'],
            'monthly_salary' => ['nullable', 'numeric', 'min:0'],
            'office_hours' => ['nullable', 'string', 'max:150'],
        ]);

        $userKeys = ['name', 'email', 'phone', 'branch_id', 'password'];
        $userData = array_intersect_key($data, array_flip($userKeys)) + ['is_active' => $request->boolean('is_active')];
        $profileData = array_diff_key($data, array_flip([...$userKeys, 'is_active']));
        $profileData['monthly_salary'] ??= 0;

        return [$userData, $profileData];
    }
}
