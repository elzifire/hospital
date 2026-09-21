<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageLog extends Model
{
    use HasFactory;

    /**
     * Jenis pesan sesuai modul penghasilnya (dari broadcast_rules).
     */
    public const JENIS = ['outreach', 'follow_up'];

    /**
     * Status pesan: "menunggu" (dalam proses antrean) → "mengirim"
     * (sedang dikirim worker) → "terkirim" / "gagal"; "dibatalkan"
     * final karena dibatalkan petugas sebelum terkirim.
     */
    public const STATUS = ['menunggu', 'mengirim', 'terkirim', 'gagal', 'dibatalkan'];

    /**
     * Label status untuk UI — alur antrean dibuat eksplisit supaya
     * pengguna tidak bingung membedakan antrean dan pengiriman
     * yang sedang berjalan.
     */
    public const LABEL_STATUS = [
        'menunggu' => 'Dalam Proses',
        'mengirim' => 'Sedang Dikirim',
        'terkirim' => 'Terkirim',
        'gagal' => 'Gagal',
        'dibatalkan' => 'Dibatalkan',
    ];

    protected $fillable = [
        'jenis',
        'rule',
        'reminder_id',
        'message_template_id',
        'pnpp_id',
        'created_by',
        'kirim_group',
        'penerima_nama',
        'penerima_no_hp',
        'konten',
        'status',
        'sent_at',
        'error',
        'kirim_pada',
        'provider',
        'provider_message_id',
        'meta_template_name',
        'meta_language',
        'template_params',
        'meta_payload',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'kirim_pada' => 'datetime',
            'template_params' => 'array',
            'meta_payload' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'message_template_id');
    }

    public function pnpp(): BelongsTo
    {
        return $this->belongsTo(Pnpp::class);
    }

    /**
     * Penjadwalan yang memicu pesan ini (sumber konteks {poli} {dokter}
     * {tanggal} {jam} saat render).
     */
    public function reminder(): BelongsTo
    {
        return $this->belongsTo(Reminder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeJenis(Builder $query, string $jenis): Builder
    {
        return $query->where('jenis', $jenis);
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return blank($status) ? $query : $query->where('status', $status);
    }

    public function scopeRule(Builder $query, ?string $rule): Builder
    {
        return blank($rule) ? $query : $query->where('rule', $rule);
    }
}
