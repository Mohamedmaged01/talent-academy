<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => __('These credentials do not match our records.')]);
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            throw ValidationException::withMessages(['email' => __('Your account has been deactivated.')]);
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        if ($user->locale) {
            $request->session()->put('locale', $user->locale);
        }

        ActivityLog::record('auth.login', "{$user->name} signed in", $user);

        // Only honour the "intended" URL when it belongs to this user's own portal;
        // otherwise a teacher who was bounced from /admin would land on a 403.
        $intended = $request->session()->pull('url.intended');

        return redirect()->to($intended && $this->belongsToPortal($user, $intended) ? $intended : $user->homeRoute());
    }

    private function belongsToPortal(\App\Models\User $user, string $url): bool
    {
        $path = '/'.ltrim((string) parse_url($url, PHP_URL_PATH), '/');

        foreach (['/'.$user->role, '/profile'] as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $locale = $request->session()->get('locale');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put('locale', $locale);

        return redirect()->route('home');
    }
}
