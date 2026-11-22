<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')
            ->addSelect(['last_login_at' => \App\Models\LoginLog::select('created_at')
                ->whereColumn('user_id', 'users.id')->where('event', 'login')
                ->orderByDesc('id')->limit(1)])
            ->withCasts(['last_login_at' => 'datetime'])
            ->orderBy('name')->get();
        $roles = Role::with('permissions')->orderBy('name')->get();
        $landingRoutes = User::landingRoutes();

        // role name => list of permission names, so the landing-page picker can
        // hide pages the selected role has no access to.
        $rolePermissions = $roles->mapWithKeys(
            fn ($role) => [$role->name => $role->permissions->pluck('name')->all()]
        );

        $canAssignRoles = $this->canAssignRoles();
        $roleGiver      = self::ROLE_GIVER;

        return view('users.index', compact('users', 'roles', 'landingRoutes', 'rolePermissions', 'canAssignRoles', 'roleGiver'));
    }

    /** Only the Super Admin may give, change or remove a user's role. */
    const ROLE_GIVER = 'Super Admin';

    private function canAssignRoles(): bool
    {
        return (bool) auth()->user()?->hasRole(self::ROLE_GIVER);
    }

    /**
     * Without the Super Admin role: no role may be sent at all (the form doesn't
     * show the field, so one arriving means a tampered request), and Super Admin
     * accounts can't be edited or switched off — that would let someone take
     * over the account that controls everything.
     */
    private function guardRoleChange(Request $request, ?User $target = null): void
    {
        if ($this->canAssignRoles()) {
            return;
        }
        if ($target && $target->hasRole(self::ROLE_GIVER)) {
            abort(403, 'Only a Super Admin can change a Super Admin account.');
        }
        $current = $target?->roles->first()?->name;
        if ($request->has('role') && (string) $request->input('role') !== (string) $current) {
            abort(403, 'Only the Super Admin can assign roles.');
        }
    }

    public function store(Request $request)
    {
        $this->guardRoleChange($request);
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'nullable|exists:roles,name',
            'landing_route' => [
                'nullable',
                Rule::in(array_keys(User::landingRouteOptions())),
                $this->landingRouteAllowedForRole($request->role),
            ],
        ]);

        $user = User::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'landing_route' => $request->landing_route ?: null,
        ]);

        if ($request->role && $this->canAssignRoles()) {
            $user->syncRoles([$request->role]);
        }

        return back()->with('success', "User \"{$user->name}\" created."
            . ($this->canAssignRoles() ? '' : ' Ask the Super Admin to give this user a role.'));
    }

    public function update(Request $request, User $user)
    {
        $user->load('roles');
        $this->guardRoleChange($request, $user);

        // Without the Super Admin role the user keeps the role they have.
        $role = $this->canAssignRoles() ? $request->input('role') : $user->roles->first()?->name;

        // A Super Admin can't take the role off their own account (they would lock themselves out).
        if ($user->id === auth()->id() && $user->hasRole(self::ROLE_GIVER) && $role !== self::ROLE_GIVER) {
            return back()->with('error', 'You cannot remove the Super Admin role from your own account.');
        }

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role'     => 'nullable|exists:roles,name',
            'landing_route' => [
                'nullable',
                Rule::in(array_keys(User::landingRouteOptions())),
                $this->landingRouteAllowedForRole($role),
            ],
        ]);

        $data = [
            'name'          => $request->name,
            'email'         => $request->email,
            'landing_route' => $request->landing_route ?: null,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        if ($this->canAssignRoles()) {
            $user->syncRoles($role ? [$role] : []);
        }

        return back()->with('success', "User \"{$user->name}\" updated.");
    }

    /**
     * Validation rule: the chosen landing page must be one the given role can
     * actually open, otherwise the user would hit a 403 right after logging in.
     */
    protected function landingRouteAllowedForRole(?string $roleName): \Closure
    {
        return function ($attribute, $value, $fail) use ($roleName) {
            if (! $value) {
                return;
            }

            $permission = User::landingRoutes()[$value]['permission'] ?? null;
            if (! $permission) {
                return; // page needs no permission beyond being logged in
            }

            $role = $roleName ? Role::where('name', $roleName)->first() : null;

            if (! $role || ! $role->permissions->contains('name', $permission)) {
                $label = User::landingRouteOptions()[$value] ?? $value;
                $fail("The selected role has no access to \"{$label}\", so it cannot be used as the landing page.");
            }
        };
    }

    /**
     * Email the user a one-time link to set a new password (valid for 60 minutes).
     * If the email can't be sent (mail not set up), the admin gets the link to
     * hand over instead.
     */
    public function sendResetLink(User $user)
    {
        if ($user->hasRole(self::ROLE_GIVER) && ! $this->canAssignRoles()) {
            abort(403, 'Only a Super Admin can reset a Super Admin account.');
        }

        $token = \Illuminate\Support\Facades\Password::broker()->createToken($user);

        try {
            $user->notify(new \App\Notifications\ResetPasswordLink($token, auth()->user()->name));
        } catch (\Throwable $e) {
            report($e);
            \App\Models\ActivityLog::record('updated', 'user', $user->id, "Password reset link made for {$user->name} (email could not be sent)");
            return back()->with('reset_link', [
                'name' => $user->name,
                'url'  => \App\Notifications\ResetPasswordLink::urlFor($user, $token),
            ]);
        }

        \App\Models\ActivityLog::record('updated', 'user', $user->id, "Password reset email sent to {$user->name} ({$user->email})");
        return back()->with('success', "A reset password email was sent to {$user->email}. The link works for 60 minutes.");
    }

    public function toggleActive(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }
        if ($user->hasRole(self::ROLE_GIVER) && ! $this->canAssignRoles()) {
            abort(403, 'Only a Super Admin can change a Super Admin account.');
        }

        $active = !($user->is_active ?? true);
        $user->update(['is_active' => $active]);

        $label = $active ? 'activated' : 'deactivated';
        return back()->with('success', "User \"{$user->name}\" {$label}.");
    }
}
