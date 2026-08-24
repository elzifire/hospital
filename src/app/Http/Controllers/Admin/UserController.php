<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

use Illuminate\Support\Facades\Auth;


class UserController extends Controller
{
    /**
     * Tampilkan daftar users.
     */
    public function index()
    {
        $users = User::with('roles')->orderBy('name')->get();

        $counts = [
            'total'      => $users->count(),
            'superadmin' => \App\Models\User::role('superadmin')->count(),
            'admin'      => \App\Models\User::role('admin')->count(),
            'user'       => \App\Models\User::role('user')->count(),
            'no_role'    => $users->filter(fn ($u) => $u->roles->isEmpty())->count(),
        ];

        return view('admin.users.index', compact('users', 'counts'));
    }

    /**
     * Tampilkan form tambah user.
     */
    public function create()
    {
        $roles = Role::withCount('permissions')->orderBy('name')->get();

        return view('admin.users.create', compact('roles'));
    }

    /**
     * Simpan user baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role'     => ['nullable', 'string', 'exists:roles,name'],
        ]);

        $user = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => $request->password,
            'email_verified_at' => $request->boolean('verified') ? now() : null,
        ]);

        if ($request->filled('role')) {
            $user->assignRole($request->role);
        }

        return redirect()->route('admin.users.index')
            ->with('success', "User \"{$user->name}\" berhasil ditambahkan.");
    }

    /**
     * Tampilkan form edit user (assign role).
     */
    public function edit(User $user)
    {
        $user->load('roles', 'permissions');
        $roles = Role::withCount('permissions')->with('permissions')->orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update user role.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user->syncRoles([$request->role]);

        return redirect()->route('admin.users.index')
            ->with('success', "Role untuk \"{$user->name}\" berhasil diperbarui menjadi \"{$request->role}\".");
    }

    /**
     * Hapus user.
     */
    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "User \"{$userName}\" berhasil dihapus.");
    }
}
