<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\PnppController;
use App\Http\Controllers\Admin\SatkerController;
use App\Http\Controllers\Admin\PenyakitKronisController;
use App\Http\Controllers\Admin\KunjunganController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root ke dashboard (jika login) atau login (jika guest)
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('login');
});

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Admin Prefix Group
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Profil (self-service): ubah nama & password milik sendiri.
        // Tidak pakai middleware role → semua user login bisa akses datanya sendiri.
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

        // Users Management (Accessible by superadmin and admin)
        Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->middleware('role:superadmin|admin');

        // Roles & Permissions Management (Only accessible by superadmin)
        Route::resource('roles', RoleController::class)->middleware('role:superadmin');
        Route::resource('permissions', PermissionController::class)->middleware('role:superadmin');

        // Data Master (PNPP, Satker, Penyakit Kronis) — pemegang permission "manage master"
        Route::middleware('permission:manage master')->group(function () {
            Route::resource('satker', SatkerController::class)->except('show');
            Route::resource('penyakit', PenyakitKronisController::class)->except('show');
            Route::resource('pnpp', PnppController::class)->except('show');

            // Riwayat kunjungan per PNPP
            Route::get('pnpp/{pnpp}/kunjungan', [PnppController::class, 'kunjungan'])->name('pnpp.kunjungan');
            Route::post('pnpp/{pnpp}/kunjungan', [KunjunganController::class, 'store'])->name('pnpp.kunjungan.store');
            Route::delete('pnpp/{pnpp}/kunjungan/{kunjungan}', [KunjunganController::class, 'destroy'])->name('pnpp.kunjungan.destroy');
        });
    });
});
