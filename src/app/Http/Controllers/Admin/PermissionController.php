<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Tampilkan daftar permissions.
     */
    public function index()
    {
        $permissions = Permission::with('roles')->orderBy('name')->get();

        return view('admin.permissions.index', compact('permissions'));
    }

    /**
     * Tampilkan form tambah permission.
     */
    public function create()
    {
        return view('admin.permissions.create');
    }

    /**
     * Simpan permission baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
        ]);

        $permission = DB::transaction(fn () => Permission::create(['name' => $request->name]));

        return redirect()->route('admin.permissions.index')
            ->with('success', "Permission \"{$permission->name}\" berhasil dibuat.");
    }

    /**
     * Tampilkan form edit permission.
     */
    public function edit(Permission $permission)
    {
        $permission->load('roles');

        return view('admin.permissions.edit', compact('permission'));
    }

    /**
     * Update permission.
     */
    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name,' . $permission->id],
        ]);

        DB::transaction(fn () => $permission->update(['name' => $request->name]));

        return redirect()->route('admin.permissions.index')
            ->with('success', "Permission \"{$permission->name}\" berhasil diperbarui.");
    }

    /**
     * Hapus permission.
     */
    public function destroy(Permission $permission)
    {
        $permName = $permission->name;
        $permission->delete();

        return redirect()->route('admin.permissions.index')
            ->with('success', "Permission \"{$permName}\" berhasil dihapus.");
    }
}
