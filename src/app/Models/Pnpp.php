<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pnpp extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'nip',
        'status_kepegawaian',
        'pangkat',
        'jabatan',
        'satuan_kerja',
        'bagian',
        'email',
        'alamat',
        'no_bpjs',
        'satker_id',
        'no_hp',
        'tanggal_lahir',
        'jenis_kelamin',
        'status_aktif',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
        ];
    }

    public function satker()
    {
        return $this->belongsTo(Satker::class);
    }

    public function penyakit()
    {
        return $this->belongsToMany(
            PenyakitKronis::class,
            'pnpp_penyakit',
            'pnpp_id',
            'penyakit_kronis_id'
        )->withPivot('keterangan');
    }

    public function kunjungans()
    {
        return $this->hasMany(Kunjungan::class);
    }

    public function latestKunjungan()
    {
        return $this->hasOne(Kunjungan::class)->latestOfMany('tanggal_kunjungan');
    }

    /**
     * Usia dihitung dari tanggal_lahir (tidak disimpan — menghindari data basi).
     */
    public function getUsiaAttribute(): ?int
    {
        return $this->tanggal_lahir?->age;
    }

    /**
     * Tanggal terakhir berobat diambil dari kunjungan terbaru (tidak disimpan).
     */
    public function getTanggalTerakhirBerobatAttribute()
    {
        return $this->latestKunjungan?->tanggal_kunjungan;
    }
}
