<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** A link people use to register themselves as citizens (subject to approval). */
class RegistrationToken extends Model
{
    protected $table = 'eb_registration_tokens';

    protected $fillable = ['token', 'label', 'expires_at', 'max_uses', 'uses', 'is_active', 'created_by'];

    protected $casts = [
        'expires_at' => 'datetime',
        'max_uses'   => 'integer',
        'uses'       => 'integer',
        'is_active'  => 'boolean',
    ];

    public static function newToken(): string
    {
        do {
            $t = Str::random(32);
        } while (static::where('token', $t)->exists());
        return $t;
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function registrations()
    {
        return $this->hasMany(CitizenRegistration::class, 'token_id');
    }

    /** 'active' | 'disabled' | 'expired' | 'full' */
    public function state(): string
    {
        if (!$this->is_active) return 'disabled';
        if ($this->expires_at && now()->gte($this->expires_at)) return 'expired';
        if ($this->max_uses !== null && $this->uses >= $this->max_uses) return 'full';
        return 'active';
    }

    public function isUsable(): bool
    {
        return $this->state() === 'active';
    }

    public function url(): string
    {
        return route('register.citizen', $this->token);
    }

    /** QR code of the link as inline SVG. */
    public function qrSvg(int $size = 220): string
    {
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle($size, 1),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        return (new \BaconQrCode\Writer($renderer))->writeString($this->url());
    }
}
