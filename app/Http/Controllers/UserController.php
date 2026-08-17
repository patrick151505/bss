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
        $users = User::with('roles')->orderBy('name')->get();
        $roles = Role::with('permissions')->orderBy('name')->get();
        $landingRoutes = User::landingRoutes();

        // role name => list of permission names, so the landing-page picker can
        // hide pages the selected role has no access to.
        $rolePermissions = $roles->mapWithKeys(
            fn ($role) => [$role->name => $role->permissions->pluck('name')->all()]
        );

        return view('users.index', compact('users', 'roles', 'landingRoutes', 'rolePermissions'));
    }

    public function store(Request $request)
    {
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

        if ($request->role) {
            $user->syncRoles([$request->role]);
        }

        return back()->with('success', "User \"{$user->name}\" created.");
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role'     => 'nullable|exists:roles,name',
            'landing_route' => [
                'nullable',
                Rule::in(array_keys(User::landingRouteOptions())),
                $this->landingRouteAllowedForRole($request->role),
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
        $user->syncRoles($request->role ? [$request->role] : []);

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

    public function toggleActive(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $active = !($user->is_active ?? true);
        $user->update(['is_active' => $active]);

        $label = $active ? 'activated' : 'deactivated';
        return back()->with('success', "User \"{$user->name}\" {$label}.");
    }
}
