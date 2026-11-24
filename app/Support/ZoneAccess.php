<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Zone-limited accounts (Settings → Users → "Zones this account can see").
 *
 * An account with zones assigned only sees citizens and households in those
 * zones, plus the records that belong to them (Barangay IDs, documents,
 * health check-ups, holds, blotter cases with a party from those zones).
 * An account with no zones — and every Super Admin — sees all zones.
 *
 * Applied through global scopes named "zone" on the models, so it also covers
 * searches, counts, exports and links typed by hand (they answer 404).
 */
class ZoneAccess
{
    public const SCOPE = 'zone';

    /** Memo: user id => zone ids (empty = all). */
    private static array $cache = [];

    /** Zone ids the signed-in user is limited to, or null when they see all zones. */
    public static function ids(): ?array
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        if (! array_key_exists($user->id, self::$cache)) {
            self::$cache[$user->id] = $user->hasRole('Super Admin')
                ? []
                : DB::table('eb_user_zones')->where('user_id', $user->id)
                    ->pluck('address_id')->map(fn ($v) => (int) $v)->all();
        }

        return self::$cache[$user->id] ?: null;
    }

    public static function restricted(): bool
    {
        return self::ids() !== null;
    }

    /** Can the signed-in user work with this zone id? */
    public static function allows($zoneId): bool
    {
        $ids = self::ids();
        return $ids === null || in_array((int) $zoneId, $ids, true);
    }

    /** Forget the memo (after a user's zones change). */
    public static function flush(?int $userId = null): void
    {
        if ($userId === null) {
            self::$cache = [];
        } else {
            unset(self::$cache[$userId]);
        }
    }

    /**
     * Subquery of citizen ids in the user's zones, for records that point to a
     * citizen. Includes archived citizens and walk-ins, so their records stay
     * visible to the zone's staff.
     */
    public static function citizenIds(): \Illuminate\Database\Query\Builder
    {
        return DB::table('eb_citizen')->select('id')->whereIn('address', self::ids() ?? []);
    }

    /** Global scope: limit a model that has its own zone column. */
    public static function byZoneColumn(string $column): \Closure
    {
        return function (Builder $query) use ($column) {
            if ($ids = self::ids()) {
                $query->whereIn($query->getModel()->getTable() . '.' . $column, $ids);
            }
        };
    }

    /** Global scope: limit a model that points to a citizen. */
    public static function byCitizenColumn(string $column = 'citizen_id'): \Closure
    {
        return function (Builder $query) use ($column) {
            if (self::restricted()) {
                $query->whereIn($query->getModel()->getTable() . '.' . $column, self::citizenIds());
            }
        };
    }

    /** Validation rule: the chosen zone must be one of the user's zones. */
    public static function rule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if ($value !== null && $value !== '' && ! self::allows($value)) {
                $fail('Your account can only add records in your assigned zones.');
            }
        };
    }
}
