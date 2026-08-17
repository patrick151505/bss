<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'photo',
        'password',
        'is_active',
        'landing_route',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
    ];

    /**
     * Selectable landing pages a user can be redirected to after login.
     * Keys are route names; each entry has the picker label and the permission
     * that guards that route (null = no permission required beyond being logged in).
     */
    public static function landingRoutes(): array
    {
        return [
            'dashboard.activity'    => ['label' => 'Activity Dashboard', 'permission' => null],
            'citizens.index'        => ['label' => 'Citizens',           'permission' => 'citizens.view'],
            'households.index'      => ['label' => 'Households',         'permission' => 'households.view'],
            'citizens.ids.index'    => ['label' => 'Citizen IDs',        'permission' => null],
            'blotters.index'        => ['label' => 'Blotter',            'permission' => 'blotter.view'],
            'events.index'          => ['label' => 'Events',             'permission' => 'events.view'],
            'inventory.items.index' => ['label' => 'Inventory',          'permission' => 'inventory.view'],
            'budget.index'          => ['label' => 'Budget',             'permission' => 'budget.view'],
            'documents.dashboard'   => ['label' => 'Documents',          'permission' => 'documents.view'],
            'birthdays.index'       => ['label' => 'Birthdays',          'permission' => null],
            'settings.index'        => ['label' => 'Settings',           'permission' => null],
        ];
    }

    /**
     * Landing page options as [route name => label], for validation and pickers.
     */
    public static function landingRouteOptions(): array
    {
        return array_map(fn ($o) => $o['label'], static::landingRoutes());
    }

    /**
     * Resolve the route this user should land on after login, or null to use
     * the system default. Returns null when the stored landing page no longer
     * exists or the user's role lacks permission for it.
     */
    public function resolveLandingRoute(): ?string
    {
        $landing = $this->landing_route;

        if (! $landing || ! \Illuminate\Support\Facades\Route::has($landing)) {
            return null;
        }

        $option = static::landingRoutes()[$landing] ?? null;
        if (! $option) {
            return null;
        }

        if ($option['permission'] && ! $this->can($option['permission'])) {
            return null;
        }

        return $landing;
    }
}
