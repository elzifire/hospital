<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        return view('dashboard', [
            'user'        => $user,
            'role'        => $user->getRoleNames()->first() ?? 'No Role',
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }
}

