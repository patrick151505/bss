<?php

namespace App\Http\Middleware;

use App\Support\Plan;
use Closure;
use Illuminate\Http\Request;

/**
 * Route middleware "feature:budget": the page exists only on plans that
 * include the module. Otherwise it answers 404, as if it were not installed.
 */
class RequirePlanFeature
{
    public function handle(Request $request, Closure $next, string $feature)
    {
        abort_unless(Plan::has($feature), 404);

        return $next($request);
    }
}
