<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blotter extends Model
{
    protected $table = 'eb_blotters';

    protected $fillable = [
        'blotter_no', 'filed_date', 'incident_date', 'incident_time',
        'type', 'incident_location',
        'complainant_name', 'complainant_address', 'complainant_contact', 'complainant_citizen_id',
        'respondent_name',  'respondent_address',  'respondent_contact',  'respondent_citizen_id',
        'narrative', 'witnesses', 'attending_officer',
        'status', 'action_taken', 'settled_date', 'referred_to', 'remarks',
        'photos', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'filed_date'    => 'date',
        'incident_date' => 'date',
        'settled_date'  => 'date',
        'photos'        => 'array',
    ];

    const TYPES = [
        'complaint'        => 'Complaint',
        'physical_injury'  => 'Physical Injury',
        'theft'            => 'Theft',
        'trespassing'      => 'Trespassing',
        'disturbance'      => 'Public Disturbance',
        'dispute'          => 'Dispute',
        'threat'           => 'Threat',
        'damage_property'  => 'Damage to Property',
        'other'            => 'Other',
    ];

    const STATUSES = [
        'filed'     => 'Filed',
        'ongoing'   => 'Ongoing',
        'settled'   => 'Settled',
        'referred'  => 'Referred',
        'dismissed' => 'Dismissed',
        'closed'    => 'Closed',
    ];

    const STATUS_COLORS = [
        'filed'     => 'bg-primary/10 text-primary',
        'ongoing'   => 'bg-warning/10 text-warning',
        'settled'   => 'bg-success/10 text-success',
        'referred'  => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
        'dismissed' => 'bg-danger/10 text-danger',
        'closed'    => 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
    ];

    public static function generateBlotterNo(): string
    {
        $year  = now()->year;
        $count = static::whereYear('filed_date', $year)->count();
        return sprintf('BLT-%d-%04d', $year, $count + 1);
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-gray-100 text-gray-500';
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function isCustomType(): bool
    {
        return !array_key_exists($this->type, self::TYPES);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    // ── Relationships ──────────────────────────────────────────────

    public function parties()
    {
        return $this->hasMany(BlotterParty::class, 'blotter_id')->orderBy('sort_order');
    }

    public function complainants()
    {
        return $this->hasMany(BlotterParty::class, 'blotter_id')
                    ->where('role', 'complainant')
                    ->orderBy('sort_order');
    }

    public function respondents()
    {
        return $this->hasMany(BlotterParty::class, 'blotter_id')
                    ->where('role', 'respondent')
                    ->orderBy('sort_order');
    }

    public function actions()
    {
        return $this->hasMany(BlotterAction::class, 'blotter_id')->orderByDesc('scheduled_date')->orderByDesc('id');
    }

    public function complainantCitizen()
    {
        return $this->belongsTo(Citizen::class, 'complainant_citizen_id');
    }

    public function respondentCitizen()
    {
        return $this->belongsTo(Citizen::class, 'respondent_citizen_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ── Helpers ────────────────────────────────────────────────────

    /** Comma-separated names for a given role, falling back to the old single column. */
    public function partyNames(string $role): string
    {
        $parties = $this->relationLoaded('parties')
            ? $this->parties->where('role', $role)
            : $this->parties()->where('role', $role)->get();

        if ($parties->isNotEmpty()) {
            return $parties->pluck('name')->join(', ');
        }

        return $role === 'complainant'
            ? ($this->complainant_name ?? '—')
            : ($this->respondent_name  ?? '—');
    }
}
