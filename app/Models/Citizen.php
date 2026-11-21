<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Citizen extends Model
{
    protected $table = 'eb_citizen';

    public $timestamps = false;

    /**
     * PII fields that were encrypted at rest in the old production database.
     * They are now stored as plaintext; this list remains so legacy values
     * can still be recognised and read.
     */
    const ENCRYPTED_FIELDS = [
        'mname', 'bplace', 'contact', 'email', 'complete_address',
        'ic_email', 'ic_fullname', 'ic_contact', 'ic_address', 'ic_relationship',
    ];

    protected $fillable = [
        'qrcode',
        'familyId',
        'isHead',
        'relationId',
        'fname',
        'mname',
        'lname',
        'suffix',
        'bday',
        'bplace',
        'contact',
        'email',
        'gender',
        'civil_status',
        'is_soloparents',
        'address',
        'no',
        'street',
        'blk',
        'lot',
        'phase',
        'year_stay',
        'date_created',
        'date_approved',
        'approval_status',
        'date_last_updated',
        'user_id_approved',
        'is_active',
        'owner_status',
        'complete_address',
        'voters',
        'is_notify',
        'is_id_release',
        'pricinct_no',
        'note',
        'is_download',
        'is_verify',
        'ic_email',
        'ic_fullname',
        'ic_contact',
        'ic_address',
        'ic_relationship',
        'is_pwd',
        'citizenship',
        'occupation',
        'profile',
        'verified_at',
        'archive_reason',
        'archived_at',
        'archived_by',
        'is_walkin',
        'walkin_id_type',
        'walkin_id_number',
        'walkin_consent_at',
    ];

    protected $casts = [
        'bday'              => 'date',
        'year_stay'         => 'date',
        'date_created'      => 'datetime',
        'date_approved'     => 'datetime',
        'date_last_updated' => 'datetime',
        'verified_at'       => 'datetime',
        'archived_at'       => 'datetime',
        'gender'            => 'integer',
        'civil_status'      => 'integer',
        'address'           => 'integer',
        'approval_status'   => 'integer',
        'is_active'         => 'integer',
        'is_pwd'            => 'integer',
        'is_soloparents'    => 'integer',
        'voters'            => 'integer',
        'is_notify'         => 'integer',
        'is_id_release'     => 'integer',
        'is_verify'         => 'integer',
        'is_download'       => 'integer',
        'owner_status'      => 'integer',
        'blk'               => 'integer',
        'lot'               => 'integer',
        'phase'             => 'integer',
        'familyId'          => 'integer',
        'isHead'            => 'integer',
        'relationId'        => 'integer',
        'is_walkin'         => 'boolean',
        'walkin_consent_at' => 'datetime',
    ];

    // Relationships
    public function family()
    {
        return $this->belongsTo(Family::class, 'familyId');
    }

    public function relation()
    {
        return $this->belongsTo(Relation::class, 'relationId');
    }

    public function eventAttendances()
    {
        return $this->hasMany(EventAttendance::class, 'citizenId');
    }

    public function eventWins()
    {
        return $this->hasMany(EventWinner::class, 'citizenId');
    }

    public function inventoryReleases()
    {
        return $this->hasMany(InventoryRelease::class, 'citizen_id');
    }

    public function documentRequests()
    {
        return $this->hasMany(DocumentRequest::class, 'citizen_id');
    }

    public function getProfilePhotoAttribute(): string
    {
        return $this->profile_url ?? asset('images/default-avatar.png');
    }

    /**
     * Public URL of the profile photo, or null when there is none.
     *
     * `profile` is stored as "public/citizen/<file>" (a path on the default
     * disk), which lives at "citizen/<file>" on the public disk — so the
     * leading "public/" must be dropped before prefixing "storage/".
     * Re-uploads reuse the same filename, so the file's modified time is
     * appended to make the browser fetch the new photo instead of a cached one.
     */
    /**
     * Save an uploaded picture as this person's profile photo, stored as
     * public/citizen/<qrcode>.<ext> (walk-ins: citizen-<id>.<ext>). Used by the
     * Barangay ID page and the document request form. Returns the old path.
     */
    public function replaceProfilePhoto(\Illuminate\Http\UploadedFile $file): ?string
    {
        $ext  = strtolower($file->extension() ?: 'jpg');
        $ext  = $ext === 'jpeg' ? 'jpg' : $ext;
        $base = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $this->qrcode) ?: 'citizen-' . $this->id;
        $path = 'public/citizen/' . $base . '.' . $ext;

        $old = $this->profile;
        $file->storeAs('public/citizen', $base . '.' . $ext);
        // A different extension than before leaves the old file behind — remove it.
        if ($old && $old !== $path && \Illuminate\Support\Facades\Storage::exists($old)) {
            \Illuminate\Support\Facades\Storage::delete($old);
        }

        $this->profile = $path;
        $this->save();
        return $old;
    }

    public function getProfileUrlAttribute(): ?string
    {
        if (! $this->profile) {
            return null;
        }

        $relative = preg_replace('#^public/#', '', $this->profile);
        $mtime    = @filemtime(storage_path('app/public/' . $relative));

        return asset('storage/' . $relative) . ($mtime ? '?v=' . $mtime : '');
    }

    public function getIsHouseholdHeadAttribute(): bool
    {
        return $this->isHead === 2;
    }

    public function getIsFamilyHeadAttribute(): bool
    {
        return $this->isHead === 1;
    }

    public function citizenIds()
    {
        return $this->hasMany(CitizenId::class, 'citizen_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'eb_citizen_tag', 'citizen_id', 'tag_id');
    }

    /** Every hold ever placed on this citizen, newest first. */
    public function holds()
    {
        return $this->hasMany(CitizenHold::class, 'citizen_id')->orderByDesc('placed_at');
    }

    /** The hold currently in force, if any. While set, no documents may be issued. */
    public function activeHold()
    {
        return $this->hasOne(CitizenHold::class, 'citizen_id')->whereNull('lifted_at')->latest('placed_at');
    }

    public function blotterParties()
    {
        return $this->hasMany(BlotterParty::class, 'citizen_id');
    }

    public function blotters()
    {
        return $this->hasManyThrough(
            Blotter::class,
            BlotterParty::class,
            'citizen_id',
            'id',
            'id',
            'blotter_id'
        );
    }

    public function civilStatus()
    {
        return $this->belongsTo(CivilStatus::class, 'civil_status');
    }

    public function addressZone()
    {
        return $this->belongsTo(Address::class, 'address');
    }

    public function approvalStatus()
    {
        return $this->belongsTo(ApprovalStatus::class, 'approval_status');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'user_id_approved');
    }

    public function archivedBy()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    /**
     * Every table that points at a citizen, as [table => [label, columns]].
     * Used to warn before a permanent delete leaves orphaned references.
     */
    const REFERENCING_TABLES = [
        'eb_blotters'           => ['Blotter case',     ['complainant_citizen_id', 'respondent_citizen_id']],
        'eb_blotter_parties'    => ['Blotter party',    ['citizen_id']],
        'eb_document_requests'  => ['Document request', ['citizen_id']],
        'eb_citizen_ids'        => ['Barangay ID',      ['citizen_id']],
        'eb_clearance'          => ['Clearance',        ['citizen_id']],
        'eb_event_attendance'   => ['Event attendance', ['citizenId']],
        'eb_event_winner'       => ['Event win',        ['citizenId']],
        'eb_inventory_releases' => ['Inventory release',['citizen_id']],
        'eb_inventory_rentals'  => ['Rental',           ['citizen_id']],
        'eb_family'             => ['Family membership',['citizenId']],
        'eb_household'          => ['Household head',   ['citizenHeadId']],
        'eb_citizen_tag'        => ['Tag',              ['citizen_id']],
    ];

    /**
     * Counts of records elsewhere that reference this citizen.
     *
     * @return array<string,int> label => count, omitting anything with none
     */
    public function referenceCounts(): array
    {
        $counts = [];

        foreach (self::REFERENCING_TABLES as $table => [$label, $columns]) {
            if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
                continue;
            }

            $query = \Illuminate\Support\Facades\DB::table($table);

            $query->where(function ($q) use ($columns, $table) {
                foreach ($columns as $column) {
                    if (\Illuminate\Support\Facades\Schema::hasColumn($table, $column)) {
                        $q->orWhere($column, $this->id);
                    }
                }
            });

            $n = $query->count();

            if ($n > 0) {
                $counts[$label] = ($counts[$label] ?? 0) + $n;
            }
        }

        return $counts;
    }

    /**
     * A citizen is verified once they are a registered voter with a precinct
     * number on record — the precinct is the proof, so the flag is derived
     * rather than set by hand.
     */
    public function qualifiesAsVerified(): bool
    {
        return (int) $this->voters === 1 && trim((string) $this->pricinct_no) !== '';
    }

    /**
     * Keep is_verify and verified_at in step with the voter details. The
     * timestamp is set the first time a citizen qualifies and cleared if
     * they stop qualifying.
     */
    protected static function booted(): void
    {
        // Archived citizens are hidden everywhere by default — dashboards,
        // searches, pickers, counts. Only the Inactive Citizens page opts
        // back in, via withInactive() / onlyInactive().
        static::addGlobalScope('active', function (Builder $query) {
            $query->where($query->getModel()->getTable() . '.is_active', 1);
        });

        // Walk-ins (unregistered people who only came for a document) are not
        // residents: hidden from lists, counts, demographics, events and pickers.
        // Only document requests opt back in, via withWalkins().
        static::addGlobalScope('residents', function (Builder $query) {
            $query->where($query->getModel()->getTable() . '.is_walkin', 0);
        });

        static::saving(function (self $citizen) {
            if ($citizen->qualifiesAsVerified()) {
                $citizen->is_verify = 1;
                $citizen->verified_at ??= now();
                return;
            }

            $citizen->is_verify   = 0;
            $citizen->verified_at = null;
        });
    }

    // Scopes

    /**
     * Free-text search over a citizen's name and contact details.
     *
     * The term is split into words and every word must match somewhere, so
     * name parts can be given in any order and the middle name may be left
     * out entirely. "John Patrick Anonuevo", "Anonuevo John Patrick" and
     * "anonuevo dioquino" all find the same person.
     *
     * A term that looks like a contact number or an email is matched against
     * those columns as a whole rather than being split.
     */
    public function scopeSearchName($query, ?string $term)
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            // Whole-term match first: handles emails, phone numbers, and
            // anything typed with punctuation. A scanned QR token is matched
            // exactly, since it is an opaque identifier.
            $whole = str_replace(['%', '_'], ['\%', '\_'], $term);
            $q->where('contact', 'like', "%{$whole}%")
              ->orWhere('email', 'like', "%{$whole}%")
              ->orWhere('qrcode', $term);

            // Then require every word of the term to appear in some name
            // part, in any order.
            $words = preg_split('/[\s,]+/u', $term, -1, PREG_SPLIT_NO_EMPTY);

            if (! $words) {
                return;
            }

            $q->orWhere(function ($q) use ($words) {
                foreach ($words as $word) {
                    $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $word) . '%';

                    $q->where(function ($q) use ($like) {
                        $q->where('fname', 'like', $like)
                          ->orWhere('mname', 'like', $like)
                          ->orWhere('lname', 'like', $like)
                          ->orWhere('suffix', 'like', $like);
                    });
                }
            });
        });
    }

    /** Include archived citizens alongside active ones. */
    public function scopeWithInactive($query)
    {
        return $query->withoutGlobalScope('active');
    }

    /** Archived citizens only — used by the Inactive Citizens page. */
    public function scopeOnlyInactive($query)
    {
        return $query->withoutGlobalScope('active')->where('is_active', 0);
    }

    /** Include walk-ins alongside registered residents (document requests only). */
    public function scopeWithWalkins($query)
    {
        return $query->withoutGlobalScope('residents');
    }

    public function scopeOnlyWalkins($query)
    {
        return $query->withoutGlobalScope('residents')->where('is_walkin', 1);
    }

    /**
     * The citizen's address as one line, built from the structured parts the way
     * the zone expects: subdivisions use Blk/Lot/Ph/street, zones marked "free
     * text" use complete_address, others use house no./street; the zone name is
     * appended. With no zone (e.g. walk-ins) the typed complete_address is used.
     * Most records keep their address only in the parts, so read this — not
     * complete_address — wherever an address is shown or printed.
     */
    public function getFullAddressAttribute(): string
    {
        $zone  = $this->addressZone;
        $parts = [];

        if ($zone) {
            if ($zone->is_subd == 1) {
                if ($this->blk)    $parts[] = 'Blk ' . $this->blk;
                if ($this->lot)    $parts[] = 'Lot ' . $this->lot;
                if ($this->phase)  $parts[] = 'Ph '  . $this->phase;
                if ($this->street) $parts[] = $this->street;
            } elseif ($zone->is_subd == 2) {
                if ($this->complete_address) $parts[] = $this->complete_address;
            } else {
                if ($this->no)     $parts[] = $this->no;
                if ($this->street) $parts[] = $this->street;
            }
            $parts[] = $zone->description;
        } elseif ($this->complete_address) {
            $parts[] = $this->complete_address;
        }

        return implode(', ', array_filter(array_map('trim', $parts)));
    }

    /** Short reference for a walk-in, e.g. WI-00012 — searchable on the request page. */
    public function getWalkinCodeAttribute(): ?string
    {
        return $this->is_walkin ? 'WI-' . str_pad($this->id, 5, '0', STR_PAD_LEFT) : null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 1);
    }

    /**
     * These fields are stored as plaintext. Legacy rows imported from the
     * old production database were encrypted at rest and have since been
     * decrypted in place (see the 2026_09_14 decrypt migration), so this
     * only guards against any stragglers.
     */
    public static function decryptValue(?string $value): ?string
    {
        if ($value === null || $value === '' || ! str_starts_with($value, 'eyJpdiI')) {
            return $value;
        }

        try {
            return Crypt::decrypt($value);
        } catch (DecryptException) {
            return $value;
        }
    }

    // Accessors
    public function getFullNameAttribute()
    {
        $parts = [$this->fname, $this->mname, $this->lname];
        if ($this->suffix) {
            $parts[] = $this->suffix;
        }
        return implode(' ', array_filter($parts));
    }

    public function getAgeAttribute()
    {
        return $this->bday ? $this->bday->age : null;
    }
}
