<?php

use App\Http\Controllers\Admin\AutoReplyController;
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
use App\Http\Controllers\Admin\Setting\MessageTemplateController;
use App\Http\Controllers\Admin\Setting\TemplateBiayaController;
use App\Http\Controllers\Admin\Setting\TemplateCategoryController;
use App\Http\Controllers\Admin\Setting\TemplateExportController;
use App\Http\Controllers\Admin\Setting\TemplateMetaSyncController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RegisterPnppController;
use App\Http\Controllers\WhatsAppWebhookController;
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
    // Throttle mencegah spam/bot: menampilkan form & mengirim pendaftaran
    // dibatasi per IP. 429 akan dikembalikan otomatis bila terlewati.
    Route::get('register-pnpp', [RegisterPnppController::class, 'create'])->name('register-pnpp.create')->middleware('throttle:60,1');
    Route::post('register-pnpp', [RegisterPnppController::class, 'store'])->name('register-pnpp.store')->middleware('throttle:20,1');
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

            // Catat kunjungan dari penjadwalan Digital Reminder (mode
            // "Dari Jadwal" di form tambah kunjungan).
            Route::post('kunjungan/catat', [KunjunganController::class, 'catatDariReminder'])->name('kunjungan.catat');

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

            // Hapus permanen penjadwalan — khusus superadmin (soft delete
            // adalah perilaku default destroy). withTrashed() agar jadwal
            // yang sudah dihapus (soft) tetap bisa dihapus permanen.
            Route::delete('digital-reminder/{reminder}/hapus-permanen', [DigitalReminderController::class, 'forceDestroy'])
                ->name('digital-reminder.force-destroy')
                ->withTrashed();

            // Tong sampah: penjadwalan soft-deleted untuk dipulihkan lagi.
            Route::get('digital-reminder/tong-sampah', [DigitalReminderController::class, 'trash'])
                ->name('digital-reminder.trash');

            // Pulihkan penjadwalan yang dihapus (soft delete) — withTrashed()
            // agar baris yang sudah dihapus tetap bisa di-resolve & dipulihkan.
            Route::post('digital-reminder/{reminder}/restore', [DigitalReminderController::class, 'restore'])
                ->name('digital-reminder.restore')
                ->withTrashed();

            // Realisasi kunjungan dari sebuah penjadwalan → status selesai.
            Route::post('digital-reminder/{reminder}/kunjungan', [DigitalReminderController::class, 'catatKunjungan'])
                ->name('digital-reminder.kunjungan');

            // Jadwal ulang: jadwal lama ditandai jadwal_ulang, lalu dibuat
            // baris baru dengan tanggal/jam baru (reschedule).
            Route::get('digital-reminder/{reminder}/jadwal-ulang', [DigitalReminderController::class, 'formJadwalUlang'])
                ->name('digital-reminder.jadwal-ulang');
            Route::post('digital-reminder/{reminder}/jadwal-ulang', [DigitalReminderController::class, 'jadwalUlang'])
                ->name('digital-reminder.jadwal-ulang.store');
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
            Route::get('outreach/{group}/edit', [OutreachController::class, 'edit'])->name('outreach.edit');
            Route::put('outreach/{group}', [OutreachController::class, 'update'])->name('outreach.update');
        });

        // Follow Up: riwayat & generate pesan tindak lanjut (H-1, hari-H, tidak datang)
        // + kirim pesan manual mengikuti pola yang sama dengan Outreach —
        // opsi template dibatasi kategori Follow Up (lihat controller).
        Route::middleware('can:manage follow-up')->group(function () {
            Route::get('follow-up', [FollowUpController::class, 'index'])->name('follow-up.index');
            Route::get('follow-up/create', [FollowUpController::class, 'create'])->name('follow-up.create');
            Route::post('follow-up', [FollowUpController::class, 'store'])->name('follow-up.store');
            Route::post('follow-up/generate', [FollowUpController::class, 'generate'])->name('follow-up.generate');
            Route::get('follow-up/{group}/edit', [FollowUpController::class, 'edit'])->name('follow-up.edit');
            Route::put('follow-up/{group}', [FollowUpController::class, 'update'])->name('follow-up.update');
        });

        // Respon: balasan pesan WhatsApp per nomor telepon (masuk via webhook)
        // + input manual & import Excel/CSV per batch (queue).
        Route::middleware('can:manage respon')->group(function () {
            // Setiap fitur = halaman terpisah (bukan tab) agar kode rapi.
            Route::get('respon/data', [ResponController::class, 'indexData'])->name('respon.data');
            Route::get('respon/manual', [ResponController::class, 'indexManual'])->name('respon.manual');
            Route::get('respon/import', [ResponController::class, 'indexImport'])->name('respon.import');
            Route::get('respon', [ResponController::class, 'index'])->name('respon.index');
            Route::get('respon/poll', [ResponController::class, 'poll'])->name('respon.poll');
            Route::get('respon/konten', [ResponController::class, 'konten'])->name('respon.konten');
            Route::get('respon/pesan-manual', [ResponController::class, 'pesanManual'])->name('respon.pesan-manual');
            Route::post('respon/pesan-manual', [ResponController::class, 'kirimPesanManual'])->name('respon.pesan-manual-kirim');
            Route::get('respon/{nomor}', [ResponController::class, 'show'])->name('respon.show');
            Route::post('respon/manual', [ResponController::class, 'storeManual'])->name('respon.manual-store');
            Route::delete('respon/manual/{responManual}', [ResponController::class, 'destroy'])->name('respon.manual-destroy');
            Route::post('respon/import/upload', [ResponController::class, 'importUpload'])->name('respon.import-upload');
            Route::post('respon/import/confirm', [ResponController::class, 'importConfirm'])->name('respon.import-confirm');
            Route::post('respon/import/cancel', [ResponController::class, 'importCancel'])->name('respon.import-cancel');

            // Balas balasan pasien (teks bebas) + polling timeline via JS
            // (event, tanpa websocket).
            Route::post('respon/{nomor}/balas', [ResponController::class, 'balas'])->name('respon.balas');
            Route::get('respon/{nomor}/timeline', [ResponController::class, 'timeline'])->name('respon.timeline');
            Route::get('respon/{nomor}/media/{balasan}', [ResponController::class, 'media'])->name('respon.media');
        });

        // Bank data Auto Reply — aturan pencocokan pesan masuk → jawaban
        // otomatis. Dikelola khusus superadmin (permission "manage auto-reply").
        Route::prefix('auto-reply')->name('auto-reply.')->middleware('can:manage auto-reply')->group(function () {
            Route::get('/', [AutoReplyController::class, 'index'])->name('index');
            Route::get('create', [AutoReplyController::class, 'create'])->name('create');
            Route::post('/', [AutoReplyController::class, 'store'])->name('store');
            Route::get('{autoReply}/edit', [AutoReplyController::class, 'edit'])->name('edit');
            Route::put('{autoReply}', [AutoReplyController::class, 'update'])->name('update');
            Route::delete('{autoReply}', [AutoReplyController::class, 'destroy'])->name('destroy');
            Route::post('{autoReply}/toggle', [AutoReplyController::class, 'toggle'])->name('toggle');
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

            // Estimasi biaya template pesan — jumlah terpakai dari database,
            // harga satuan sementara di-hardcode di controller.
            Route::get('biaya', [TemplateBiayaController::class, 'index'])->name('biaya');

            // CRUD Kategori — halaman terpisah agar tambah & edit lebih lega.
            Route::get('kategori', [TemplateCategoryController::class, 'index'])->name('kategori.index');
            Route::get('kategori/create', [TemplateCategoryController::class, 'create'])->name('kategori.create');
            Route::post('kategori', [TemplateCategoryController::class, 'store'])->name('kategori.store');
            Route::get('kategori/{kategori}/edit', [TemplateCategoryController::class, 'edit'])->name('kategori.edit');
            Route::put('kategori/{kategori}', [TemplateCategoryController::class, 'update'])->name('kategori.update');
            Route::delete('kategori/{kategori}', [TemplateCategoryController::class, 'destroy'])->name('kategori.destroy');

            // CRUD Template Pesan — pendaftaran lewat sistem, sinkron dengan Meta.
            Route::get('template/create', [MessageTemplateController::class, 'create'])->name('template.create');
            Route::post('template', [MessageTemplateController::class, 'store'])->name('template.store');
            Route::get('template/{template}/edit', [MessageTemplateController::class, 'edit'])->name('template.edit');
            Route::delete('template/{template}', [MessageTemplateController::class, 'destroy'])->name('template.destroy');
            Route::put('template/{template}', [MessageTemplateController::class, 'update'])->name('template.update');
            Route::post('template/{template}/duplicate', [MessageTemplateController::class, 'duplicate'])->name('template.duplicate');
            Route::post('template/sync-meta', [TemplateMetaSyncController::class, 'sync'])->name('template.sync-meta');

            // Export
            Route::get('export', [TemplateExportController::class, 'download'])->name('export.download');
            Route::get('export/template', [TemplateExportController::class, 'template'])->name('export.template');
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

            // Hapus permanen (soft delete sudah ditangani route resource di
            // atas) — khusus superadmin, rute harus resolve baris trashed.
            Route::delete('pnpp/{pnpp}/hapus-permanen', [PnppController::class, 'forceDestroy'])
                ->name('pnpp.force-destroy')
                ->withTrashed();
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

        // Registrasi PNPP: verifikasi pendaftaran publik & persetujuan per poli
        // (akun poli menyetujui bagiannya, admin/superadmin menyetujui semua).
        Route::middleware('can:manage register-pnpp')->group(function () {
            Route::get('register-pnpp', [AdminRegisterPnppController::class, 'index'])->name('register-pnpp.index');
            Route::get('register-pnpp/{registerPnpp}', [AdminRegisterPnppController::class, 'show'])->name('register-pnpp.show');
            Route::patch('register-pnpp/{registerPnpp}/status', [AdminRegisterPnppController::class, 'approve'])->name('register-pnpp.approve');
            Route::patch('register-pnpp/{registerPnpp}/batal-status', [AdminRegisterPnppController::class, 'unapprove'])->name('register-pnpp.unapprove');
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

// Webhook masuk Meta WhatsApp Cloud API — verifikasi endpoint (GET) dan
// penerima balasan/pesan pasien (POST) diproses di
// WhatsAppWebhookController -> WebhookHandler (di luar CSRF, lihat
// bootstrap/app.php, karena Meta menandatangani body dengan HMAC).
Route::get('/whatsapp/webhook', [WhatsAppWebhookController::class, 'verify']);
Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'handle']);
