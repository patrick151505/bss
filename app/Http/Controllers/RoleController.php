<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    // Grouped permissions for the Roles UI. When $user is given (and is not a
    // Super Admin), only permissions that user actually holds are returned — you
    // can't grant access you don't have yourself.
    public static function groupedPermissions(?\App\Models\User $user = null): array
    {
        $labels = [
            'citizens'      => 'Citizens',
            'households'    => 'Households',
            'blotter'       => 'Blotter',
            'documents'     => 'Documents',
            'inventory'     => 'Inventory',
            'events'        => 'Events',
            'budget'        => 'Budget',
            'tags'          => 'Tags',
            'addresses'     => 'Zones / Addresses',
            'settings'      => 'Settings',
            'users'         => 'Users',
            'roles'         => 'Roles',
            'activity_logs' => 'Activity Logs',
            'issue_document'=> 'Document Types (Issue)',
        ];

        $permissions = Permission::orderBy('name')->get();

        // Restrict to the current user's own permissions (Super Admin sees all).
        if ($user && !$user->hasRole('Super Admin')) {
            $held = $user->getAllPermissions()->pluck('name')->all();
            $permissions = $permissions->whereIn('name', $held);
        }

        $all    = $permissions->groupBy(fn($p) => explode('.', $p->name)[0]);
        $groups = [];

        foreach ($labels as $key => $label) {
            if (isset($all[$key])) {
                $groups[$label] = $all[$key];
            }
        }

        return $groups;
    }

    // Flat list of permission names the given user is allowed to assign.
    public static function assignablePermissionNames(?\App\Models\User $user): array
    {
        if ($user && $user->hasRole('Super Admin')) {
            return Permission::pluck('name')->all();
        }
        return $user ? $user->getAllPermissions()->pluck('name')->all() : [];
    }

    // Map each issue_document.{id} permission name to its document type name,
    // so the Roles page shows the type name instead of a numeric id.
    public static function documentTypeLabels(): array
    {
        return DocumentType::orderBy('name')->pluck('name', 'id')
            ->mapWithKeys(fn ($name, $id) => ['issue_document.' . $id => $name])
            ->all();
    }

    public function index()
    {
        $roles   = Role::withCount('users')->orderBy('name')->get();
        $groups  = self::groupedPermissions(auth()->user());
        $docTypeLabels = self::documentTypeLabels();
        return view('users.roles', compact('roles', 'groups', 'docTypeLabels'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
        ]);

        // Only allow assigning permissions the current user actually holds.
        $assignable = self::assignablePermissionNames($request->user());
        $requested  = array_intersect($request->input('permissions', []), $assignable);

        $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);
        $role->syncPermissions($requested);

        return back()->with('success', "Role \"{$role->name}\" created.");
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:roles,name,' . $role->id,
        ]);

        $assignable = self::assignablePermissionNames($request->user());

        // Permissions on this role the editor CAN manage (their assignable set).
        // Anything outside that set is preserved untouched, so an editor can't
        // strip permissions they aren't even allowed to see.
        $preserved = $role->permissions->pluck('name')
            ->reject(fn ($p) => in_array($p, $assignable))
            ->all();

        // From the submitted list, keep only what the editor is allowed to grant.
        $requested = array_intersect($request->input('permissions', []), $assignable);

        $role->update(['name' => $request->name]);
        $role->syncPermissions(array_values(array_unique(array_merge($preserved, $requested))));

        return back()->with('success', "Role \"{$role->name}\" updated.");
    }

    public function destroy(Role $role)
    {
        if ($role->users()->count() > 0) {
            return back()->with('error', "Cannot delete \"{$role->name}\" — {$role->users()->count()} user(s) are assigned to it.");
        }

        $role->delete();
        return back()->with('success', 'Role deleted.');
    }

    public function permissions(Role $role)
    {
        return response()->json([
            'name'        => $role->name,
            'permissions' => $role->permissions->pluck('name'),
        ]);
    }
}
