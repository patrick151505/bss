<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public static function groupedPermissions(): array
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
            'settings'      => 'Settings',
            'users'         => 'Users',
            'activity_logs' => 'Activity Logs',
        ];

        $all    = Permission::orderBy('name')->get()->groupBy(fn($p) => explode('.', $p->name)[0]);
        $groups = [];

        foreach ($labels as $key => $label) {
            if (isset($all[$key])) {
                $groups[$label] = $all[$key];
            }
        }

        return $groups;
    }

    public function index()
    {
        $roles  = Role::withCount('users')->orderBy('name')->get();
        $groups = self::groupedPermissions();
        return view('users.roles', compact('roles', 'groups'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
        ]);

        $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);
        $role->syncPermissions($request->input('permissions', []));

        return back()->with('success', "Role \"{$role->name}\" created.");
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:roles,name,' . $role->id,
        ]);

        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->input('permissions', []));

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
