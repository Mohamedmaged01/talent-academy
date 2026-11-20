<?php

namespace App\Support;

use App\Models\Batch;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Who a user may start a conversation with:
 *  - administrators: anyone with an active account;
 *  - teachers: administrators, students in their active classes and those students' parents;
 *  - students: administrators and the teachers of their active classes;
 *  - parents: administrators and the teachers of their children's active classes.
 */
class MessagingDirectory
{
    /** @return Collection<int, array{user: User, context: string}> */
    public function recipientsFor(User $user): Collection
    {
        $admins = User::where('role', 'admin')->where('is_active', true)->whereKeyNot($user->id)->orderBy('name')->get()
            ->map(fn (User $u) => ['user' => $u, 'context' => __('Administration')]);

        $others = match ($user->role) {
            'admin' => User::where('role', '!=', 'admin')->where('is_active', true)->orderBy('role')->orderBy('name')->get()
                ->map(fn (User $u) => ['user' => $u, 'context' => self::roleLabel($u->role)]),
            'teacher' => $this->forTeacher($user),
            'student' => $this->teachersOf($user->studentRecord ? [$user->studentRecord->id] : []),
            'parent' => $this->teachersOf($user->children()->pluck('students.id')->all()),
            default => collect(),
        };

        return $admins->concat($others)->unique(fn ($row) => $row['user']->id)->values();
    }

    public function canMessage(User $from, User $to): bool
    {
        return $this->recipientsFor($from)->contains(fn ($row) => $row['user']->id === $to->id);
    }

    private function forTeacher(User $teacher): Collection
    {
        $batches = Batch::where('teacher_id', $teacher->id)->whereIn('status', ['active', 'upcoming'])
            ->with(['activeStudents.user', 'activeStudents.guardians'])->get();

        $rows = collect();

        foreach ($batches as $batch) {
            foreach ($batch->activeStudents as $student) {
                if ($student->user?->is_active) {
                    $rows->push(['user' => $student->user, 'context' => __('Student · :class', ['class' => $batch->name])]);
                }

                foreach ($student->guardians->where('is_active', true) as $guardian) {
                    $rows->push(['user' => $guardian, 'context' => __('Parent of :name', ['name' => $student->full_name])]);
                }
            }
        }

        return $rows;
    }

    /** Teachers of the active classes these students are enrolled in. */
    private function teachersOf(array $studentIds): Collection
    {
        if ($studentIds === []) {
            return collect();
        }

        $batchIds = Enrollment::whereIn('student_id', $studentIds)->where('status', 'active')->pluck('batch_id');

        return Batch::whereIn('id', $batchIds)->whereIn('status', ['active', 'upcoming'])->whereNotNull('teacher_id')
            ->with(['teacher', 'course'])->get()
            ->filter(fn (Batch $b) => $b->teacher?->is_active)
            ->map(fn (Batch $b) => ['user' => $b->teacher, 'context' => __('Teacher · :course', ['course' => $b->course->tr('title')])]);
    }

    public static function roleLabel(string $role): string
    {
        return [
            'admin' => __('Administrator'),
            'teacher' => __('Teacher / Trainer'),
            'student' => __('Student / Trainee'),
            'parent' => __('Parent'),
        ][$role] ?? $role;
    }
}
