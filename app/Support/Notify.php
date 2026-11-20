<?php

namespace App\Support;

use App\Models\Student;
use App\Models\User;
use App\Notifications\PortalNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * One place to send in-app notifications. The acting user is never notified
 * about their own action, and inactive accounts are skipped.
 */
class Notify
{
    public static function users(iterable|User|null $users, string $key, array $params = [], ?string $url = null, string $icon = 'bell'): void
    {
        $recipients = Collection::wrap($users instanceof User ? [$users] : ($users ?? []))
            ->filter(fn ($u) => $u instanceof User && $u->is_active && $u->id !== auth()->id())
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new PortalNotification($key, $params, self::relative($url), $icon));
    }

    /** Active administrators who may open the given module (all of them when no module is given). */
    public static function admins(string $key, array $params = [], ?string $url = null, string $icon = 'bell', ?string $module = null): void
    {
        $admins = User::where('role', 'admin')->where('is_active', true)->with('adminRole')->get()
            ->filter(fn (User $admin) => $module === null || $admin->canAdmin($module));

        self::users($admins, $key, $params, $url, $icon);
    }

    /** The student's own portal account and, when a parent URL is given, their guardians. */
    public static function student(Student $student, string $key, array $params = [], ?string $url = null, string $icon = 'bell', ?string $parentUrl = null): void
    {
        self::users($student->user, $key, $params, $url, $icon);

        if ($parentUrl !== null) {
            self::users($student->guardians, $key, $params, $parentUrl, $icon);
        }
    }

    /** Store links without the host so they keep working if the domain changes. */
    private static function relative(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $query = parse_url($url, PHP_URL_QUERY);

        return $path.($query ? '?'.$query : '');
    }
}
