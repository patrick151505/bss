<?php

namespace App\Support;

/**
 * The subscription plan this install runs on (config/plan.php, set by
 * SUBSCRIPTION_PLAN in .env) and which plan-only modules it includes.
 */
class Plan
{
    /** Modules that some plans leave out. Anything else is always available. */
    public const GATED = ['budget'];

    public static function current(): string
    {
        $plan = config('plan.current', 'pro');
        return array_key_exists($plan, config('plan.plans', [])) ? $plan : 'pro';
    }

    public static function label(): string
    {
        return config('plan.plans.' . self::current() . '.label', ucfirst(self::current()));
    }

    /** Does the current plan include this module (e.g. "budget")? */
    public static function has(string $feature): bool
    {
        if (! in_array($feature, self::GATED, true)) {
            return true;
        }
        return in_array($feature, config('plan.plans.' . self::current() . '.features', []), true);
    }

    /**
     * Is this permission name (e.g. "budget.view") part of a module the plan
     * leaves out? Used to hide it from the Roles page and pickers.
     */
    public static function hidesPermission(string $permission): bool
    {
        return ! self::has(explode('.', $permission)[0]);
    }
}
