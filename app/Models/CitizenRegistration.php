<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A self-registration waiting for review. Approving it creates the eb_citizen
 * record; until then the person is not a citizen anywhere in the system.
 */
class CitizenRegistration extends Model
{
    protected $table = 'eb_citizen_registrations';

    protected $fillable = [
        'token_id', 'status', 'fname', 'mname', 'lname', 'suffix', 'bday', 'data', 'photo',
        'citizen_id', 'reviewed_by', 'reviewed_at', 'reject_reason', 'ip',
    ];

    protected $casts = [
        'bday'        => 'date',
        'data'        => 'array',
        'reviewed_at' => 'datetime',
    ];

    const STATUSES = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];

    public function token()
    {
        return $this->belongsTo(RegistrationToken::class, 'token_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function citizen()
    {
        return $this->belongsTo(Citizen::class, 'citizen_id')->withoutGlobalScopes();
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->lname}, {$this->fname} " . ($this->mname ? $this->mname . ' ' : '') . ($this->suffix ?? ''));
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? asset('storage/' . preg_replace('#^public/#', '', $this->photo)) : null;
    }

    const GROUPS = [
        'is_senior'      => ['key' => 'senior', 'label' => 'Senior'],
        'is_pwd'         => ['key' => 'pwd',    'label' => 'PWD'],
        'is_soloparents' => ['key' => 'solo',   'label' => 'Solo parent'],
    ];

    /**
     * 'yes' (ticked, ID not rejected), 'invalid' (ticked but staff marked the ID Not valid,
     * so the citizen does NOT get the group) or 'no'.
     */
    public function groupState(string $flag): string
    {
        if (($this->data[$flag] ?? 0) != 1) return 'no';
        $key = self::GROUPS[$flag]['key'] ?? null;
        return ($this->data['verification'][$key]['status'] ?? null) === 'invalid' ? 'invalid' : 'yes';
    }

    /** One submitted value from the data JSON. */
    public function field(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /** Existing citizens (residents or walk-ins, active or not) with the same name and birthday. */
    public function possibleDuplicates()
    {
        return Citizen::withoutGlobalScopes()
            ->where('fname', $this->fname)
            ->where('lname', $this->lname)
            ->whereDate('bday', $this->bday)
            ->get();
    }

    public static function pendingCount(): int
    {
        return static::where('status', 'pending')->count();
    }
}
