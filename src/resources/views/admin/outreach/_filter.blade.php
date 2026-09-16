@php($actionFilter = $actionFilter ?? request()->url())
<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4">
        <h2 class="text-sm font-bold text-slate-900">Cari &amp; Pilih Pasien</h2>
        <p class="mt-0.5 text-xs text-slate-500">Hanya pasien dengan jadwal Digital Reminder yang ditampilkan, diurutkan sesuai jadwal terdekat.</p>
    </div>
    <form method="GET" action="{{ $actionFilter }}"
          class="flex flex-wrap items-end gap-3 bg-slate-50/50 px-5 py-4">
        <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Cari</label>
            <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Nama / NIP / no. HP…"
                   class="w-52 rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
        </div>
        <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Satker</label>
            <select name="satker" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                <option value="">Semua</option>
                @foreach ($satkers as $s)
                    <option value="{{ $s->id }}" {{ $filters['satker'] == $s->id ? 'selected' : '' }}>{{ $s->nama }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tanggal Jadwal</label>
            <input type="date" name="tanggal" value="{{ $filters['tanggal'] ?? '' }}"
                   class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
        </div>
        <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tampilkan</label>
            <select name="tampilkan" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                <option value="berjadwal" {{ ($filters['tampilkan'] ?? 'berjadwal') === 'berjadwal' ? 'selected' : '' }}>Hanya yang punya jadwal</option>
                <option value="semua" {{ ($filters['tampilkan'] ?? '') === 'semua' ? 'selected' : '' }}>Semua PNPP</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit"
                    class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700">
                Filter
            </button>
            <a href="{{ $actionFilter }}"
               class="rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-300">
                Reset
            </a>
        </div>
    </form>
</div>