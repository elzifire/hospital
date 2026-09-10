{{-- Baris poli repeatable untuk form kunjungan multi-poli.
    Satu pasien bisa ke beberapa poli dalam satu tanggal — tiap baris
    menjadi satu baris kunjungan. Self-contained (bawa x-data sendiri). --}}
@php
    $rowsAwal = collect(old('polis', []))
        ->map(fn ($r) => ['poli_id' => $r['poli_id'] ?? '', 'keluhan' => $r['keluhan'] ?? '', 'diagnosa' => $r['diagnosa'] ?? ''])
        ->values()
        ->all();
    $rowsAwal = $rowsAwal ?: [['poli_id' => '', 'keluhan' => '', 'diagnosa' => '']];
@endphp
<div x-data="{
        rows: @js($rowsAwal),
        tambahRow() { this.rows.push({ poli_id: '', keluhan: '', diagnosa: '' }) },
        hapusRow(i) { if (this.rows.length > 1) this.rows.splice(i, 1) }
     }"
     class="space-y-3">
    <template x-for="(row, i) in rows" :key="i">
        <div class="grid grid-cols-1 items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/50 p-4 lg:grid-cols-[180px_1fr_1fr_44px]">
            <div>
                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-400">Poli <span class="text-rose-500">*</span></label>
                <select x-model="row.poli_id" :name="'polis[' + i + '][poli_id]'" required
                        class="w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500">
                    <option value="" disabled>— Pilih poli —</option>
                    @foreach ($polis as $po)
                        <option value="{{ $po->id }}">{{ $po->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-400">Keluhan</label>
                <input type="text" x-model="row.keluhan" :name="'polis[' + i + '][keluhan]'" maxlength="1000" placeholder="cth. Pusing, demam"
                       class="w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500">
            </div>
            <div>
                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-400">Diagnosa</label>
                <input type="text" x-model="row.diagnosa" :name="'polis[' + i + '][diagnosa]'" maxlength="1000" placeholder="cth. Hipertensi"
                       class="w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500">
            </div>
            <div class="flex items-end justify-end">
                <button type="button" @click="hapusRow(i)" x-show="rows.length > 1" x-cloak title="Hapus baris poli ini"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                    </svg>
                </button>
            </div>
        </div>
    </template>

    <button type="button" @click="tambahRow()"
            class="inline-flex items-center gap-1.5 rounded-lg border border-dashed border-slate-300 px-4 py-2 text-xs font-bold text-slate-500 transition hover:border-sky-400 hover:text-sky-600">
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Tambah Poli
    </button>

    @error('polis')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
</div>
