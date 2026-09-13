<?php

use App\Http\Controllers\Admin\BroadcastLogController;
use App\Http\Controllers\Admin\DigitalReminderController;
use App\Http\Controllers\Admin\DokterController;
use App\Http\Controllers\Admin\FollowUpController;
use App\Http\Controllers\Admin\JadwalController;
use App\Http\Controllers\Admin\KunjunganController;
use App\Http\Controllers\Admin\MasterExportController;
use App\Http\Controllers\Admin\MasterImportController;
use App\Http\Controllers\Admin\Monitoring\MonitoringController;
use App\Http\Controllers\Admin\Monitoring\ReportController;
use App\Http\Controllers\Admin\Monitoring\ReportExportController;
use App\Http\Controllers\Admin\OutreachController;
use App\Http\Controllers\Admin\PenyakitKronisController;
use App\Http\Controllers\Admin\PenyakitMenahunController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PnppController;
use App\Http\Controllers\Admin\PoliController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RegisterPnppController as AdminRegisterPnppController;
use App\Http\Controllers\Admin\ResponController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SatkerController;
use App\Http\Controllers\Admin\Setting\BroadcastRuleController;
use App\Http\Controllers\Admin\Setting\MessageTemplateController;
use App\Http\Controllers\Admin\Setting\TemplateCategoryController;
use App\Http\Controllers\Admin\Setting\TemplateExportController;
use App\Http\Controllers\Admin\Setting\TemplateImportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RegisterPnppController;
use Illuminate\Support\Facades\Route;

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

    // Pendaftaran PNPP publik — bisa diakses tanpa login.
    Route::get('register-pnpp', [RegisterPnppController::class, 'create'])->name('register-pnpp.create');
    Route::post('register-pnpp', [RegisterPnppController::class, 'store'])->name('register-pnpp.store');
});

