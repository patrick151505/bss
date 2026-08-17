<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'eb_settings';

    protected $fillable = [
        'barangay_name',
        'address',
        'municipality',
        'province',
        'contact',
        'email',
        'logo',
        'municipal_logo',
        'captain_name',
        'captain_position',
        'captain_signature',
        'citizen_id_prefix',
        'citizen_id_digits',
        'id_validity',
    ];

    /** Per-request memoized single settings row (avoids repeat queries). */
    protected static ?self $cachedInstance = null;

    public static function instance(): static
    {
        return static::$cachedInstance
            ??= static::firstOrCreate(['id' => 1], ['captain_position' => 'Barangay Captain']);
    }

    protected static function booted(): void
    {
        // Keep the memoized instance in sync after any settings change.
        static::saved(fn () => static::$cachedInstance = null);
    }

    public function formatCitizenId(int $id): string
    {
        $prefix = $this->citizen_id_prefix ?: 'EBT';
        $digits = max(1, (int) ($this->citizen_id_digits ?: 6));
        return $prefix . '-' . str_pad($id, $digits, '0', STR_PAD_LEFT);
    }

    /** Compute an ID's expiry from the barangay-wide validity setting. */
    public function idValidUntil(?\Carbon\Carbon $from = null): \Carbon\Carbon
    {
        $from = ($from ?? now())->copy();
        return match ($this->id_validity) {
            '6m'    => $from->addMonths(6),
            '1y'    => $from->addYear(),
            default => $from->addYears(2),   // '2y' or unset
        };
    }

    /** Human label for the current validity setting, e.g. "2 years". */
    public function idValidityLabel(): string
    {
        return match ($this->id_validity) {
            '6m'    => '6 months',
            '1y'    => '1 year',
            default => '2 years',
        };
    }

    public function fullAddress(): string
    {
        return collect([
            $this->address,
            $this->municipality,
            $this->province,
        ])->filter()->implode(', ');
    }
}
