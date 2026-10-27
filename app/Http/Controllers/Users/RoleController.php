<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Permission;
use App\Role;
use App\RolePermission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $roles = Role::all();
        return view('pages.users.roles', [
            'roles' => $roles,
        ]);
    }

    public function configure($role_id)
    {
        $permissions = Permission::all();
        $role_permissions = RolePermission::where("role_id", $role_id)->get();
        $role = Role::find($role_id);
        return view('pages.users.role-permissions', [
            'permissions' => $permissions,
            'role' => $role,
            'role_permissions' => $role_permissions,
        ]);
    }

    public function store(Request $request)
    {
        $role = new Role;
        $role->name = $request->name;
        $role->save();
        return redirect()->back()->with('status', 'Role Successfully Saved!');
    }

    public function update(Request $request)
    {
        $role = Role::find($request->id);
        $role->name = $request->name;
        $role->update();
        return redirect()->back()->with('status', 'Role Successfully Updated!');
    }
}
