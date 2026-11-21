<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A picture on the kiosk's start screen carousel. */
class KioskSlide extends Model
{
    protected $table = 'eb_kiosk_slides';

    protected $fillable = ['image', 'caption', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function getUrlAttribute(): string
    {
        return asset('storage/' . preg_replace('#^public/#', '', $this->image));
    }

    public static function forScreen()
    {
        return static::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
    }
}
