<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\Payment;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * For administrators whose role is limited to their own branch: any record in
 * the URL (student, class, invoice, payment, expense, application…) must belong
 * to that branch. Lists are filtered in the controllers.
 */
class EnforceBranchScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $branch = $request->user()?->branchScope();

        if ($branch) {
            foreach ($request->route()?->parameters() ?? [] as $parameter) {
                if (! $parameter instanceof Model) {
                    continue;
                }

                $owner = match (true) {
                    $parameter instanceof Branch => $parameter->id,
                    $parameter instanceof Payment => $parameter->invoice?->branch_id,
                    array_key_exists('branch_id', $parameter->getAttributes()) => $parameter->branch_id,
                    default => null,
                };

                abort_if($owner !== null && (int) $owner !== $branch, 403, __('This record belongs to another branch.'));
            }
        }

        return $next($request);
    }
}
