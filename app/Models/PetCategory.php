<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Kind of pet (Dog, Cat, …). Picked or typed on the pet form, like document purposes.
 */
class PetCategory extends Model
{
    protected $table = 'eb_pet_categories';

    protected $fillable = ['name', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    public function pets()
    {
        return $this->hasMany(Pet::class, 'category_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /** Typed text → category id: reuse a match (any letter case) or add it for next time. */
    public static function resolveId(?string $typed): ?int
    {
        $name = trim(preg_replace('/\s+/', ' ', (string) $typed));
        if ($name === '') {
            return null;
        }
        $name = mb_substr($name, 0, 60);

        $existing = static::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($existing) {
            if (! $existing->is_active) {
                $existing->update(['is_active' => true]);
            }
            return $existing->id;
        }

        return static::create([
            'name'       => mb_strtoupper(mb_substr($name, 0, 1)) . mb_substr($name, 1),
            'is_active'  => true,
            'sort_order' => (int) static::max('sort_order') + 1,
        ])->id;
    }

    /** MingCute icon for the common kinds. */
    public static function iconFor(?string $name): string
    {
        $n = mb_strtolower((string) $name);
        return match (true) {
            str_contains($n, 'dog')                              => 'mgc_dog_line',
            str_contains($n, 'cat')                              => 'mgc_cat_line',
            str_contains($n, 'chick'), str_contains($n, 'rooster'),
            str_contains($n, 'manok')                            => 'mgc_chicken_line',
            str_contains($n, 'bird')                             => 'mgc_bird_line',
            str_contains($n, 'fish')                             => 'mgc_fish_line',
            str_contains($n, 'pig')                              => 'mgc_pig_line',
            default                                              => 'mgc_bone_line',
        };
    }
}
