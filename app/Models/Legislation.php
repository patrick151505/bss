<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An ordinance or resolution of the Sangguniang Barangay.
 *
 * Ordinances go through three readings before approval; resolutions are
 * adopted in one sitting. Either can later be repealed by another measure.
 */
class Legislation extends Model
{
    use SoftDeletes;

    protected $table = 'eb_legislations';

    protected $fillable = [
        'type', 'number', 'series_year', 'title', 'summary', 'category', 'status',
        'date_introduced', 'date_approved', 'date_effective',
        'votes_yes', 'votes_no', 'votes_abstain',
        'amends_id', 'repealed_by_id', 'file_path', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'date_introduced' => 'date',
        'date_approved'   => 'date',
        'date_effective'  => 'date',
        'series_year'     => 'integer',
        'votes_yes'       => 'integer',
        'votes_no'        => 'integer',
        'votes_abstain'   => 'integer',
    ];

    const TYPES = ['ordinance' => 'Ordinance', 'resolution' => 'Resolution'];

    /** status => label. Badge colours live in legislation/_status-badge.blade.php
     *  (Tailwind only scans resources/, so class names can't live here). */
    const STATUSES = [
        'draft'          => 'Draft',
        'first_reading'  => 'First reading',
        'second_reading' => 'Second reading',
        'third_reading'  => 'Third reading',
        'approved'       => 'Approved',
        'effective'      => 'In force',
        'vetoed'         => 'Vetoed',
        'repealed'       => 'Repealed',
    ];

    /** The normal path of each type, in order. */
    const FLOW = [
        'ordinance'  => ['draft', 'first_reading', 'second_reading', 'third_reading', 'approved', 'effective'],
        'resolution' => ['draft', 'approved', 'effective'],
    ];

    /** Statuses that mean the measure has passed (it can repeal/amend others, and be repealed). */
    const PASSED = ['approved', 'effective'];

    const CATEGORIES = [
        'Administrative', 'Appropriation / Budget', 'Revenue / Fees', 'Peace & Order',
        'Health & Sanitation', 'Environment', 'Infrastructure', 'Social Services',
        'Education & Youth', 'Livelihood', 'Disaster Risk Reduction', 'Other',
    ];

    const AUTHOR_ROLES = ['author' => 'Author', 'co_author' => 'Co-author', 'sponsor' => 'Sponsor'];

    // ── Relations ──────────────────────────────────────────────────────────
    public function authors()
    {
        return $this->hasMany(LegislationAuthor::class)->orderByRaw("FIELD(role,'author','co_author','sponsor')")->orderBy('id');
    }

    public function events()
    {
        return $this->hasMany(LegislationEvent::class)->orderBy('event_date')->orderBy('id');
    }

    public function amends()
    {
        return $this->belongsTo(self::class, 'amends_id')->withTrashed();
    }

    public function amendments()
    {
        return $this->hasMany(self::class, 'amends_id');
    }

    public function repealedBy()
    {
        return $this->belongsTo(self::class, 'repealed_by_id')->withTrashed();
    }

    /** Measures this one repeals (they point at us). */
    public function repeals()
    {
        return $this->hasMany(self::class, 'repealed_by_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────
    public function scopeSearch($q, ?string $term)
    {
        $term = trim((string) $term);
        if ($term === '') return $q;
        return $q->where(function ($w) use ($term) {
            $w->where('number', 'like', "%{$term}%")
              ->orWhere('title', 'like', "%{$term}%")
              ->orWhere('summary', 'like', "%{$term}%")
              ->orWhereHas('authors', fn($a) => $a->where('name', 'like', "%{$term}%"));
        });
    }

    public function scopeOfType($q, ?string $type)
    {
        return isset(self::TYPES[$type]) ? $q->where('type', $type) : $q;
    }

    // ── Helpers ────────────────────────────────────────────────────────────
    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    /** "Ordinance No. 2026-003" */
    public function reference(): string
    {
        return $this->typeLabel() . ' No. ' . $this->number;
    }

    public function statusLabel(): string
    {
        if ($this->status === 'approved' && $this->type === 'resolution') return 'Adopted';
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function isInForce(): bool
    {
        return $this->status === 'effective';
    }

    public function hasPassed(): bool
    {
        return in_array($this->status, self::PASSED, true);
    }

    /** The next status on the normal path, or null at the end / off the path. */
    public function nextStatus(): ?string
    {
        $flow = self::FLOW[$this->type] ?? self::FLOW['ordinance'];
        $i = array_search($this->status, $flow, true);
        return ($i === false || $i === count($flow) - 1) ? null : $flow[$i + 1];
    }

    /** Label of a status for this measure's type. */
    public function labelFor(string $status): string
    {
        if ($status === 'approved' && $this->type === 'resolution') return 'Adopted';
        return self::STATUSES[$status] ?? ucfirst($status);
    }

    /** Can be vetoed by the Punong Barangay: an ordinance passed on third reading, not yet approved. */
    public function canBeVetoed(): bool
    {
        return $this->type === 'ordinance' && $this->status === 'third_reading';
    }

    public function authorNames(string $role = 'author'): string
    {
        return $this->authors->where('role', $role)->pluck('name')->implode(', ');
    }

    /** Suggested next number for a type in a year: "2026-004". */
    public static function nextNumber(string $type, int $year): string
    {
        $max = self::withTrashed()->where('type', $type)->where('series_year', $year)
            ->pluck('number')
            ->map(fn($n) => preg_match('/(\d+)\s*$/', $n, $m) ? (int) $m[1] : 0)
            ->max() ?? 0;
        return sprintf('%d-%03d', $year, $max + 1);
    }
}
