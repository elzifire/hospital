<?php

namespace Database\Factories;

use App\Models\Pnpp;
use App\Models\Satker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pnpp>
 */
class PnppFactory extends Factory
{
    /**
     * Nama depan Indonesia (datar, tanpa aksen) — menjaga konsistensi
     * dengan encoding DB (WIN1252) agar tidak muncul karakter aneh.
     */
    protected array $namaDepan = [
        'Budi', 'Agus', 'Slamet', 'Wahyu', 'Dedi', 'Rudi', 'Joko', 'Eko', 'Bambang', 'Hendra',
        'Rizki', 'Andi', 'Yudi', 'Fajar', 'Irfan', 'Taufik', 'Hasan', 'Dwi', 'Bayu', 'Arif',
        'Siti', 'Rina', 'Dewi', 'Sari', 'Fitri', 'Nur', 'Ani', 'Sri', 'Wati', 'Yanti',
        'Maya', 'Lia', 'Dian', 'Ratna', 'Indah', 'Rini', 'Putri', 'Ayu', 'Lina', 'Tuti',
    ];

    /**
     * Nama belakang Indonesia.
     */
    protected array $namaBelakang = [
        'Santoso', 'Wijaya', 'Pratama', 'Saputra', 'Hidayat', 'Kusuma', 'Ramadhan', 'Maulana',
        'Setiawan', 'Nugroho', 'Susanto', 'Purnama', 'Gunawan', 'Firmansyah', 'Hakim', 'Syafii',
        'Utami', 'Handayani', 'Lestari', 'Anggraini', 'Wulandari', 'Puspita', 'Rahayu', 'Mawarni',
        'Permata', 'Melati', 'Kartika', 'Safitri', 'Pertiwi', 'Ningrum',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jk = $this->faker->randomElement(['L', 'P']);
        $nama = $this->faker->randomElement($this->namaDepan).' '.$this->faker->randomElement($this->namaBelakang);

        return [
            'nama' => $nama,
            'nip' => $this->faker->unique()->numerify('####') . str_pad((string) $this->faker->unique()->numberBetween(1, 99999999), 8, '0', STR_PAD_LEFT),
            'status_kepegawaian' => $this->faker->randomElement(['Anggota Polri', 'PNS', 'TNI', 'ASN Polri']),
            'pangkat' => $this->faker->randomElement(['Bripda', 'Briptu', 'Bripka', 'Aiptu', 'Ipda', 'Iptu', 'AKP']),
            'jabatan' => $this->faker->randomElement(['Bintara', 'Panit', 'Kasat', 'Staf', 'Analis', 'Penyidik']),
            'satuan_kerja' => $this->faker->randomElement(['Dinkes', 'Polresta Bogor Kota', 'Polres Bogor', 'Polda Jabar', 'Kemenkes', 'BPJS']),
            'bagian' => $this->faker->randomElement(['Bagian Umum', 'Bagian Kesehatan', 'Bagian Operasional', 'Bagian SDM']),
            'email' => $this->faker->unique()->safeEmail(),
            'alamat' => 'Jl. '.$this->faker->streetName.' No. '.$this->faker->numberBetween(1, 300),
            'no_bpjs' => $this->faker->unique()->numerify('000############'),
            'satker_id' => Satker::query()->inRandomOrder()->value('id'),
            'no_hp' => '08'.$this->faker->unique()->numerify('##########'),
            'tanggal_lahir' => $this->faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'jenis_kelamin' => $jk,
            'status_aktif' => $this->faker->randomElement(['aktif', 'aktif', 'aktif', 'nonaktif']),
        ];
    }
}