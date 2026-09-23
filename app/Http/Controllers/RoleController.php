<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('roles.index', [
            'roles' => Role::with('permissions:id')->withCount('users')->orderBy('id')->get(),
            'permissions' => Permission::orderBy('module')->orderBy('id')->get()->groupBy('module'),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        if ($role->slug === Permissions::ROLE_SUPER_ADMIN) {
            return back()->with('error', 'Super Admin always has every permission.');
        }

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $role->permissions()->sync($data['permissions'] ?? []);
        $role->update(['description' => $data['description'] ?? $role->description]);

        activity('updated', 'settings', "{$request->user()->name} updated permissions of role {$role->name}", $role);

        return back()->with('success', "Permissions for {$role->name} saved.");
    }
}
