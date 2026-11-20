<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `admin.can:module` — reading pages needs "module.view", anything that changes
 * data (POST, PUT, PATCH, DELETE) needs "module.manage".
 */
class EnsureAdminPermission
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $action = $request->isMethodSafe() ? 'view' : 'manage';

        abort_unless($request->user()?->canAdmin($module, $action), 403, __('Your role does not allow this.'));

        return $next($request);
    }
}
