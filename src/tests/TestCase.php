<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * .env pengembangan bisa memuat WA_TEST_TARGET (nomor uji coba kirim
     * nyata); direset agar tidak bocor ke pengujian yang berasumsi pesan
     * dikirim ke nomor asli penerima.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config(['whatsapp.test_target' => '']);
    }
}
