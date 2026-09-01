# Hospital — Sistem Informasi Manajemen Rumah Sakit

Aplikasi web untuk mengelola administrasi rumah sakit: pengguna & hak akses (RBAC), data master (PNPP, satker, penyakit kronis), jadwal dokter & poli, serta import/export data Excel/CSV dengan antrean (queue) Redis.

Dibangun dengan **Laravel 12**, **Tailwind CSS 4**, **Alpine.js**, dan **SweetAlert2**.

## Daftar Isi

- [Tech Stack](#tech-stack)
- [Fitur](#fitur)
- [Kebutuhan Sistem](#kebutuhan-sistem)
- [Instalasi](#instalasi)
- [Konfigurasi Queue (Redis)](#konfigurasi-queue-redis)
- [Akun Default](#akun-default)
- [Import & Export](#import--export)
- [Struktur Direktori](#struktur-direktori)
- [Lisensi](#lisensi)

## Tech Stack

| Lapisan    | Teknologi |
|------------|-----------|
| Backend    | Laravel 12 (PHP 8.2+) |
| Database   | PostgreSQL (default di `.env`), kompatibel dengan MySQL/SQLite |
| RBAC       | [spatie/laravel-permission](https://spatie.be/docs/laravel-permission) |
| Frontend   | Blade, Tailwind CSS 4, Alpine.js, SweetAlert2 |
| Spreadsheet| [phpoffice/phpspreadsheet](https://github.com/PHPOffice/PhpSpreadsheet) |
| Queue      | Redis (via `predis/predis`) |

## Fitur

- **Autentikasi** — login/logout, profil pribadi (ganti nama & password dengan verifikasi password lama).
- **Manajemen Pengguna** — CRUD user + assign role.
- **Manajemen Role & Permission** — role terproteksi (superadmin), permission grouping.
- **Data Master**:
  - **PNPP** — data pegawai/pasien (NIP, No. BPJS, satker, usia dihitung dari tanggal lahir, jenis kelamin, penyakit kronis, riwayat kunjungan).
  - **Satker** — satuan kerja.
  - **Penyakit Kronis** — master jenis penyakit.
- **Jadwal Dokter & Poli**:
  - **Poli** — poliklinik (1 dokter = 1 poli).
  - **Dokter** — data dokter + spesialisasi.
  - **Jadwal** — slot hari & jam praktik per dokter.
- **Import & Export (Excel/CSV)** — semua data master bisa diimport/diexport, dengan pratinjau yang **bisa diedit langsung** sebelum diproses. Import berjalan di background melalui **Redis queue**.

## Kebutuhan Sistem

- PHP >= 8.2 dengan ekstensi `pdo_pgsql` (atau `pdo_mysql`/`pdo_sqlite`), `mbstring`, `openssl`, `gd`, `zip`, `xml`.
- Composer 2.
- Node.js + npm (untuk build asset Vite).
- Redis server (untuk queue import/export).
- PostgreSQL (atau database lain sesuai konfigurasi).

## Instalasi

```bash
# 1. Clone & masuk direktori proyek
git clone <repo-url> hospital
cd hospital

# 2. Install dependensi PHP
composer install

# 3. Salin environment & set konfigurasi
cp .env.example .env
php artisan key:generate

# 4. Sesuaikan .env (koneksi database, Redis, dll.)
#    DB_CONNECTION=pgsql  (atau mysql/sqlite)
#    REDIS_CLIENT=predis
#    QUEUE_CONNECTION=redis

# 5. Migrasi & seed data awal
php artisan migrate --seed

# 6. Install & build asset frontend
npm install
npm run build

# 7. Jalankan server
php artisan serve
```

## Konfigurasi Queue (Redis)

Import/export data berjalan di background menggunakan queue Redis. Pastikan:

1. Redis berjalan: `redis-server`
2. Pada `.env`:
   ```
   QUEUE_CONNECTION=redis
   REDIS_CLIENT=predis
   REDIS_HOST=127.0.0.1
   REDIS_PORT=6379
   REDIS_QUEUE=default
   REDIS_QUEUE_CONNECTION=default
   ```
3. Jalankan worker:
   ```bash
   php artisan queue:work
   ```

## Akun Default

Setelah `php artisan db:seed`, tersedia akun berikut (password: `password`):

| Role       | Email                 |
|------------|-----------------------|
| Superadmin | `superadmin@gmail.com` |
| Admin      | `admin@gmail.com`      |
| User       | `user@gmail.com`       |

## Import & Export

Setiap entitas data master memiliki halaman **Import** dan **Export** tersendiri.

**Import:**
1. Buka menu entitas → tombol **Import**.
2. Unduh **Template Excel/CSV** (berisi header + 1 baris contoh).
3. Isi file sesuai template, lalu upload (mendukung `.xlsx`, `.xls`, `.csv`).
4. Pada **pratinjau**, data bisa **diedit langsung** dengan mengklik sel tabel.
5. Klik **Proses Import** → data diproses di background (Redis queue), status diperbarui otomatis.

**Export:**
1. Buka menu entitas → tombol **Export**.
2. Unduh seluruh data sebagai **Excel (.xlsx)** atau **CSV**.

**Aturan kolom/relasi** (import):
- `satker`, `penyakit`, `poli` → kode (opsional) + nama (wajib).
- `dokter` → nama (wajib) + poli (nama, harus sudah ada) + spesialisasi (opsional).
- `jadwal` → dokter (nama, harus sudah ada) + hari (Senin–Minggu) + jam mulai/selesai (`HH:mm`).
- `pnpp` → nama & NIP/NRP (wajib; NIP/NRP menjadi kunci unik, bila sama datanya diperbarui) + status kepegawaian, pangkat, jabatan, satker (nama — otomatis dibuat bila belum ada), satuan kerja, bagian, email, alamat, No. BPJS (13 digit), No. HP, tanggal lahir, jenis kelamin (boleh kosong), status aktif, dan penyakit kronis (dipisah koma).
- NIP/NRP diterima sebagai teks bebas (tidak dinormalkan ke digit); No. BPJS otomatis dinormalkan ke 13 digit (notasi ilmiah/float Excel ikut dikonversi); No. HP dinormalkan ke format `08…`.

## Struktur Direktori

```
app/
├─ Http/Controllers/Admin/   # User, Role, Permission, Profile, Pnpp, Satker,
│                            # PenyakitKronis, Poli, Dokter, Jadwal,
│                            # MasterImport, MasterExport
├─ Jobs/ImportMasterJob.php  # Import background (queue)
├─ Models/                   # User, Satker, Pnpp, PenyakitKronis, Kunjungan,
│                            # Poli, Dokter, Jadwal
└─ Support/                  # MasterRegistry, SheetHelper, CsvHelper
database/
├─ migrations/               # Skema database
└─ seeders/                  # RolePermission, User, MasterData, MasterPermission
resources/views/
├─ layouts/app.blade.php     # Layout utama (sidebar + topbar)
└─ admin/                    # Halaman-halaman modul
```

## Lisensi

Proyek ini bersifat internal. Lisensi: [MIT](https://opensource.org/licenses/MIT).
