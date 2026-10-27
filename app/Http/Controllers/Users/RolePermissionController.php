<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\RolePermission;
use Illuminate\Http\Request;

class RolePermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function add(Request $request)
    {
        $request->validate([
            'permissions' => 'required'
        ]);
        foreach ($request->permissions as $d) {
            $role_p = new RolePermission;
            $role_p->role_id = $request->role_id;
            $role_p->permission_id = $d;
            $role_p->save();
        }

        return redirect()->back()->with("status", "Permissions Successfully Added To Role");
    }

    public function remove(Request $request)
    {

        $request->validate([
            'permissions1' => 'required'
        ]);
        foreach ($request->permissions1 as $d) {
            $role_p = RolePermission::find($d);
            $role_p->delete();
        }

        return redirect()->back()->with("status", "Permissions Removed From Role");
    }
}
