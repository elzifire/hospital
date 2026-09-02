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
use App\Http\Controllers\Admin\PoliController;
use App\Http\Controllers\Admin\DokterController;
use App\Http\Controllers\Admin\JadwalController;
use App\Http\Controllers\Admin\MasterImportController;
use App\Http\Controllers\Admin\MasterExportController;
use App\Http\Controllers\Admin\DigitalReminderController;
use App\Http\Controllers\Admin\FollowUpController;
use App\Http\Controllers\Admin\OutreachController;
use App\Http\Controllers\Admin\MonitoringController;

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

        // Modul Broadcasting & Layanan (halaman referensi UI)
        Route::get('kunjungan', [KunjunganController::class, 'index'])->name('kunjungan.index');
        Route::get('monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');

        Route::get('digital-reminder', [DigitalReminderController::class, 'index'])->name('digital-reminder.index');
        Route::get('digital-reminder/template', [DigitalReminderController::class, 'template'])->name('digital-reminder.template');
        Route::get('digital-reminder/create', [DigitalReminderController::class, 'create'])->name('digital-reminder.create');
        Route::get('digital-reminder/{id}/edit', [DigitalReminderController::class, 'edit'])->name('digital-reminder.edit');
        Route::get('digital-reminder/import', [DigitalReminderController::class, 'import'])->name('digital-reminder.import');

        Route::get('follow-up', [FollowUpController::class, 'index'])->name('follow-up.index');
        Route::get('follow-up/create', [FollowUpController::class, 'create'])->name('follow-up.create');
        Route::get('follow-up/{id}/edit', [FollowUpController::class, 'edit'])->name('follow-up.edit');
        Route::get('follow-up/import', [FollowUpController::class, 'import'])->name('follow-up.import');

        Route::get('outreach', [OutreachController::class, 'index'])->name('outreach.index');
        Route::get('outreach/create', [OutreachController::class, 'create'])->name('outreach.create');
        Route::get('outreach/{id}/edit', [OutreachController::class, 'edit'])->name('outreach.edit');
        Route::get('outreach/import', [OutreachController::class, 'import'])->name('outreach.import');

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

            // Poli, Dokter & Jadwal
            Route::resource('poli', PoliController::class)->except('show');
            Route::resource('dokter', DokterController::class)->except('show');
            Route::resource('jadwal', JadwalController::class)->except('show');

            // Import & Export data master (semua entitas, per entitas halaman tersendiri)
            Route::get('master/{entity}/import', [MasterImportController::class, 'index'])->name('master.import');
            Route::post('master/{entity}/import/upload', [MasterImportController::class, 'upload'])->name('master.import.upload');
            Route::post('master/{entity}/import/confirm', [MasterImportController::class, 'confirm'])->name('master.import.confirm');
            Route::post('master/{entity}/import/cancel', [MasterImportController::class, 'cancel'])->name('master.import.cancel');
            Route::get('master/{entity}/export', [MasterExportController::class, 'index'])->name('master.export');
            Route::get('master/{entity}/export/download', [MasterExportController::class, 'download'])->name('master.export.download');
            Route::get('master/{entity}/template', [MasterExportController::class, 'template'])->name('master.template');

            // Riwayat kunjungan per PNPP
            Route::get('pnpp/{pnpp}/kunjungan', [PnppController::class, 'kunjungan'])->name('pnpp.kunjungan');
            Route::post('pnpp/{pnpp}/kunjungan', [KunjunganController::class, 'store'])->name('pnpp.kunjungan.store');
            Route::delete('pnpp/{pnpp}/kunjungan/{kunjungan}', [KunjunganController::class, 'destroy'])->name('pnpp.kunjungan.destroy');
        });
    });
});
