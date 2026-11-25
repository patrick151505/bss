<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A named filter combination saved on a list page (see eb_saved_filters). */
class SavedFilter extends Model
{
    protected $table = 'eb_saved_filters';

    protected $fillable = ['user_id', 'module', 'name', 'query', 'is_shared'];

    protected $casts = ['is_shared' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** The user's own filters plus the ones others shared, own first. */
    public static function visibleTo(int $userId, string $module)
    {
        return static::with('user:id,name')
            ->where('module', $module)
            ->where(fn ($q) => $q->where('user_id', $userId)->orWhere('is_shared', true))
            ->orderByRaw('user_id = ? DESC', [$userId])
            ->orderBy('name')
            ->get();
    }
}
