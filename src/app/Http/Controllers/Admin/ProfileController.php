<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Halaman profil milik user yang sedang login.
     *
     * Ownership: data diambil lewat $request->user() (bukan dari input/id),
     * sehingga user HANYA bisa melihat & mengubah data dirinya sendiri.
     */
    public function edit(Request $request)
    {
        return view('admin.profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Perbarui nama user yang login.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(fn () => $user->update($validated));

        return redirect()
            ->route('admin.profile.edit')
            ->with('success', 'Nama berhasil diperbarui.');
    }

    /**
     * Ubah password user yang login.
     *
     * Wajib memasukkan password saat ini sebagai bukti kepemilikan akun.
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'        => ['required', Password::defaults(), 'confirmed'],
        ]);

        DB::transaction(function () use ($user, $validated) {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
        });

        return redirect()
            ->route('admin.profile.edit')
            ->with('success', 'Password berhasil diubah.');
    }
}
