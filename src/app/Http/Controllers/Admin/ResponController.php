<?php

namespace App\Http\Controllers\Admin;

use App\Broadcasting\PhoneFormat;
use App\Http\Controllers\Controller;
use App\Models\MessageLog;
use App\Models\MessageReply;
use App\Models\Pnpp;
use Illuminate\Http\Request;

class ResponController extends Controller
{
    /**
     * Daftar balasan WhatsApp pasien yang masuk otomatis via webhook
     * (read-only) — dengan ringkasan, pencarian, dan pintasan follow up.
     */
    public function index(Request $request)
    {
        $q = (string) $request->query('q', '');

        $balasan = MessageReply::query()
            ->with('pnpp:id,nama')
            ->when($q, fn ($query) => $query->where(
                fn ($sub) => $sub->where('nama', 'like', "%{$q}%")
                    ->orWhere('no_hp', 'like', "%{$q}%")
                    ->orWhere('isi_pesan', 'like', "%{$q}%")
            ))
            ->orderByDesc('waktu_masuk')
            ->paginate(10)
            ->withQueryString();

        return view('admin.respon.index', [
            'balasan' => $balasan,
            'total' => MessageReply::count(),
            'hariIni' => MessageReply::whereBetween('waktu_masuk', [now()->startOfDay(), now()])->count(),
            'pasienUnik' => MessageReply::whereNotNull('pnpp_id')->distinct()->count('pnpp_id'),
            'takTerdaftar' => MessageReply::whereNull('pnpp_id')->count(),
            'filters' => ['q' => $q],
        ]);
    }

    /**
     * Percakapan satu nomor: pesan keluar (broadcast) + balasan masuk,
     * digabung dalam satu garis waktu.
     */
    public function show(string $nomor)
    {
        $noHp = PhoneFormat::toWa($nomor) ?? $nomor;

        $keluar = MessageLog::query()
            ->where('penerima_no_hp', $noHp)
            ->orderBy('created_at')
            ->get()
            ->map(fn (MessageLog $log) => [
                'arah' => 'keluar',
                'isi' => $log->konten,
                'waktu' => $log->sent_at ?? $log->created_at,
                'status' => $log->status,
                'jenis' => $log->jenis,
            ]);

        $masuk = MessageReply::query()
            ->where('no_hp', $noHp)
            ->orderBy('waktu_masuk')
            ->get()
            ->map(fn (MessageReply $b) => [
                'arah' => 'masuk',
                'isi' => $b->isi_pesan,
                'waktu' => $b->waktu_masuk,
                'nama' => $b->nama,
            ]);

        $timeline = $keluar->merge($masuk)->sortBy(fn ($item) => $item['waktu'])->values();

        $pnpp = Pnpp::query()
            ->whereNotNull('no_hp')
            ->get()
            ->first(fn (Pnpp $p) => PhoneFormat::toWa($p->no_hp) === $noHp);

        return view('admin.respon.show', [
            'noHp' => $noHp,
            'pnpp' => $pnpp,
            'timeline' => $timeline,
        ]);
    }
}
