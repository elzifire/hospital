{{--
    Tab "Aturan Pesan" — pasangkan template ke tiap rule generate
    (saat ini hanya outreach H-7/H-1; aturan follow up dinonaktifkan
    sementara). Butuh: $aturan (BroadcastRule + template), $templateAktif.
--}}

<div x-show="activeTab === 'aturan'" class="space-y-6">

    {{-- Box Penjelasan --}}
    <div class="rounded-2xl bg-gradient-to-br from-white to-slate-50/60 p-5 shadow-xs ring-1 ring-slate-200">
        <div class="flex items-start gap-3.5">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-bold text-slate-900">Aturan Generate Pesan</p>
                <p class="mt-1 text-xs leading-relaxed text-slate-500">
                    Pesan outreach dibuat otomatis dari penjadwalan <strong>Digital Reminder</strong> sesuai aturan di bawah.
                    Pasangkan template aktif untuk mengaktifkan tiap aturan, lalu tekan tombol
                    <strong>Generate Pesan</strong> di modul Outreach untuk menjalankannya.
                    Aturan tanpa template atau yang dinonaktifkan akan dilewati.
                    Aturan generate follow up (H-1, hari-H, tidak datang) dinonaktifkan sementara.
                </p>
            </div>
        </div>
    </div>

    {{-- Kartu aturan per rule --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        @php
            $ruleLabel = ['h-7' => 'H-7', 'h-1' => 'H-1', 'h' => 'Hari-H', 'tidak_datang' => 'Tidak Datang'];
            $ruleDesc = [
                'outreach' => [
                    'h-7' => 'Undangan awal — jadwal tepat 7 hari ke depan.',
                    'h-1' => 'Pengingat akhir — jadwal besok.',
                ],
                'follow_up' => [
                    'h-1' => 'Penegasan jadwal — H-1 sebelum kunjungan.',
                    'h'   => 'Pesan hari-H — jadwal hari ini.',
                    'tidak_datang' => 'Tindak lanjut pasien yang tidak datang pada jadwalnya.',
                ],
            ];
        @endphp

        @foreach ($aturan as $a)
            @php($isOutreach = $a->jenis === 'outreach')
            <form action="{{ route('admin.setting.aturan.update', $a) }}" method="POST"
                  class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                @csrf
                @method('PUT')

                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl font-bold {{ $isOutreach ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-amber-100' }} ring-1">
                            {{ $ruleLabel[$a->rule] ?? strtoupper($a->rule) }}
                        </span>
                        <div>
                            <p class="text-sm font-bold text-slate-900">
                                {{ $isOutreach ? 'Outreach' : 'Follow Up' }}
                                <span class="font-medium text-slate-400">· {{ $ruleLabel[$a->rule] ?? $a->rule }}</span>
                            </p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $ruleDesc[$a->jenis][$a->rule] ?? '' }}</p>
                        </div>
                    </div>
                    @if ($a->is_active)
                        <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200/70">Aktif</span>
                    @else
                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-500 ring-1 ring-inset ring-slate-200/70">Nonaktif</span>
                    @endif
                </div>

                <div class="space-y-4 px-5 py-4">
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Template Pesan</label>
                        <select name="message_template_id"
                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                            <option value="">— Tanpa template (rule dilewati) —</option>
                            @foreach ($templateAktif as $t)
                                <option value="{{ $t->id }}" {{ (string) old('message_template_id', $a->message_template_id) === (string) $t->id ? 'selected' : '' }}>
                                    {{ $t->judul }}
                                </option>
                            @endforeach
                        </select>
                        @if ($templateAktif->isEmpty())
                            <p class="mt-1 text-xs text-rose-500">Belum ada template aktif — buat dulu di tab Daftar Template Pesan.</p>
                        @elseif (! $a->message_template_id)
                            <p class="mt-1 text-xs text-amber-600">Aturan ini belum menghasilkan pesan (template belum dipasang).</p>
                        @endif
                    </div>

                    <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $a->is_active) ? 'checked' : '' }}
                               class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                        Aturan aktif — ikut saat tombol Generate ditekan
                    </label>
                </div>

                <div class="flex items-center justify-end border-t border-slate-100 bg-slate-50/50 px-5 py-3">
                    <button type="submit"
                            class="rounded-xl bg-sky-600 px-5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-sky-700">
                        Simpan Aturan
                    </button>
                </div>
            </form>
        @endforeach
    </div>
</div>