// Webhook WhatsApp lama (WAHA) dihapus — fokus pengiriman pesan.

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Admin Prefix Group
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Modul Kunjungan: riwayat berobat pasien PNPP — satu pasien bisa
        // mengunjungi beberapa poli dalam satu tanggal (1 baris per poli).
        Route::middleware('can:manage kunjungan')->group(function () {
            Route::get('kunjungan', [KunjunganController::class, 'index'])->name('kunjungan.index');
            Route::get('kunjungan/create', [KunjunganController::class, 'create'])->name('kunjungan.create');
            Route::post('kunjungan', [KunjunganController::class, 'store'])->name('kunjungan.store');

            // Riwayat kunjungan satu PNPP (tambah/edit/hapus baris poli).
            Route::get('pnpp/{pnpp}/kunjungan', [PnppController::class, 'kunjungan'])->name('pnpp.kunjungan');
            Route::post('pnpp/{pnpp}/kunjungan', [KunjunganController::class, 'storeUntukPasien'])->name('pnpp.kunjungan.store');
            Route::get('pnpp/{pnpp}/kunjungan/{kunjungan}/edit', [KunjunganController::class, 'edit'])->name('pnpp.kunjungan.edit');
            Route::put('pnpp/{pnpp}/kunjungan/{kunjungan}', [KunjunganController::class, 'update'])->name('pnpp.kunjungan.update');
            Route::delete('pnpp/{pnpp}/kunjungan/{kunjungan}', [KunjunganController::class, 'destroy'])->name('pnpp.kunjungan.destroy');
        });

        // Modul Monitoring (laporan): hub kartu + detail laporan per entitas.
        // Detail & export mendukung pagination sisi server, filter, search,
        // dan unduhan xlsx/csv yang mengikuti filter aktif. Tiap laporan
        // terkunci permission fiturnya (lihat MonitoringRegistry).
        Route::prefix('monitoring')->name('monitoring.')->group(function () {
            Route::get('/', [MonitoringController::class, 'index'])->name('index');
            Route::get('report/{entity}', [ReportController::class, 'show'])->name('report.show');
            Route::get('report/{entity}/export', [ReportExportController::class, 'download'])->name('report.export');
        });

        // Modul broadcast — tiap modul terkunci permission fiturnya sendiri.
        // Digital Reminder: CRUD penjadwalan kunjungan (murni jadwal).
        Route::middleware('can:manage digital-reminder')->group(function () {
            Route::resource('digital-reminder', DigitalReminderController::class)
                ->except('show')
                ->parameters(['digital-reminder' => 'reminder']);

            // Realisasi kunjungan dari sebuah penjadwalan → status selesai.
            Route::post('digital-reminder/{reminder}/kunjungan', [DigitalReminderController::class, 'catatKunjungan'])
                ->name('digital-reminder.kunjungan');
        });

        // Outreach: riwayat & generate pesan undangan jadwal (rule H-7, H-1)
        // + kirim pesan manual mengikuti metode resource Laravel:
        //   create → tampilkan form pilih penerima, store → simpan & kirim.
        // Jenis follow-up dicek izinnya di controller.
        Route::middleware('can:manage outreach')->group(function () {
            Route::get('outreach', [OutreachController::class, 'index'])->name('outreach.index');
            Route::get('outreach/create', [OutreachController::class, 'create'])->name('outreach.create');
            Route::post('outreach', [OutreachController::class, 'store'])->name('outreach.store');
            Route::post('outreach/generate', [OutreachController::class, 'generate'])->name('outreach.generate');
        });

        // Follow Up: riwayat & generate pesan tindak lanjut (H-1, hari-H, tidak datang).
        Route::middleware('can:manage follow-up')->group(function () {
            Route::get('follow-up', [FollowUpController::class, 'index'])->name('follow-up.index');
            Route::post('follow-up/generate', [FollowUpController::class, 'generate'])->name('follow-up.generate');
        });

        // Respon: balasan pesan WhatsApp per nomor telepon (masuk via webhook)
        Route::middleware('can:manage respon')->group(function () {
            Route::get('respon', [ResponController::class, 'index'])->name('respon.index');
            Route::get('respon/{nomor}', [ResponController::class, 'show'])->name('respon.show');
        });

        // Aksi atas riwayat pesan (dipakai lintas modul broadcast):
        // detail, kirim ulang (gagal), kirim sekarang (antrean), dan
        // pembatalan — permission dicek per jenis pesan di controller.
        Route::get('broadcast/log/{log}', [BroadcastLogController::class, 'show'])->name('broadcast.show');
        Route::post('broadcast/log/{log}/kirim-ulang', [BroadcastLogController::class, 'kirimUlang'])->name('broadcast.kirim-ulang');
        Route::post('broadcast/kirim/{jenis}', [BroadcastLogController::class, 'kirimSekarang'])->name('broadcast.kirim-sekarang');
        Route::delete('broadcast/log/{log}/batalkan', [BroadcastLogController::class, 'batalkan'])->name('broadcast.batalkan');

        // Pengaturan Template Pesan & Kategori (khusus pengelola template)
        Route::prefix('setting')->name('setting.')->middleware('can:manage template')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->name('index');
            Route::get('/template', [SettingController::class, 'template'])->name('template');
            Route::put('/aturan/{aturan}', [BroadcastRuleController::class, 'update'])->name('aturan.update');

            // CRUD Kategori
            Route::post('kategori', [TemplateCategoryController::class, 'store'])->name('kategori.store');
            Route::put('kategori/{kategori}', [TemplateCategoryController::class, 'update'])->name('kategori.update');
            Route::delete('kategori/{kategori}', [TemplateCategoryController::class, 'destroy'])->name('kategori.destroy');

            // CRUD Template Pesan
            Route::post('template', [MessageTemplateController::class, 'store'])->name('template.store');
            Route::put('template/{template}', [MessageTemplateController::class, 'update'])->name('template.update');
            Route::delete('template/{template}', [MessageTemplateController::class, 'destroy'])->name('template.destroy');
            Route::post('template/{template}/duplicate', [MessageTemplateController::class, 'duplicate'])->name('template.duplicate');

            // Export & Import
            Route::get('export', [TemplateExportController::class, 'download'])->name('export.download');
            Route::get('export/template', [TemplateExportController::class, 'template'])->name('export.template');

            Route::get('import', [TemplateImportController::class, 'index'])->name('import.index');
            Route::post('import/upload', [TemplateImportController::class, 'upload'])->name('import.upload');
            Route::post('import/confirm', [TemplateImportController::class, 'confirm'])->name('import.confirm');
            Route::post('import/cancel', [TemplateImportController::class, 'cancel'])->name('import.cancel');
        });

        // Profil (self-service): ubah nama & password milik sendiri.
        // Tidak pakai middleware role → semua user login bisa akses datanya sendiri.
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

        // Manajemen Pengguna
        Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->middleware('can:manage users');

        // Manajemen Role & Permission (khusus pemegang "manage roles")
        Route::resource('roles', RoleController::class)->middleware('can:manage roles');
        Route::resource('permissions', PermissionController::class)->middleware('can:manage roles');

        // Data Master — tiap entitas terkunci permission fiturnya sendiri.
        Route::middleware('can:manage pnpp')->group(function () {
            Route::resource('pnpp', PnppController::class)->except('show');
        });

        Route::middleware('can:manage satker')->group(function () {
            Route::resource('satker', SatkerController::class)->except('show');
        });

        Route::middleware('can:manage penyakit')->group(function () {
            Route::resource('penyakit', PenyakitKronisController::class)->except('show');
            Route::resource('penyakit-menahun', PenyakitMenahunController::class)
                ->except('show')
                ->parameters(['penyakit-menahun' => 'penyakitMenahun']);
        });

        Route::middleware('can:manage poli')->group(function () {
            Route::resource('poli', PoliController::class)->except('show');
        });

        // Registrasi PNPP: verifikasi pendaftaran publik & persetujuan.
        Route::middleware('can:manage register-pnpp')->group(function () {
            Route::get('register-pnpp', [AdminRegisterPnppController::class, 'index'])->name('register-pnpp.index');
            Route::get('register-pnpp/{registerPnpp}', [AdminRegisterPnppController::class, 'show'])->name('register-pnpp.show');
            Route::patch('register-pnpp/{registerPnpp}/status', [AdminRegisterPnppController::class, 'approve'])->name('register-pnpp.approve');
            Route::delete('register-pnpp/{registerPnpp}', [AdminRegisterPnppController::class, 'destroy'])->name('register-pnpp.destroy');
        });

        Route::middleware('can:manage dokter')->group(function () {
            Route::resource('dokter', DokterController::class)->except('show');
        });

        Route::middleware('can:manage jadwal')->group(function () {
            Route::resource('jadwal', JadwalController::class)->except('show');
        });

        // Import & Export data master: permission per entitas dicek di
        // controller (MasterRegistry menyimpan permission tiap entitas).
        Route::get('master/{entity}/import', [MasterImportController::class, 'index'])->name('master.import');
        Route::post('master/{entity}/import/upload', [MasterImportController::class, 'upload'])->name('master.import.upload');
        Route::post('master/{entity}/import/confirm', [MasterImportController::class, 'confirm'])->name('master.import.confirm');
        Route::post('master/{entity}/import/cancel', [MasterImportController::class, 'cancel'])->name('master.import.cancel');
        Route::get('master/{entity}/export', [MasterExportController::class, 'index'])->name('master.export');
        Route::get('master/{entity}/export/download', [MasterExportController::class, 'download'])->name('master.export.download');
        Route::get('master/{entity}/template', [MasterExportController::class, 'template'])->name('master.template');
    });
});

// Route::get('/whatsapp/webhook', function (\Illuminate\Http\Request $request) {
//     $verifyToken = 'ELZIFIRE_WA_VERIFY_2026';

//     if (
//         $request->query('hub_mode') === 'subscribe' &&
//         $request->query('hub_verify_token') === $verifyToken
//     ) {
//         return response($request->query('hub_challenge'), 200);
//     }

//     return response('Forbidden', 403);
// });

// Route::post('/whatsapp/webhook', function (\Illuminate\Http\Request $request) {
//     Log::info('WhatsApp Webhook', $request->all());

//     return response()->json([
//         'status' => 'ok'
//     ]);
// });
