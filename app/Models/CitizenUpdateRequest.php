<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Changes to a citizen's details asked for at the kiosk, waiting for staff.
 * Approving copies them onto the citizen record.
 */
class CitizenUpdateRequest extends Model
{
    protected $table = 'eb_citizen_update_requests';

    protected $fillable = ['citizen_id', 'kiosk_id', 'changes', 'status', 'reviewed_by', 'reviewed_at', 'reject_reason'];

    protected $casts = [
        'changes'     => 'array',
        'reviewed_at' => 'datetime',
    ];

    const STATUSES = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];

    /** Fields a resident may change at the kiosk, with their labels. Name, birthday and sex need the counter. */
    const FIELDS = [
        'contact'          => 'Mobile number',
        'email'            => 'Email',
        'civil_status'     => 'Civil status',
        'occupation'       => 'Occupation',
        'address'          => 'Zone / area',
        'no'               => 'House no.',
        'street'           => 'Street',
        'blk'              => 'Block',
        'lot'              => 'Lot',
        'phase'            => 'Phase',
        'complete_address' => 'Complete address',
        'voters'           => 'Registered voter',
        'pricinct_no'      => 'Precinct no.',
        'ic_fullname'      => 'Emergency contact',
        'ic_relationship'  => 'Emergency contact relationship',
        'ic_contact'       => 'Emergency contact mobile',
        'ic_address'       => 'Emergency contact address',
        'ic_email'         => 'Emergency contact email',
    ];

    public function citizen()
    {
        return $this->belongsTo(Citizen::class, 'citizen_id')->withoutGlobalScopes();
    }

    public function kiosk()
    {
        return $this->belongsTo(Kiosk::class, 'kiosk_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public static function pendingCount(): int
    {
        return static::where('status', 'pending')->count();
    }

    /** A stored value as people read it (zone name, civil status name, Yes/No). */
    public static function display(string $field, $value): string
    {
        if ($value === null || $value === '') return '—';
        return match ($field) {
            'civil_status' => CivilStatus::find($value)?->description ?? (string) $value,
            'address'      => Address::find($value)?->description ?? (string) $value,
            'voters'       => (int) $value === 1 ? 'Yes' : 'No',
            default        => (string) $value,
        };
    }

    /** 09171234567 → 0917•••4567 (shown on the public kiosk screen). */
    public static function maskPhone(?string $v): ?string
    {
        $v = trim((string) $v);
        return strlen($v) >= 8 ? substr($v, 0, 4) . '•••' . substr($v, -4) : ($v ?: null);
    }

    /** maria.santos@gmail.com → ma•••@gmail.com */
    public static function maskEmail(?string $v): ?string
    {
        $v = trim((string) $v);
        if (!str_contains($v, '@')) return $v ?: null;
        [$name, $domain] = explode('@', $v, 2);
        return mb_substr($name, 0, 2) . '•••@' . $domain;
    }
}
