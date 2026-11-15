<?php

namespace App\Support;

use App\Models\Family;
use App\Models\Setting;

/**
 * Optional family income classification.
 *
 * Classes follow the PIDS income clusters: income per family member compared
 * with the per-person poverty threshold (set in Settings). Nothing is stored —
 * changing the threshold or the family size re-classifies automatically.
 * When the exact amount is unknown, staff may pick the class directly.
 */
class IncomeClass
{
    /** min/max are multiples of the per-person poverty threshold; max null = no upper limit. */
    const BANDS = [
        'poor'         => ['label' => 'Poor',                'min' => 0,  'max' => 1,    'tone' => 'danger'],
        'low'          => ['label' => 'Low income',          'min' => 1,  'max' => 2,    'tone' => 'warning'],
        'lower_middle' => ['label' => 'Lower middle income', 'min' => 2,  'max' => 4,    'tone' => 'info'],
        'middle'       => ['label' => 'Middle income',       'min' => 4,  'max' => 7,    'tone' => 'info'],
        'upper_middle' => ['label' => 'Upper middle income', 'min' => 7,  'max' => 12,   'tone' => 'success'],
        'upper'        => ['label' => 'Upper income',        'min' => 12, 'max' => 20,   'tone' => 'success'],
        'rich'         => ['label' => 'Rich',                'min' => 20, 'max' => null, 'tone' => 'success'],
    ];

    public static function enabled(): bool
    {
        return (bool) Setting::instance()->income_enabled;
    }

    /** The feature is on and this user may see income. */
    public static function visibleTo($user): bool
    {
        return self::enabled() && $user && $user->can('households.income');
    }

    /** Per-person monthly poverty threshold, or null when not set yet. */
    public static function threshold(): ?float
    {
        $t = (float) Setting::instance()->poverty_threshold;
        return $t > 0 ? $t : null;
    }

    /** Class key for an amount (preferred) or a picked class. */
    public static function keyFor($monthlyIncome, ?string $picked, int $familySize): ?string
    {
        $t = self::threshold();
        if ($monthlyIncome !== null && $familySize > 0 && $t) {
            $ratio = ((float) $monthlyIncome / $familySize) / $t;
            foreach (self::BANDS as $key => $band) {
                if ($band['max'] === null || $ratio < $band['max']) return $key;
            }
        }
        return $picked && isset(self::BANDS[$picked]) ? $picked : null;
    }

    /** Monthly family-income range (₱) for a class at this family size, when a threshold is set. */
    public static function rangeFor(string $key, int $familySize): ?array
    {
        $t = self::threshold();
        $band = self::BANDS[$key] ?? null;
        if (!$t || !$band || $familySize < 1) return null;
        return [
            'min' => round($band['min'] * $t * $familySize),
            'max' => $band['max'] === null ? null : round($band['max'] * $t * $familySize),
        ];
    }

    public static function familySize(Family $family): int
    {
        return $family->relationLoaded('allMembers') ? $family->allMembers->count() : $family->allMembers()->count();
    }

    /** Everything the UI needs about one family's income. */
    public static function describe(Family $family, ?int $size = null): array
    {
        $size = $size ?? self::familySize($family);
        $key  = self::keyFor($family->monthly_income, $family->income_class, $size);
        return [
            'amount'      => $family->monthly_income !== null ? (float) $family->monthly_income : null,
            'picked'      => $family->income_class,
            'size'        => $size,
            'per_person'  => $family->monthly_income !== null && $size > 0 ? round($family->monthly_income / $size, 2) : null,
            'key'         => $key,
            'label'       => $key ? self::BANDS[$key]['label'] : null,
            'tone'        => $key ? self::BANDS[$key]['tone'] : null,
            'assessed'    => $family->monthly_income !== null || $family->income_class !== null,
            'needs_threshold' => $family->monthly_income !== null && !self::threshold(),
            'updated_at'  => $family->income_updated_at?->format('M d, Y'),
        ];
    }

    /**
     * Narrow a citizen query to people whose family is in class $key, or "none"
     * for families not assessed. Mirrors keyFor(): an amount (per member vs. the
     * threshold) wins over a picked class.
     */
    public static function applyCitizenFilter($query, string $key): void
    {
        $size = '(SELECT COUNT(*) FROM eb_citizen m WHERE m.familyId = eb_family.id)';
        $t    = self::threshold();

        if ($key === 'none') {
            $query->where(function ($q) use ($t) {
                $q->whereNull('familyId')
                  ->orWhereHas('family', function ($f) use ($t) {
                      $f->whereNull('income_class')->where(fn($x) => $t ? $x->whereNull('monthly_income') : $x);
                  });
            });
            return;
        }

        $band = self::BANDS[$key] ?? null;
        if (!$band) return;

        $query->whereHas('family', function ($f) use ($key, $band, $t, $size) {
            $f->where(function ($w) use ($key, $band, $t, $size) {
                $w->where(fn($p) => $p->whereNull('monthly_income')->where('income_class', $key));
                if ($t) {
                    $w->orWhere(function ($a) use ($band, $t, $size) {
                        $a->whereNotNull('monthly_income')
                          ->whereRaw("monthly_income / {$size} >= ?", [$band['min'] * $t]);
                        if ($band['max'] !== null) {
                            $a->whereRaw("monthly_income / {$size} < ?", [$band['max'] * $t]);
                        }
                    });
                }
            });
        });
    }

    /** Class options with the peso range for this family size (for the picker). */
    public static function options(int $familySize): array
    {
        $out = [];
        foreach (self::BANDS as $key => $band) {
            $out[] = ['key' => $key, 'label' => $band['label'], 'tone' => $band['tone'], 'range' => self::rangeFor($key, $familySize)];
        }
        return $out;
    }
}
