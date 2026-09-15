<?php

namespace Tests\Feature;

use App\Models\AutoReply;
use App\Models\MessageLog;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Bank data Auto Reply — aturan pencocokan pesan masuk → jawaban otomatis.
 * Hanya pemegang permission "manage auto-reply" (khusus superadmin) yang
 * bisa mengelola; webhook memakai aturan aktif yang cocok di jam
 * operasional untuk membalas otomatis.
 */
class AutoReplyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        Config::set('whatsapp.webhook.verify_token', 'token_uji');
        Config::set('whatsapp.webhook.app_secret', 'rahasia_uji');
    }

    protected function superadmin(): User
    {
        return User::where('email', 'superadmin@gmail.com')->firstOrFail();
    }

    protected function admin(): User
    {
        return User::where('email', 'admin@gmail.com')->firstOrFail();
    }

    protected function userBiasa(): User
    {
        return User::where('email', 'user@gmail.com')->firstOrFail();
    }

    // ── Akses (permission "manage auto-reply", khusus superadmin) ──

    #[Test]
    public function superadmin_bisa_mengakses_halaman_auto_reply(): void
    {
        $this->actingAs($this->superadmin())
            ->get(route('admin.auto-reply.index'))
            ->assertOk()
            ->assertSee('Auto Reply');
    }

    #[Test]
    public function admin_ditolak_dari_auto_reply(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.auto-reply.index'))
            ->assertForbidden();
    }

    #[Test]
    public function user_biasa_ditolak_dari_auto_reply(): void
    {
        $this->actingAs($this->userBiasa())
            ->get(route('admin.auto-reply.index'))
            ->assertForbidden();
    }

    // ── CRUD ──

    #[Test]
    public function superadmin_menambah_aturan_auto_reply(): void
    {
        $this->actingAs($this->superadmin())
            ->post(route('admin.auto-reply.store'), [
                'kategori' => 'operasional',
                'nama' => 'Jam Besuk',
                'cara_cocok' => 'mengandung',
                'pola' => 'jam besuk',
                'isi' => 'Jam besuk tersedia dari pukul 14.00 sampai 16.00.',
                'prioritas' => 5,
            ])
            ->assertRedirect(route('admin.auto-reply.index'));

        $this->assertDatabaseHas('auto_replies', [
            'nama' => 'Jam Besuk',
            'kategori' => 'operasional',
            'cara_cocok' => 'mengandung',
            'pola' => 'jam besuk',
            'prioritas' => 5,
            'aktif' => true,
        ]);
    }

    #[Test]
    public function superadmin_mengubah_aturan_auto_reply(): void
    {
        $aturan = AutoReply::create([
            'kategori' => 'operasional',
            'nama' => 'Jam Operasional',
            'cara_cocok' => 'mengandung',
            'pola' => 'jam operasional',
            'isi' => 'Jam operasional tersedia dari jam 8 pagi sampai jam 10 malam.',
        ]);

        $this->actingAs($this->superadmin())
            ->put(route('admin.auto-reply.update', $aturan), [
                'kategori' => 'operasional',
                'nama' => 'Jam Operasional (Baru)',
                'cara_cocok' => 'sama',
                'pola' => 'jam operasional?',
                'isi' => 'Kami buka setiap hari pukul 08.00–22.00.',
                'prioritas' => 2,
            ])
            ->assertRedirect(route('admin.auto-reply.index'));

        $this->assertDatabaseHas('auto_replies', [
            'id' => $aturan->id,
            'nama' => 'Jam Operasional (Baru)',
            'cara_cocok' => 'sama',
            'prioritas' => 2,
        ]);
    }

    #[Test]
    public function superadmin_menghapus_aturan_auto_reply(): void
    {
        $aturan = AutoReply::create([
            'kategori' => 'umum',
            'nama' => 'Aturan Sementara',
            'cara_cocok' => 'semua',
            'isi' => 'Halo.',
        ]);

        $this->actingAs($this->superadmin())
            ->delete(route('admin.auto-reply.destroy', $aturan))
            ->assertRedirect(route('admin.auto-reply.index'));

        $this->assertDatabaseMissing('auto_replies', ['id' => $aturan->id]);
    }

    #[Test]
    public function superadmin_menonaktifkan_dan_mengaktifkan_aturan(): void
    {
        $aturan = AutoReply::create([
            'kategori' => 'umum',
            'nama' => 'Sapaan',
            'cara_cocok' => 'semua',
            'isi' => 'Halo.',
        ]);

        $this->actingAs($this->superadmin())
            ->post(route('admin.auto-reply.toggle', $aturan))
            ->assertRedirect(route('admin.auto-reply.index'));

        $this->assertDatabaseHas('auto_replies', ['id' => $aturan->id, 'aktif' => false]);

        $this->actingAs($this->superadmin())
            ->post(route('admin.auto-reply.toggle', $aturan));

        $this->assertDatabaseHas('auto_replies', ['id' => $aturan->id, 'aktif' => true]);
    }

    #[Test]
    public function cara_cocok_tidak_valid_ditolak(): void
    {
        $this->actingAs($this->superadmin())
            ->post(route('admin.auto-reply.store'), [
                'kategori' => 'umum',
                'nama' => 'Salah',
                'cara_cocok' => 'tidak-ada',
                'pola' => 'x',
                'isi' => 'Halo.',
            ])
            ->assertSessionHasErrors('cara_cocok');
    }

    // ── Logika pencocokan pola (bank data pertanyaan) ──

    #[Test]
    public function cocok_memeriksa_semua_mode_pencocokan(): void
    {
        $semua = AutoReply::create(['kategori' => 'umum', 'nama' => 'Semua', 'cara_cocok' => 'semua', 'pola' => null, 'isi' => 'Halo.']);
        $sama = AutoReply::create(['kategori' => 'umum', 'nama' => 'Sama', 'cara_cocok' => 'sama', 'pola' => 'jadwal dokter', 'isi' => 'x']);
        $mulai = AutoReply::create(['kategori' => 'umum', 'nama' => 'Mulai', 'cara_cocok' => 'mulai', 'pola' => 'info', 'isi' => 'x']);
        $mengandung = AutoReply::create(['kategori' => 'umum', 'nama' => 'Kandungan', 'cara_cocok' => 'mengandung', 'pola' => 'harga', 'isi' => 'x']);
        $akhiri = AutoReply::create(['kategori' => 'umum', 'nama' => 'Akhiri', 'cara_cocok' => 'akhiri', 'pola' => 'terima kasih', 'isi' => 'x']);

        $this->assertTrue($semua->cocok('pesan apa saja'));
        $this->assertTrue($sama->cocok('Jadwal Dokter'));      // case-insensitive
        $this->assertFalse($sama->cocok('jadwal dokter hari ini'));
        $this->assertTrue($mulai->cocok('Info poli gigi'));
        $this->assertFalse($mulai->cocok('Butuh info?'));
        $this->assertTrue($mengandung->cocok('Berapa harga kontrol?'));
        $this->assertFalse($mengandung->cocok('Halo'));
        $this->assertTrue($akhiri->cocok('Baik, terima kasih'));
        $this->assertFalse($akhiri->cocok('terima kasih banyak dok, sampai jumpa'));
    }

    // ── Integrasi webhook → balasan otomatis ──

    #[Test]
    public function webhook_membalas_otomatis_dengan_jawaban_bank_data(): void
    {
        // Pastikan jam operasional selalu aktif untuk pengujian terisolasi.
        Config::set('whatsapp.auto_reply.jam_buka', '00:00');
        Config::set('whatsapp.auto_reply.jam_tutup', '23:59');

        // Bersihkan bank data bawaan seeder agar aturan uji yang dipakai jelas.
        AutoReply::query()->delete();

        AutoReply::create([
            'kategori' => 'operasional',
            'nama' => 'Jam Operasional',
            'cara_cocok' => 'mengandung',
            'pola' => 'jam operasional',
            'isi' => 'jam operasional tersedia dari jam 8 pagi sampai jam 10 malam',
        ]);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '108633889005396',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'contacts' => [
                                    ['profile' => ['name' => 'Budi Santoso'], 'wa_id' => '6281234567890'],
                                ],
                                'messages' => [
                                    [
                                        'from' => '6281234567890',
                                        'id' => 'wamid.AUTO1',
                                        'timestamp' => '1700000000',
                                        'type' => 'text',
                                        'text' => ['body' => 'Kak, mau tanya jam operasional rumah sakit?'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'rahasia_uji');

        $this->postJson('/whatsapp/webhook', $payload, ['X-Hub-Signature-256' => $signature])
            ->assertOk()
            ->assertJson(['status' => 'ok', 'diproses' => 1]);

        $this->assertDatabaseHas('message_logs', [
            'jenis' => 'respon',
            'rule' => 'auto',
            'penerima_no_hp' => '6281234567890',
            'penerima_nama' => 'Budi Santoso',
            'konten' => 'jam operasional tersedia dari jam 8 pagi sampai jam 10 malam',
        ]);
    }

    #[Test]
    public function webhook_di_luar_jam_operasional_tidak_membalas(): void
    {
        // 1700000000 = 04:33 WIB — masih pagi, di luar jam operasional.
        AutoReply::create([
            'kategori' => 'operasional',
            'nama' => 'Jam Operasional',
            'cara_cocok' => 'semua',
            'isi' => 'Jam operasional tersedia dari jam 8 pagi sampai jam 10 malam.',
        ]);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '108633889005396',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'contacts' => [
                                    ['profile' => ['name' => 'Budi Santoso'], 'wa_id' => '6281234567890'],
                                ],
                                'messages' => [
                                    [
                                        'from' => '6281234567890',
                                        'id' => 'wamid.AUTO2',
                                        'timestamp' => '1700000000',
                                        'type' => 'text',
                                        'text' => ['body' => 'Assalamualaikum'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'rahasia_uji');

        $this->postJson('/whatsapp/webhook', $payload, ['X-Hub-Signature-256' => $signature])
            ->assertOk();

        $this->assertDatabaseCount('message_logs', 0);
    }

    #[Test]
    public function webhook_tidak_membalas_saat_tidak_ada_aturan_yang_cocok(): void
    {
        Config::set('whatsapp.auto_reply.jam_buka', '00:00');
        Config::set('whatsapp.auto_reply.jam_tutup', '23:59');

        // Hapus aturan catch-all bawaan seeder — hanya sisakan yang spesifik.
        AutoReply::query()->delete();

        AutoReply::create([
            'kategori' => 'operasional',
            'nama' => 'Jam Operasional',
            'cara_cocok' => 'mengandung',
            'pola' => 'jam operasional',
            'isi' => 'Jam operasional tersedia dari jam 8 pagi sampai jam 10 malam.',
        ]);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '108633889005396',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'contacts' => [
                                    ['profile' => ['name' => 'Budi Santoso'], 'wa_id' => '6281234567890'],
                                ],
                                'messages' => [
                                    [
                                        'from' => '6281234567890',
                                        'id' => 'wamid.AUTO3',
                                        'timestamp' => '1700000000',
                                        'type' => 'text',
                                        'text' => ['body' => 'Sekarang dokter siapa yang jaga?'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'rahasia_uji');

        $this->postJson('/whatsapp/webhook', $payload, ['X-Hub-Signature-256' => $signature])
            ->assertOk();

        $this->assertDatabaseCount('message_logs', 0);
    }

    #[Test]
    public function prioritas_aturan_diprioritaskan_sesuai_urutan_mendaftar(): void
    {
        Config::set('whatsapp.auto_reply.jam_buka', '00:00');
        Config::set('whatsapp.auto_reply.jam_tutup', '23:59');

        AutoReply::query()->delete();

        // Aturan "semua" (catch-all) dengan prioritas lebih tinggi (angka
        // lebih besar diproses belakangan), aturan spesifik berprioritas
        // lebih kecil (diproses dulu).
        AutoReply::create([
            'kategori' => 'umum',
            'nama' => 'Sapaan Umum',
            'cara_cocok' => 'semua',
            'isi' => 'Pesan Anda kami terima.',
            'prioritas' => 10,
        ]);
        AutoReply::create([
            'kategori' => 'jadwal_dokter',
            'nama' => 'Jadwal Dokter',
            'cara_cocok' => 'mengandung',
            'pola' => 'jadwal dokter',
            'isi' => 'Silakan hubungi admin untuk jadwal dokter.',
            'prioritas' => 0,
        ]);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '108633889005396',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'contacts' => [
                                    ['profile' => ['name' => 'Budi Santoso'], 'wa_id' => '6281234567890'],
                                ],
                                'messages' => [
                                    [
                                        'from' => '6281234567890',
                                        'id' => 'wamid.AUTO4',
                                        'timestamp' => '1700000000',
                                        'type' => 'text',
                                        'text' => ['body' => 'Tolong info jadwal dokter ya'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'rahasia_uji');

        $this->postJson('/whatsapp/webhook', $payload, ['X-Hub-Signature-256' => $signature])
            ->assertOk();

        $this->assertDatabaseHas('message_logs', [
            'jenis' => 'respon',
            'rule' => 'auto',
            'konten' => 'Silakan hubungi admin untuk jadwal dokter.',
        ]);
        $this->assertSame(1, MessageLog::where('rule', 'auto')->count());
    }
}
