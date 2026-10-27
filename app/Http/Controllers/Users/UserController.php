<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Role;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $users = User::where("id", "<>", 13)->get();
        $roles = Role::all();
        return view('pages.users.index', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request)
    {
        $user = User::find($request->id);
        $user->email = $request->email;
        $user->name = $request->name;
        $user->status = $request->status;
        $user->role_id = $request->role_id;
        $user->update();
        return redirect()->back()->with('status', 'User Details Successfully Updated!');
    }

    public function store(Request $request)
    {
        if (!$request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', 'min:8', 'confirmed'],


        ]));
        $user = new User;
        $user->email = $request->email;
        $user->name = $request->name;
        $user->password = Hash::make($request->password);
        $user->status = $request->status;
        $user->role_id = $request->role_id;
        $user->save();
        return redirect()->back()->with('status', 'User Details Successfully Updated!');
    }
}
