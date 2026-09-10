<?php

namespace Tests\Unit;

use App\Broadcasting\MessageRenderer;
use App\Models\PenyakitKronis;
use App\Models\Pnpp;
use App\Models\Satker;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MessageRendererTest extends TestCase
{
    private function pasien(): Pnpp
    {
        $pnpp = new Pnpp(['nama' => 'Budi Santoso', 'nip' => '198501012010011001']);
        $pnpp->setRelation('satker', new Satker(['kode' => 'DINKES', 'nama' => 'Dinas Kesehatan']));
        $pnpp->setRelation('penyakit', collect([
            new PenyakitKronis(['kode' => 'HTN', 'nama' => 'Hipertensi']),
            new PenyakitKronis(['kode' => 'DM', 'nama' => 'Diabetes Melitus']),
        ]));

        return $pnpp;
    }

    #[Test]
    public function token_pasien_dan_meta_terganti(): void
    {
        $hasil = app(MessageRenderer::class)->render(
            'Halo {nama} ({nip}) dari {satker}, obat Anda: {obat}. Kontrol di {poli} bersama {dokter} pukul {jam}.',
            $this->pasien(),
            ['poli' => ['Poli Umum', 'Poli Gigi'], 'dokter' => 'dr. Rina', 'jam' => '09:00'],
        );

        $this->assertSame(
            'Halo Budi Santoso (198501012010011001) dari Dinas Kesehatan, obat Anda: Hipertensi, Diabetes Melitus. Kontrol di Poli Umum, Poli Gigi bersama dr. Rina pukul 09:00.',
            $hasil,
        );
    }

    #[Test]
    public function tanggal_meta_diformat_indonesia(): void
    {
        $hasil = app(MessageRenderer::class)->render(
            'Jadwal kontrol Anda pada {tanggal}.',
            $this->pasien(),
            ['tanggal' => '2026-09-10'],
        );

        $diharapkan = Carbon::parse('2026-09-10')->locale('id')->translatedFormat('l, j F Y');

        $this->assertSame('Jadwal kontrol Anda pada '.$diharapkan.'.', $hasil);
    }

    #[Test]
    public function tanggal_bukan_tanggal_dipakai_apa_adanya(): void
    {
        $hasil = app(MessageRenderer::class)->render(
            'Kontrol {tanggal}.',
            null,
            ['tanggal' => 'segera'],
        );

        $this->assertSame('Kontrol segera.', $hasil);
    }

    #[Test]
    public function token_tak_dikenal_dan_nilai_kosong_dibiarkan(): void
    {
        $pnpp = new Pnpp(['nama' => 'Siti Aminah']);
        $pnpp->setRelation('satker', null);
        $pnpp->setRelation('penyakit', collect());

        $hasil = app(MessageRenderer::class)->render(
            'Yth. {nama}, antrian Anda {no_antrian}, satker {satker}.',
            $pnpp,
            [],
        );

        $this->assertSame('Yth. Siti Aminah, antrian Anda {no_antrian}, satker {satker}.', $hasil);
    }

    #[Test]
    public function tanpa_pasien_hanya_token_meta_yang_terganti(): void
    {
        $hasil = app(MessageRenderer::class)->render(
            'Pemberitahuan untuk {nama}: kegiatan di {poli} pada {tanggal}.',
            null,
            ['poli' => 'Poli Umum', 'tanggal' => '2026-09-10'],
        );

        $this->assertStringContainsString('{nama}', $hasil);
        $this->assertStringContainsString('Poli Umum', $hasil);
        $this->assertStringNotContainsString('{poli}', $hasil);
        $this->assertStringNotContainsString('{tanggal}', $hasil);
    }
}
