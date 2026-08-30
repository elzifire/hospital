# WhatsApp Gateway — Multi-Provider, Redis Queue, Human Anti-Ban & Laravel Integration

<div align="center">

![WhatsApp Gateway](https://img.shields.io/badge/WhatsApp-Gateway-25D366?style=for-the-badge&logo=whatsapp&logoColor=white)
![Express.js](https://img.shields.io/badge/Express.js-5.x-000000?style=for-the-badge&logo=express&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-Shared_Laravel_DB-336791?style=for-the-badge&logo=postgresql&logoColor=white)
![Redis](https://img.shields.io/badge/Redis-BullMQ_Queue-DC382D?style=for-the-badge&logo=redis&logoColor=white)
![Baileys](https://img.shields.io/badge/Baileys-v7.0_ESM-green?style=for-the-badge)

**Solusi WhatsApp Gateway Mandiri Berperforma Tinggi untuk Aplikasi Rumah Sakit / Klinik / Bisnis**  
*Mendukung Multi-Provider (Baileys WebSocket & Fonnte API), Redis Asynchronous Queue, Algoritma Human-Like Anti-Ban, Inbox Outreach & Webhook ke Laravel.*

</div>

---

## 📑 Daftar Isi
- [Arsitektur Sistem](#-arsitektur-sistem)
- [Fitur Utama](#-fitur-utama)
- [Algoritma Human-Like Anti-Ban Meta](#-algoritma-human-like-anti-ban-meta)
- [Struktur Database (PostgreSQL)](#-struktur-database-postgresql)
- [Instalasi & Menjalankan](#-instalasi--menjalankan)
- [Dokumentasi Lengkap API (Endpoints)](#-dokumentasi-lengkap-api-endpoints)
- [Panduan Integrasi Laravel](#-panduan-integrasi-laravel)
- [UI Dashboard & Fitur](#-ui-dashboard--fitur)
- [Saran & Rencana Pengembangan Kedepan (Roadmap)](#-saran--rencana-pengembangan-kedepan-roadmap)

---

## 🏗️ Arsitektur Sistem

```mermaid
flowchart TD
    subgraph Frontend["Frontend & Laravel Integration"]
        UI["Web Dashboard UI\n(Tailwind CSS)"]
        LaravelApp["Laravel Backend\n(Hospital Information System)"]
    end

    subgraph CoreEngine["Express.js Gateway Engine"]
        AuthMid["JWT Auth Middleware\n(Bcrypt Verification on users table)"]
        API["REST API Routes\n(/devices, /broadcasts, /outreach, /logs)"]
        HumanScheduler["Human-Like Random Scheduler\n(Anti-Ban Protection)"]
        LogService["Log Service\n(wa_logs & Quota Monitoring)"]
    end

    subgraph QueueLayer["Redis Asynchronous Queue"]
        BullQueue["BullMQ Producer Queue\n(wa-broadcast-queue)"]
        BullWorker["BullMQ Consumer Worker\n(Concurrency + Random Delays)"]
        CronScheduler["Node-Cron Scheduler\n(Scheduled Jobs & Auto-Retries)"]
    end

    subgraph Providers["Multi-Provider Adapter Layer"]
        PM["Provider Manager"]
        Baileys["Baileys Adapter\n(Direct WebSocket + Multi-file Auth)"]
        Fonnte["Fonnte Adapter\n(REST API Gateway)"]
    end

    subgraph Storage["Persistent Storage"]
        Postgres[(PostgreSQL Database\nShared with Laravel)]
        RedisStore[(Redis Server)]
    end

    UI -->|JWT Auth & REST API| API
    LaravelApp -->|HTTP REST Client| API
    LaravelApp <---|Inbound Webhooks (Outreach)| CoreEngine
    API --> AuthMid
    AuthMid --> Postgres
    API --> HumanScheduler
    HumanScheduler --> BullQueue
    CronScheduler --> BullQueue
    BullQueue --> RedisStore
    BullWorker --> RedisStore
    BullWorker --> PM
    PM --> Baileys
    PM --> Fonnte
    Baileys -->|Incoming Replies| LogService
    LogService --> Postgres
```

---

## 🌟 Fitur Utama

1. **Shared Database Authentication (Laravel PostgreSQL)**:
   - Terhubung langsung ke tabel `users` PostgreSQL milik Laravel.
   - Login memverifikasi password hash bawaan Laravel (`$2y$` / `$2a$`) menggunakan `bcryptjs`.
   - Mengeluarkan JWT Token aman untuk API & Dashboard.

2. **Arsitektur Multi-Provider (Adapter Pattern)**:
   - **`@whiskeysockets/baileys` (v7 ESM)**: Koneksi langsung ke WhatsApp Web via WebSocket tanpa headless browser. Multi-device, auto-reconnect, dan live Base64 QR Code scanner.
   - **`Fonnte API Gateway`**: Provider alternatif berbasis token API Fonnte.
   - **`ProviderManager`**: Mengelola session pool di memori dan otomatis memulihkan koneksi aktif saat server restart.

3. **Redis Queue (BullMQ) & High-Throughput Processing**:
   - Pemrosesan broadcast asinkron di latar belakang menggunakan Redis.
   - Live status tracking per penerima: `pending` ➔ `processing` ➔ `sent` / `failed` (lengkap dengan pesan error).

4. **Outreach (Pesan Balasan Pasien) & Laravel Webhook**:
   - Menangkap pesan masuk/balasan dari pasien via event socket Baileys & webhook Fonnte.
   - Disimpan di tabel `outreach_messages`.
   - Diteruskan otomatis ke endpoint webhook Laravel via HTTP POST.
   - UI Inbox dengan fitur **Quick Reply** untuk membalas langsung dari dashboard.

5. **Log Aktivitas Sentral (`wa_logs`)**:
   - Mencatat setiap pengiriman pesan, kegagalan, dan event penjadwalan.
   - Memantau penggunaan kuota harian per device.

---

## 🛡️ Algoritma Human-Like Anti-Ban Meta

Untuk mencegah nomor WhatsApp terdeteksi sebagai spammer/bot oleh sistem keamanan Meta, gateway menerapkan aturan humanis:

1. **Tidak Dikirim Serentak (No Burst / Non-NOW)**:
   - Pesan broadcast didistribusikan secara acak sepanjang rentang jam aktif (**08:00 – 21:00**).
   - Pengiriman malam hari otomatis digeser mulai pukul 08:00 keesokan harinya.
   - *Pengecualian*: Hanya fitur **Tes Pesan** di tab Devices yang dieksekusi seketika (`NOW`).

2. **Dynamic Jitter & Delays**:
   - Jeda antar pesan diberikan variasi acak (deviasi -25% s/d +40% dari interval dasar), mengaburkan pola ritme robot.

3. **Human Micro-Breaks (Jeda Istirahat Alami)**:
   - Algoritma secara berkala menyisipkan jeda istirahat (6–14 menit) setiap pengiriman 8–14 pesan, meniru aktivitas manusia normal.

4. **Batas Aman Kuota Harian (Maksimal 100 Pesan / Hari per Device)**:
   - Sistem memeriksa total pesan terkirim hari ini di tabel log. Jika kuota telah tercapai, penambahan broadcast pada hari yang sama akan dicegah.

---

## 🗄️ Struktur Database (PostgreSQL)

### 1. `wa_devices` (Daftar Device / Provider)
```sql
CREATE TABLE IF NOT EXISTS wa_devices (
    id SERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    provider VARCHAR(50) NOT NULL DEFAULT 'baileys', -- 'baileys' | 'fonnte'
    phone_number VARCHAR(50),
    provider_config JSONB DEFAULT '{}'::jsonb,
    status VARCHAR(50) DEFAULT 'disconnected',      -- 'disconnected' | 'connecting' | 'connected'
    webhook_url TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

### 2. `broadcasts` (Header Kampanye Broadcast)
```sql
CREATE TABLE IF NOT EXISTS broadcasts (
    id SERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    device_id INTEGER NOT NULL REFERENCES wa_devices(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    media_url TEXT,
    status VARCHAR(50) DEFAULT 'pending',          -- 'pending' | 'processing' | 'completed' | 'scheduled'
    scheduled_at TIMESTAMP WITH TIME ZONE,
    delay_min_ms INTEGER DEFAULT 1500,
    delay_max_ms INTEGER DEFAULT 3500,
    total_recipients INTEGER DEFAULT 0,
    sent_count INTEGER DEFAULT 0,
    failed_count INTEGER DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

### 3. `broadcast_recipients` (Detail Penerima & Status)
```sql
CREATE TABLE IF NOT EXISTS broadcast_recipients (
    id SERIAL PRIMARY KEY,
    broadcast_id INTEGER NOT NULL REFERENCES broadcasts(id) ON DELETE CASCADE,
    phone_number VARCHAR(50) NOT NULL,
    name VARCHAR(255),
    custom_data JSONB DEFAULT '{}'::jsonb,
    status VARCHAR(50) DEFAULT 'pending',          -- 'pending' | 'processing' | 'sent' | 'failed'
    error_message TEXT,
    retry_count INTEGER DEFAULT 0,
    job_id VARCHAR(255),
    sent_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

### 4. `outreach_messages` (Inbox Pesan Masuk / Balasan)
```sql
CREATE TABLE IF NOT EXISTS outreach_messages (
    id SERIAL PRIMARY KEY,
    device_id INTEGER NOT NULL REFERENCES wa_devices(id) ON DELETE CASCADE,
    from_number VARCHAR(50) NOT NULL,
    from_name VARCHAR(255),
    message TEXT,
    message_type VARCHAR(50) DEFAULT 'text',       -- 'text' | 'image' | 'document' | 'audio'
    media_url TEXT,
    raw_data JSONB,
    is_read BOOLEAN DEFAULT FALSE,
    webhook_sent BOOLEAN DEFAULT FALSE,
    webhook_response TEXT,
    received_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

### 5. `wa_logs` (Riwayat & Audit Log Aktivitas)
```sql
CREATE TABLE IF NOT EXISTS wa_logs (
    id SERIAL PRIMARY KEY,
    user_id BIGINT,
    device_id INTEGER REFERENCES wa_devices(id) ON DELETE SET NULL,
    broadcast_id INTEGER REFERENCES broadcasts(id) ON DELETE SET NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'system',    -- 'broadcast' | 'direct' | 'outreach' | 'device'
    level VARCHAR(20) NOT NULL DEFAULT 'info',      -- 'info' | 'warn' | 'error' | 'success'
    action VARCHAR(100) NOT NULL,                  -- 'MESSAGE_SENT', 'MESSAGE_FAILED', 'BROADCAST_SCHEDULED'
    recipient VARCHAR(50),
    message TEXT,
    details JSONB DEFAULT '{}'::jsonb,
    ip_address VARCHAR(50),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

---

## ⚡ Instalasi & Menjalankan

### 1. Kebutuhan Sistem
- **Node.js**: v18.0.0 atau lebih baru (ES Modules).
- **PostgreSQL**: Server aktif dengan tabel `users` milik Laravel.
- **Redis Server**: Server aktif untuk BullMQ Queue.

### 2. Setup Environment (`.env`)
Salin `.env.example` menjadi `.env` lalu sesuaikan kredensial Anda:
```env
PORT=3000
NODE_ENV=development
JWT_SECRET=super_secret_jwt_key_wa_gateway_2026_change_me

# Database PostgreSQL (Database Laravel)
DB_HOST=localhost
DB_PORT=5432
DB_NAME=hospital
DB_USER=postgres
DB_PASSWORD=postgres

# Redis Server (BullMQ Queue)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# Default Webhook
DEFAULT_LARAVEL_WEBHOOK=http://localhost:8000/api/wa/webhook
```

### 3. Migrasi Database
Jalankan migrasi untuk membuat seluruh tabel WA Gateway:
```bash
npm run migrate
```

### 4. Menjalankan Server
```bash
# Mode Development (auto-reload saat file berubah)
npm run dev

# Mode Production
npm start
```

Akses di browser:
- **Login UI**: `http://localhost:3000`
- **Dashboard UI**: `http://localhost:3000/dashboard.html`

---

## 📡 Dokumentasi Lengkap API (Endpoints)

Semua endpoint privat mewajibkan header:  
`Authorization: Bearer <JWT_TOKEN>`

### 1. Autentikasi (`/api/auth`)
| Method | Endpoint | Deskripsi |
|---|---|---|
| `POST` | `/api/auth/login` | Login menggunakan email & password Laravel |
| `GET` | `/api/auth/me` | Ambil profil user saat ini |

**Payload `POST /api/auth/login`:**
```json
{
  "email": "admin@hospital.com",
  "password": "password123"
}
```

---

### 2. Device & Provider (`/api/devices`)
| Method | Endpoint | Deskripsi |
|---|---|---|
| `GET` | `/api/devices` | Daftar semua device milik user |
| `POST` | `/api/devices` | Tambah device baru (Baileys / Fonnte) |
| `GET` | `/api/devices/:id/qr` | Ambil Base64 QR Code (Baileys) |
| `POST` | `/api/devices/:id/connect` | Mulai inisialisasi koneksi / QR |
| `POST` | `/api/devices/:id/disconnect` | Putuskan sesi / Logout WA |
| `DELETE` | `/api/devices/:id` | Hapus device |
| `POST` | `/api/devices/:id/send-test` | Kirim pesan tes langsung (`NOW`) |

---

### 3. Broadcasting (`/api/broadcasts`)
| Method | Endpoint | Deskripsi |
|---|---|---|
| `GET` | `/api/broadcasts` | Daftar riwayat kampanye broadcast |
| `GET` | `/api/broadcasts/metrics` | Metrik agregat (Total, Sent, Failed) |
| `POST` | `/api/broadcasts` | Buat & jalankan broadcast hari ini (disinkronkan acak humanis) |
| `POST` | `/api/broadcasts/schedule` | **Buat broadcast terjadwal untuk tanggal/jam mendatang** |
| `GET` | `/api/broadcasts/:id` | Detail kampanye & status setiap penerima |
| `DELETE` | `/api/broadcasts/:id` | Hapus kampanye |

**Payload `POST /api/broadcasts/schedule` (Penjadwalan):**
```json
{
  "device_id": 1,
  "title": "Pengingat Jadwal Dokter Besok",
  "scheduled_at": "2026-09-01T08:30:00+07:00",
  "message": "Halo {name}, jangan lupa jadwal kontrol Anda besok di Poli Spesialis.",
  "media_url": "https://example.com/brosur.jpg",
  "recipients": [
    { "phone_number": "081234567890", "name": "Budi Santoso" },
    { "phone_number": "089876543210", "name": "Siti Rahma" }
  ]
}
```

---

### 4. Outreach / Inbox Balasan (`/api/outreach`)
| Method | Endpoint | Deskripsi |
|---|---|---|
| `GET` | `/api/outreach` | Daftar pesan masuk (filter: `device_id`, `is_read`, `search`) |
| `GET` | `/api/outreach/stats` | Statistik pesan unread & total kontak |
| `PATCH` | `/api/outreach/:id/read` | Tandai pesan telah dibaca |
| `POST` | `/api/outreach/:id/reply` | Balas langsung pesan pasien via device terkait |

---

### 5. Activity Logs & Kuota (`/api/logs`)
| Method | Endpoint | Deskripsi |
|---|---|---|
| `GET` | `/api/logs` | Daftar audit log aktivitas (filter: `type`, `level`, `search`) |
| `GET` | `/api/logs/quota/:deviceId` | Cek sisa kuota harian device (maksimal 100 pesan/hari) |

---

## 💻 Panduan Integrasi Laravel

### 1. Buat Service Class di Laravel: `app/Services/WhatsAppGatewayService.php`

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppGatewayService
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = config('services.wa_gateway.url', 'http://localhost:3000');
        $this->token   = config('services.wa_gateway.token');
    }

    /**
     * Kirim pesan langsung (NOW / Verifikasi / Tes)
     */
    public function sendDirectMessage(int $deviceId, string $to, string $message): array
    {
        $response = Http::withToken($this->token)
            ->post("{$this->baseUrl}/api/devices/{$deviceId}/send-test", [
                'to'      => $to,
                'message' => $message,
            ]);

        return $response->json();
    }

    /**
     * Kirim broadcast hari ini dengan distribusi acak humanis
     */
    public function sendBroadcast(int $deviceId, string $title, string $message, array $recipients, ?string $mediaUrl = null): array
    {
        $response = Http::withToken($this->token)
            ->post("{$this->baseUrl}/api/broadcasts", [
                'device_id' => $deviceId,
                'title'     => $title,
                'message'   => $message,
                'media_url' => $mediaUrl,
                'recipients'=> $recipients,
            ]);

        return $response->json();
    }

    /**
     * Jadwalkan broadcast untuk tanggal/waktu tertentu
     */
    public function scheduleBroadcast(int $deviceId, string $title, string $scheduledAt, string $message, array $recipients, ?string $mediaUrl = null): array
    {
        $response = Http::withToken($this->token)
            ->post("{$this->baseUrl}/api/broadcasts/schedule", [
                'device_id'    => $deviceId,
                'title'        => $title,
                'scheduled_at' => $scheduledAt,
                'message'      => $message,
                'media_url'    => $mediaUrl,
                'recipients'   => $recipients,
            ]);

        return $response->json();
    }
}
```

### 2. Menerima Webhook Balasan Pasien di Laravel: `routes/api.php`

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

Route::post('/wa/webhook', function (Request $request) {
    $senderPhone = $request->input('from');       // Nomor pengirim (misal: 628123456789)
    $senderName  = $request->input('sender_name'); // Nama profil WhatsApp
    $messageText = $request->input('message');     // Isi teks pesan
    $deviceId    = $request->input('device_id');
    $receivedAt  = $request->input('received_at');

    Log::info("Pesan masuk dari {$senderPhone} ({$senderName}): {$messageText}");

    // CONTOH: Auto-confirm jadwal kontrol pasien jika membalas "YA" atau "1"
    if (in_array(strtoupper(trim($messageText)), ['YA', '1', 'KONFIRMASI'])) {
        // App\Models\Appointment::where('patient_phone', $senderPhone)->update(['status' => 'confirmed']);
    }

    return response()->json(['status' => 'success']);
});
```

---

## 🚀 Saran & Rencana Pengembangan Kedepan (Roadmap)

Berikut adalah beberapa usulan fitur strategis untuk pengembangan tahap berikutnya:

1. **Multi-Device Round-Robin Load Balancing**:
   - Jika memiliki 5 nomor WhatsApp (device), sistem dapat membagi 500 pesan secara otomatis (100 pesan per nomor) secara merata dan aman.

2. **AI-Powered Auto-Reply Chatbot (LLM / OpenAI / Gemini)**:
   - Integrasikan model AI pada endpoint Outreach untuk otomatis menjawab pertanyaan umum pasien (tanya jadwal dokter, lokasi klinik, tata cara pendaftaran) 24/7.

3. **Interactive Buttons & List Messages (WhatsApp Cloud API Adapter)**:
   - Menambahkan adapter ketiga untuk WhatsApp Cloud API resmi (Meta BSP) untuk mendukung pesan bertombol interaktif (*Call to Action / Quick Reply Buttons*).

4. **Contact Grouping & Segmentasi Pasien**:
   - Fitur grup kontak (misal: Pasien Kronis, Pasien Poli Gigi, Ibu Hamil) agar broadcast dapat dipilih berdasarkan segmen tanpa perlu copy-paste nomor berulang kali.

5. **Device Health Watchdog & Auto-Alert**:
   - Mengirim notifikasi email / Telegram ke IT Administrator jika ada sesi WhatsApp yang mendadak terputus (*logged out*).
