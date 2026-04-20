<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Enums\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('role')->get();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = [
            Role::ADMIN => 'Admin',
            Role::KEPALA_UPA => 'Kepala UPA',
            Role::KEPALA_DIVISI => 'Kepala Divisi Teknis',
            Role::ANALIS => 'Analis',
        ];

        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username'  => 'required|unique:users,username',
            'email'     => 'nullable|email|unique:users,email',
            'full_name' => 'required|string',
            'role_id'   => 'required|integer',
            'password'  => 'required|min:6',
        ]);

        User::create([
            'username'  => $validated['username'],
            'email'     => $validated['email'] ?? null,
            'full_name' => $validated['full_name'],
            'role_id'   => $validated['role_id'],
            'password'  => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully');
    }

    public function toggle(User $user)
    {
        // Prevent disabling Super Admin
        if ($user->role_id == Role::SUPER_ADMIN) {
            return back()->withErrors('Cannot disable Super Admin');
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        return back()->with('success', 'User status updated');
    }

    public function edit(User $user)
    {
        // Prevent editing Super Admin role
        if ($user->role_id == Role::SUPER_ADMIN) {
            abort(403, 'Cannot edit Super Admin');
        }

        $roles = [
            Role::ADMIN => 'Admin',
            Role::KEPALA_UPA => 'Kepala UPA',
            Role::KEPALA_DIVISI => 'Kepala Divisi Teknis',
            Role::ANALIS => 'Analis',
        ];

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        if ($user->role_id == Role::SUPER_ADMIN) {
            abort(403, 'Cannot edit Super Admin');
        }

        $validated = $request->validate([
            'username'  => 'required|unique:users,username,' . $user->user_id . ',user_id',
            'email'     => 'nullable|email|unique:users,email,' . $user->user_id . ',user_id',
            'full_name' => 'required|string',
            'role_id'   => 'required|integer',
        ]);

        $user->update($validated);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully');
    }

    public function editPassword(User $user)
    {
        if ($user->role_id == Role::SUPER_ADMIN) {
            abort(403, 'Cannot reset Super Admin password here');
        }

        return view('users.password', compact('user'));
    }

    public function updatePassword(Request $request, User $user)
    {
        if ($user->role_id == Role::SUPER_ADMIN) {
            abort(403, 'Cannot reset Super Admin password here');
        }

        $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Password reset successfully');
    }

}
