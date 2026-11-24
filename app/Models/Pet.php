<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A citizen's pet (Citizen → Pets).
 */
class Pet extends Model
{
    protected $table = 'eb_pets';

    protected $fillable = [
        'citizen_id', 'category_id', 'name', 'breed', 'sex', 'color', 'birth_date',
        'is_neutered', 'last_vaccinated_on', 'photo', 'status', 'notes', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'birth_date'         => 'date',
        'last_vaccinated_on' => 'date',
        'is_neutered'        => 'boolean',
    ];

    /** status => [label, pill classes, dot class] */
    public const STATUSES = [
        'active'   => ['Active',   'bg-green-100 text-green-800',  'bg-green-400'],
        'lost'     => ['Lost',     'bg-yellow-100 text-yellow-800', 'bg-yellow-400'],
        'deceased' => ['Deceased', 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200', 'bg-gray-400'],
    ];

    /** Anti-rabies shots are yearly. */
    public const VACCINE_VALID_DAYS = 365;

    protected static function booted(): void
    {
        // Zone-limited accounts only see pets of citizens in their zones.
        static::addGlobalScope(\App\Support\ZoneAccess::SCOPE, \App\Support\ZoneAccess::byCitizenColumn('citizen_id'));

        static::deleted(function (self $pet) {
            if ($pet->photo && Storage::exists($pet->photo)) {
                Storage::delete($pet->photo);
            }
        });
    }

    public function citizen()
    {
        return $this->belongsTo(Citizen::class, 'citizen_id')->withoutGlobalScopes(['active']);
    }

    public function category()
    {
        return $this->belongsTo(PetCategory::class, 'category_id');
    }

    public function scopeSearch($q, ?string $term)
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $q;
        }
        return $q->where(function ($w) use ($term) {
            $w->where('name', 'like', "%{$term}%")
              ->orWhere('breed', 'like', "%{$term}%")
              ->orWhere('color', 'like', "%{$term}%")
              ->orWhereHas('citizen', fn ($c) => $c->searchName($term));
        });
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo) {
            return null;
        }
        $relative = preg_replace('#^public/#', '', $this->photo);
        $mtime    = @filemtime(storage_path('app/public/' . $relative));
        return asset('storage/' . $relative) . ($mtime ? '?v=' . $mtime : '');
    }

    /** "3 yrs", "5 mos", "2 wks" — or null without a birth date. */
    public function ageText(): ?string
    {
        if (! $this->birth_date) {
            return null;
        }
        $d = $this->birth_date->diff(now());
        if ($d->y > 0) return $d->y . ' yr' . ($d->y > 1 ? 's' : '');
        if ($d->m > 0) return $d->m . ' mo' . ($d->m > 1 ? 's' : '');
        $w = intdiv($d->days, 7);
        return $w > 0 ? $w . ' wk' . ($w > 1 ? 's' : '') : $d->days . ' day' . ($d->days === 1 ? '' : 's');
    }

    /** null = never recorded, true = shot within the last year, false = overdue. */
    public function vaccineCurrent(): ?bool
    {
        if (! $this->last_vaccinated_on) {
            return null;
        }
        return $this->last_vaccinated_on->copy()->addDays(self::VACCINE_VALID_DAYS)->isFuture();
    }

    public function sexLabel(): ?string
    {
        return ['male' => 'Male', 'female' => 'Female'][$this->sex] ?? null;
    }

    /** Store a positioned photo as public/pets/pet-<id>.jpg (replacing the old one). */
    public function savePhoto(\Illuminate\Http\UploadedFile $file): void
    {
        $ext  = strtolower($file->extension() ?: 'jpg');
        $ext  = $ext === 'jpeg' ? 'jpg' : $ext;
        $name = 'pet-' . $this->id . '.' . $ext;
        $path = 'public/pets/' . $name;

        $old = $this->photo;
        $file->storeAs('public/pets', $name);
        if ($old && $old !== $path && Storage::exists($old)) {
            Storage::delete($old);
        }
        $this->photo = $path;
        $this->saveQuietly();
    }
}
