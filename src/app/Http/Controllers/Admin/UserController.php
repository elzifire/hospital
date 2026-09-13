<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poli;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Tampilkan daftar users.
     */
    public function index()
    {
        $users = User::with('roles', 'userDetail.poli')->orderBy('name')->get();

        $counts = [
            'total' => $users->count(),
            'superadmin' => User::role('superadmin')->count(),
            'admin' => User::role('admin')->count(),
            'poli' => User::role('poli')->count(),
            'user' => User::role('user')->count(),
            'no_role' => $users->filter(fn ($u) => $u->roles->isEmpty())->count(),
        ];

        return view('admin.users.index', compact('users', 'counts'));
    }

    /**
     * Tampilkan form tambah user.
     */
    public function create()
    {
        $roles = Role::withCount('permissions')->orderBy('name')->get();
        $polis = Poli::orderBy('nama')->get();

        return view('admin.users.create', compact('roles', 'polis'));
    }

    /**
     * Simpan user baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
            'poli_id' => ['nullable', 'integer', 'exists:polis,id', Rule::requiredIf($request->input('role') === 'poli')],
        ]);

        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'email_verified_at' => $request->boolean('verified') ? now() : null,
            ]);

            if ($request->filled('role')) {
                $user->assignRole($request->role);
            }

            if ($request->role === 'poli' && $request->filled('poli_id')) {
                $user->userDetail()->create(['poli_id' => $request->poli_id]);
            }

            return $user;
        });

        return redirect()->route('admin.users.index')
            ->with('success', "User \"{$user->name}\" berhasil ditambahkan.");
    }

    /**
     * Tampilkan form edit user (assign role + detail poli).
     */
    public function edit(User $user)
    {
        $user->load('roles', 'permissions', 'userDetail.poli');
        $roles = Role::withCount('permissions')->with('permissions')->orderBy('name')->get();
        $polis = Poli::orderBy('nama')->get();

        return view('admin.users.edit', compact('user', 'roles', 'polis'));
    }

    /**
     * Update user role & detail poli.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
            'poli_id' => ['nullable', 'integer', 'exists:polis,id', Rule::requiredIf($request->input('role') === 'poli')],
        ]);

        DB::transaction(function () use ($request, $user) {
            $user->syncRoles([$request->role]);

            if ($request->role === 'poli') {
                $user->userDetail()->updateOrCreate([], ['poli_id' => $request->poli_id]);
            }
        });

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
