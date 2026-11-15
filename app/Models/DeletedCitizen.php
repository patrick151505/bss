<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A citizen that was permanently removed from eb_citizen. The full original
 * row is kept in `snapshot` so the record remains auditable.
 */
class DeletedCitizen extends Model
{
    protected $table = 'eb_deleted_citizens';

    protected $fillable = [
        'citizen_id', 'fname', 'mname', 'lname', 'suffix', 'bday', 'contact',
        'snapshot', 'reason', 'orphaned', 'deleted_by', 'deleted_at',
    ];

    protected $casts = [
        'snapshot'   => 'array',
        'orphaned'   => 'array',
        'bday'       => 'date',
        'deleted_at' => 'datetime',
    ];

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([$this->fname, $this->mname, $this->lname, $this->suffix])));
    }

    /** Same flexible matching as the live citizen search. */
    public function scopeSearchName(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $whole = str_replace(['%', '_'], ['\%', '\_'], $term);
            $q->where('contact', 'like', "%{$whole}%")
              ->orWhere('reason', 'like', "%{$whole}%");

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
}
