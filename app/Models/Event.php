<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Event extends Model
{
    protected $table = 'eb_event';
    public $timestamps = false;

    protected $fillable = [
        'title', 'description', 'category', 'venue',
        'event_date', 'event_start', 'event_end',
        'cover_photo', 'raffle_enabled', 'allow_winner_repeat', 'allowed_addresses', 'eligibility',
        'raffle_pin', 'raffle_pin_expires_at',
        'status', 'reopened_at', 'user_id', 'date_created', 'date_updated',
    ];

    protected $casts = [
        'event_date'            => 'date',
        'date_created'          => 'datetime',
        'date_updated'          => 'datetime',
        'raffle_enabled'        => 'integer',
        'allow_winner_repeat'   => 'integer',
        'raffle_pin_expires_at' => 'datetime',
        'reopened_at'           => 'datetime',
        'allowed_addresses'     => 'array',
        'eligibility'           => 'array',
        'user_id'               => 'integer',
    ];

    public function hasValidPin(): bool
    {
        return $this->raffle_pin &&
               $this->raffle_pin_expires_at &&
               now()->lt($this->raffle_pin_expires_at);
    }

    // ─── Allowed areas (address zones) ────────────────────────────────────────
    // Empty/NULL allowed_addresses = open to everyone.

    /** @return int[] eb_address ids this event is limited to (empty = everyone). */
    public function allowedAddressIds(): array
    {
        return array_values(array_map('intval', $this->allowed_addresses ?? []));
    }

    public function restrictsAddresses(): bool
    {
        return count($this->allowedAddressIds()) > 0;
    }

    public function allowsAddress($addressId): bool
    {
        return !$this->restrictsAddresses() || in_array((int) $addressId, $this->allowedAddressIds(), true);
    }

    /** Allowed zones as Address models, alphabetical (empty collection = everyone). */
    public function allowedAddresses()
    {
        return $this->restrictsAddresses()
            ? Address::whereIn('id', $this->allowedAddressIds())->orderBy('description')->get()
            : collect();
    }

    // ─── Eligibility rules (age, gender, groups, tags) ─────────────────────────
    // Checked together with the allowed areas: a citizen must pass every rule that
    // is set. Ages are counted on the event date, so someone turning 60 before the
    // event already counts as a senior.

    const GROUPS = [
        'senior' => 'Senior citizen (60+)',
        'pwd'    => 'PWD',
        'solo'   => 'Solo parent',
        'voter'  => 'Registered voter',
    ];
    const GROUP_SHORT = ['senior' => 'Senior', 'pwd' => 'PWD', 'solo' => 'Solo parent', 'voter' => 'Voter'];
    const SENIOR_AGE = 60;

    /** Normalized rules (missing keys filled in). */
    public function eligibilityRules(): array
    {
        $r = $this->eligibility ?? [];
        return [
            'min_age'      => isset($r['min_age']) && $r['min_age'] !== '' ? (int) $r['min_age'] : null,
            'max_age'      => isset($r['max_age']) && $r['max_age'] !== '' ? (int) $r['max_age'] : null,
            'gender'       => isset($r['gender']) && $r['gender'] !== '' ? (int) $r['gender'] : null,   // 1 = male, 0 = female
            'groups'       => array_values(array_intersect($r['groups'] ?? [], array_keys(self::GROUPS))),
            'groups_match' => ($r['groups_match'] ?? 'any') === 'all' ? 'all' : 'any',
            'tags'         => array_values(array_map('intval', $r['tags'] ?? [])),
        ];
    }

    public function hasEligibilityRules(): bool
    {
        $r = $this->eligibilityRules();
        return $r['min_age'] !== null || $r['max_age'] !== null || $r['gender'] !== null || $r['groups'] || $r['tags'];
    }

    /** Areas or any other rule — i.e. not open to everyone. */
    public function restrictsAttendees(): bool
    {
        return $this->restrictsAddresses() || $this->hasEligibilityRules();
    }

    private function ageDate(): string
    {
        return ($this->event_date ?? now())->format('Y-m-d');
    }

    /** Limit a Citizen query to people who may attend (areas + rules). */
    public function applyEligibility($query)
    {
        if ($this->restrictsAddresses()) {
            $query->whereIn('address', $this->allowedAddressIds());
        }
        $r   = $this->eligibilityRules();
        $on  = $this->ageDate();
        $age = 'TIMESTAMPDIFF(YEAR, bday, ?)';

        if ($r['min_age'] !== null) $query->whereNotNull('bday')->whereRaw("$age >= ?", [$on, $r['min_age']]);
        if ($r['max_age'] !== null) $query->whereNotNull('bday')->whereRaw("$age <= ?", [$on, $r['max_age']]);
        if ($r['gender'] !== null)  $query->where('gender', $r['gender']);

        if ($r['groups']) {
            $conds = [
                'senior' => fn($q) => $q->whereNotNull('bday')->whereRaw("$age >= ?", [$on, self::SENIOR_AGE]),
                'pwd'    => fn($q) => $q->where('is_pwd', 1),
                'solo'   => fn($q) => $q->where('is_soloparents', 1),
                'voter'  => fn($q) => $q->where('voters', 1),
            ];
            $query->where(function ($q) use ($r, $conds) {
                foreach ($r['groups'] as $g) {
                    $r['groups_match'] === 'all' ? $q->where($conds[$g]) : $q->orWhere($conds[$g]);
                }
            });
        }
        if ($r['tags']) {
            $query->whereHas('tags', fn($q) => $q->whereIn('eb_tags.id', $r['tags']));
        }
        return $query;
    }

    /** Why a citizen can't attend (areas excluded — see allowsAddress), or null if they can. */
    public function eligibilityFailure(Citizen $c): ?string
    {
        if (!$this->hasEligibilityRules()) return null;
        $r   = $this->eligibilityRules();
        $age = $c->bday ? $c->bday->diffInYears($this->event_date ?? now()) : null;

        if (($r['min_age'] !== null || $r['max_age'] !== null) && $age === null) {
            return 'their birthday is not recorded, so their age can\'t be checked';
        }
        if ($r['min_age'] !== null && $age < $r['min_age']) return "they are {$age}; this event is for ages " . $this->ageLabel();
        if ($r['max_age'] !== null && $age > $r['max_age']) return "they are {$age}; this event is for ages " . $this->ageLabel();
        if ($r['gender'] !== null && (int) $c->gender !== $r['gender']) {
            return 'this event is for ' . ($r['gender'] === 1 ? 'men' : 'women') . ' only';
        }
        if ($r['groups']) {
            $has = [
                'senior' => $age !== null && $age >= self::SENIOR_AGE,
                'pwd'    => (bool) $c->is_pwd,
                'solo'   => (bool) $c->is_soloparents,
                'voter'  => (bool) $c->voters,
            ];
            $hits = array_filter($r['groups'], fn($g) => $has[$g]);
            $ok   = $r['groups_match'] === 'all' ? count($hits) === count($r['groups']) : count($hits) > 0;
            if (!$ok) return 'this event is only for ' . $this->groupsLabel();
        }
        if ($r['tags']) {
            $mine = $c->relationLoaded('tags') ? $c->tags->pluck('id') : $c->tags()->pluck('eb_tags.id');
            if ($mine->intersect($r['tags'])->isEmpty()) {
                return 'this event is only for citizens tagged ' . $this->tagNames()->implode(' or ');
            }
        }
        return null;
    }

    public function ageLabel(): ?string
    {
        $r = $this->eligibilityRules();
        if ($r['min_age'] !== null && $r['max_age'] !== null) return "{$r['min_age']}–{$r['max_age']}";
        if ($r['min_age'] !== null) return "{$r['min_age']}+";
        if ($r['max_age'] !== null) return "{$r['max_age']} and below";
        return null;
    }

    public function groupsLabel(): string
    {
        $r = $this->eligibilityRules();
        return collect($r['groups'])->map(fn($g) => self::GROUP_SHORT[$g])->implode($r['groups_match'] === 'all' ? ' and ' : ' or ');
    }

    public function tagNames()
    {
        $ids = $this->eligibilityRules()['tags'];
        return $ids ? Tag::whereIn('id', $ids)->orderBy('name')->pluck('name') : collect();
    }

    /** Short chips for the event page, e.g. ["Age 60+", "Senior or PWD", "Tag: 4Ps"]. */
    public function eligibilityLabels(): array
    {
        $r = $this->eligibilityRules();
        $out = [];
        if ($age = $this->ageLabel())  $out[] = "Age {$age}";
        if ($r['gender'] !== null)     $out[] = $r['gender'] === 1 ? 'Men only' : 'Women only';
        if ($r['groups'])              $out[] = $this->groupsLabel();
        if ($r['tags'])                $out[] = 'Tag: ' . $this->tagNames()->implode(' or ');
        return $out;
    }

    public function attendance()
    {
        return $this->hasMany(EventAttendance::class, 'eventId');
    }

    public function winners()
    {
        return $this->hasMany(EventWinner::class, 'eventId')->orderBy('round');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getAttendanceCountAttribute(): int
    {
        return $this->attendance()->count();
    }

    public function getIsOpenAttribute(): bool
    {
        if ($this->status === 'closed') return false;

        // Reopened by staff: stays open past the schedule until closed again.
        if ($this->reopened_at) return true;

        return !$this->isPastSchedule();
    }

    // Whether the scheduled end (date + end time, or end of the day) has passed.
    public function isPastSchedule(): bool
    {
        if ($this->event_date && $this->event_end) {
            $end = Carbon::parse($this->event_date->format('Y-m-d') . ' ' . $this->event_end);
            return now()->gte($end);
        }

        if ($this->event_date) {
            return now()->gte($this->event_date->endOfDay());
        }

        return false;
    }

    public function getEffectiveStatusAttribute(): string
    {
        return $this->is_open ? 'open' : 'closed';
    }

    public function getCoverPhotoUrlAttribute(): ?string
    {
        return $this->cover_photo
            ? asset(str_replace('public/', 'storage/', $this->cover_photo))
            : null;
    }
}
