@extends('layouts.app')

@section('title', 'Edit PNPP')
@section('page-title', 'Edit PNPP')

@section('content')
<div x-data="pnppForm()" class="mx-auto max-w-6xl space-y-6">

    {{-- ===== Header + Breadcrumb ===== --}}
    <div>
        <nav class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('admin.pnpp.index') }}" class="rounded transition hover:text-sky-600">Data PNPP</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-slate-600">{{ $pnpp->nama }}</span>
        </nav>
        <h2 class="text-xl font-bold tracking-tight text-slate-900">Edit Data PNPP</h2>
        <p class="mt-0.5 text-sm text-slate-500">Perbarui identitas dan riwayat kesehatan PNPP.</p>
    </div>

    <form action="{{ route('admin.pnpp.update', $pnpp->id) }}" method="POST" @submit="saving = true" x-cloak>
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- ===== Form Utama ===== --}}
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="border-b border-slate-100 p-6">
                        <h3 class="text-base font-bold text-slate-900">Identitas</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Data dasar identitas PNPP.</p>
                    </div>

                    <div class="space-y-5 p-6">
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="nama" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Nama Lengkap <span class="text-rose-500">*</span></label>
                                <input type="text" name="nama" id="nama" value="{{ old('nama', $pnpp->nama) }}" x-model="nama" required placeholder="cth. Budi Santoso"
                                       class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('nama') ring-rose-300 focus:ring-rose-500 @enderror">
                                @error('nama')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="nip" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">NIP/NRP</label>
                                <input type="text" name="nip" id="nip" value="{{ old('nip', $pnpp->nip) }}" x-model="nip" placeholder="18 digit NIP"
                                       class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('nip') ring-rose-300 focus:ring-rose-500 @enderror">
                                @error('nip')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="status_kepegawaian" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Status Kepegawaian</label>
                                <select name="status_kepegawaian" id="status_kepegawaian" x-model="statusKepegawaian"
                                        class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition cursor-pointer @error('status_kepegawaian') ring-rose-300 focus:ring-rose-500 @enderror">
                                    <option value="">— Pilih —</option>
                                    <option value="Anggota Polri" @selected(old('status_kepegawaian', $pnpp->status_kepegawaian) == 'Anggota Polri')>Anggota Polri</option>
                                    <option value="PNS" @selected(old('status_kepegawaian', $pnpp->status_kepegawaian) == 'PNS')>PNS</option>
                                    <option value="TNI" @selected(old('status_kepegawaian', $pnpp->status_kepegawaian) == 'TNI')>TNI</option>
                                    <option value="ASN Polri" @selected(old('status_kepegawaian', $pnpp->status_kepegawaian) == 'ASN Polri')>ASN Polri</option>
                                </select>
                                @error('status_kepegawaian')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="pangkat" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Pangkat</label>
                                <input type="text" name="pangkat" id="pangkat" value="{{ old('pangkat', $pnpp->pangkat) }}" x-model="pangkat" placeholder="cth. Bripka / III/c"
                                       class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('pangkat') ring-rose-300 focus:ring-rose-500 @enderror">
                                @error('pangkat')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="jabatan" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Jabatan</label>
                                <input type="text" name="jabatan" id="jabatan" value="{{ old('jabatan', $pnpp->jabatan) }}" x-model="jabatan" placeholder="cth. Bintara"
                                       class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('jabatan') ring-rose-300 focus:ring-rose-500 @enderror">
                                @error('jabatan')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="no_bpjs" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">No. BPJS</label>
                                <input type="text" name="no_bpjs" id="no_bpjs" value="{{ old('no_bpjs', $pnpp->no_bpjs) }}" x-model="noBpjs" placeholder="13 digit BPJS"
                                       class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('no_bpjs') ring-rose-300 focus:ring-rose-500 @enderror">
                                @error('no_bpjs')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="satker_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Satker</label>
                                <select name="satker_id" id="satker_id" x-model="satkerId" @change="if (!satuanKerja) satuanKerja = satkerName"
                                        class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition cursor-pointer @error('satker_id') ring-rose-300 focus:ring-rose-500 @enderror">
                                    <option value="">— Pilih Satker —</option>
                                    @foreach ($satkers as $satker)
                                        <option value="{{ $satker->id }}" @selected(old('satker_id', $pnpp->satker_id) == $satker->id)>{{ $satker->nama }}</option>
                                    @endforeach
                                </select>
                                @error('satker_id')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="satuan_kerja" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Satuan Kerja</label>
                                <input type="text" name="satuan_kerja" id="satuan_kerja" value="{{ old('satuan_kerja', $pnpp->satuan_kerja) }}" x-model="satuanKerja" placeholder="cth. Dinas Kesehatan"
                                       class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('satuan_kerja') ring-rose-300 focus:ring-rose-500 @enderror">
                                @error('satuan_kerja')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="bagian" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Bagian</label>
                                <input type="text" name="bagian" id="bagian" value="{{ old('bagian', $pnpp->bagian) }}" x-model="bagian" placeholder="cth. Bagian Umum"
                                       class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('bagian') ring-rose-300 focus:ring-rose-500 @enderror">
                                @error('bagian')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="no_hp" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">No. HP</label>
                                <input type="text" name="no_hp" id="no_hp" value="{{ old('no_hp', $pnpp->no_hp) }}" x-model="noHp" placeholder="cth. 081234567890"
                                       class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('no_hp') ring-rose-300 focus:ring-rose-500 @enderror">
                                @error('no_hp')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="email" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Email</label>
                                <input type="email" name="email" id="email" value="{{ old('email', $pnpp->email) }}" x-model="email" placeholder="cth. budi@contoh.id"
                                       class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('email') ring-rose-300 focus:ring-rose-500 @enderror">
                                @error('email')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="alamat" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Alamat</label>
                                <input type="text" name="alamat" id="alamat" value="{{ old('alamat', $pnpp->alamat) }}" x-model="alamat" placeholder="cth. Jl. Merdeka No. 1"
                                       class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('alamat') ring-rose-300 focus:ring-rose-500 @enderror">
                                @error('alamat')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="tanggal_lahir" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Tanggal Lahir</label>
                                <input type="date" name="tanggal_lahir" id="tanggal_lahir" value="{{ old('tanggal_lahir', $pnpp->tanggal_lahir?->format('Y-m-d')) }}" x-model="tanggalLahir"
                                       class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition @error('tanggal_lahir') ring-rose-300 focus:ring-rose-500 @enderror">
                                @error('tanggal_lahir')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                                <p class="mt-1.5 text-xs text-slate-400" x-show="usia !== null">Usia otomatis: <strong class="text-slate-600" x-text="usia + ' tahun'"></strong></p>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Jenis Kelamin</label>
                                <div class="grid grid-cols-2 gap-2.5">
                                    <label class="relative flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 p-2.5 transition-all duration-200"
                                           :class="jk === 'L' ? 'border-sky-600 bg-sky-50/60 ring-1 ring-sky-600/20' : 'border-slate-200 hover:border-sky-300'">
                                        <input type="radio" name="jenis_kelamin" value="L" x-model="jk" class="sr-only">
                                        <svg class="h-4 w-4 text-sky-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                                        <span class="text-sm font-bold text-slate-700">Laki-laki</span>
                                    </label>
                                    <label class="relative flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 p-2.5 transition-all duration-200"
                                           :class="jk === 'P' ? 'border-rose-600 bg-rose-50/60 ring-1 ring-rose-600/20' : 'border-slate-200 hover:border-rose-300'">
                                        <input type="radio" name="jenis_kelamin" value="P" x-model="jk" class="sr-only">
                                        <svg class="h-4 w-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                                        <span class="text-sm font-bold text-slate-700">Perempuan</span>
                                    </label>
                                </div>
                                @error('jenis_kelamin')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="status_aktif" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Status Aktif</label>
                                <select name="status_aktif" id="status_aktif" x-model="statusAktif"
                                        class="block w-full rounded-xl border-0 py-2.5 px-3.5 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-sky-500 transition cursor-pointer @error('status_aktif') ring-rose-300 focus:ring-rose-500 @enderror">
                                    <option value="aktif" @selected(old('status_aktif', $pnpp->status_aktif) == 'aktif')>Aktif</option>
                                    <option value="nonaktif" @selected(old('status_aktif', $pnpp->status_aktif) == 'nonaktif')>Nonaktif</option>
                                </select>
                                @error('status_aktif')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Penyakit Kronis --}}
                <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="border-b border-slate-100 p-6">
                        <h3 class="text-base font-bold text-slate-900">Penyakit Kronis</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Centang bila ada riwayat penyakit kronis (boleh lebih dari satu).</p>
                    </div>
                    <div class="p-6">
                        @if ($penyakits->isEmpty())
                            <p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500 ring-1 ring-slate-100">
                                Belum ada data penyakit kronis. <a href="{{ route('admin.penyakit.create') }}" class="font-semibold text-sky-600 hover:underline">Tambahkan dulu</a>.
                            </p>
                        @else
                            <div class="flex flex-wrap gap-2.5">
                                @foreach ($penyakits as $penyakit)
                                    <label class="cursor-pointer rounded-xl p-3 ring-1 transition-all duration-200"
                                           :class="penyakit.includes('{{ $penyakit->id }}') ? 'bg-amber-50 ring-2 ring-amber-500' : 'bg-white ring-slate-200 hover:ring-amber-300'">
                                        <input type="checkbox" name="penyakit[]" value="{{ $penyakit->id }}" x-model="penyakit" class="sr-only">
                                        <span class="flex items-center gap-2">
                                            <span class="flex h-5 w-5 items-center justify-center rounded-full transition-colors"
                                                  :class="penyakit.includes('{{ $penyakit->id }}') ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-400'">
                                                <svg x-show="penyakit.includes('{{ $penyakit->id }}')" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                            </span>
                                            <span class="text-sm font-semibold text-slate-700">{{ $penyakit->nama }}</span>
                                            @if ($penyakit->kode)
                                                <code class="rounded bg-slate-100 px-1 font-mono text-[10px] text-slate-400">{{ $penyakit->kode }}</code>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('penyakit.*')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        @endif
                    </div>
                </div>

                {{-- Action bar --}}
                <div class="flex items-center justify-between gap-3">
                    <a href="{{ route('admin.pnpp.kunjungan', $pnpp) }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        Riwayat Kunjungan
                    </a>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('admin.pnpp.index') }}"
                           class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50">Batal</a>
                        <button type="submit" :disabled="saving"
                                class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                            <svg x-show="!saving" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="saving ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ===== Preview Sidebar (sticky) ===== --}}
            <div class="lg:col-span-1">
                <div class="sticky top-20 space-y-4">
                    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                        <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-3">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Pratinjau Langsung</p>
                        </div>
                        <div class="px-5 py-5 text-center">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-linear-to-br from-sky-500 to-indigo-500 text-2xl font-extrabold uppercase text-white shadow-lg ring-4 ring-slate-50"
                                 x-text="initials || '?'">?</div>
                            <h3 class="mt-3 truncate text-base font-bold text-slate-900" x-text="nama || 'Nama PNPP'"></h3>
                            <p class="truncate font-mono text-xs text-slate-400" x-text="nip || 'NIP —'"></p>

                            <div class="mt-4 flex flex-wrap items-center justify-center gap-1.5">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold capitalize ring-1 ring-inset"
                                      :class="jk === 'L' ? 'bg-sky-50 text-sky-700 ring-sky-600/20' : (jk === 'P' ? 'bg-rose-50 text-rose-700 ring-rose-600/20' : 'bg-slate-100 text-slate-500 ring-slate-300')">
                                    <span x-text="jk === 'L' ? 'Laki-laki' : (jk === 'P' ? 'Perempuan' : 'JK belum dipilih')"></span>
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600" x-show="usia !== null">
                                    <span x-text="usia + ' th'"></span>
                                </span>
                            </div>

                            <dl class="mt-5 space-y-2.5 border-t border-slate-100 pt-4 text-left text-xs">
                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-400">No. BPJS</dt>
                                    <dd class="truncate font-mono font-semibold text-slate-700" x-text="noBpjs || '—'"></dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-400">Status Kepegawaian</dt>
                                    <dd class="truncate font-semibold text-slate-700" x-text="statusKepegawaian || '—'"></dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-400">Satker</dt>
                                    <dd class="truncate font-semibold text-slate-700" x-text="satkerName"></dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-400">Satuan Kerja</dt>
                                    <dd class="truncate font-semibold text-slate-700" x-text="satuanKerja || '—'"></dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-400">No. HP</dt>
                                    <dd class="truncate font-semibold text-slate-700" x-text="noHp || '—'"></dd>
                                </div>
                            </dl>

                            <div class="mt-4 border-t border-slate-100 pt-4 text-left">
                                <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Penyakit Kronis</p>
                                <template x-if="penyakit.length === 0">
                                    <p class="text-xs italic text-slate-400">Tidak ada / sehat</p>
                                </template>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="id in penyakit" :key="id">
                                        <span class="rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 ring-1 ring-amber-200/70" x-text="penyakitName(id)"></span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl bg-sky-50 p-4 text-xs leading-relaxed text-sky-800 ring-1 ring-sky-100">
                        <p><strong>Riwayat kunjungan</strong> dikelola di halaman terpisah. Klik tombol "Riwayat Kunjungan" di bawah untuk menambah/menghapus catatan berobat.</p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('pnppForm', () => ({
            nama: @js(old('nama', $pnpp->nama)),
            nip: @js(old('nip', $pnpp->nip)),
            statusKepegawaian: @js(old('status_kepegawaian', $pnpp->status_kepegawaian)),
            pangkat: @js(old('pangkat', $pnpp->pangkat)),
            jabatan: @js(old('jabatan', $pnpp->jabatan)),
            noBpjs: @js(old('no_bpjs', $pnpp->no_bpjs)),
            satkerId: @js(old('satker_id', $pnpp->satker_id)),
            satuanKerja: @js(old('satuan_kerja', $pnpp->satuan_kerja)),
            bagian: @js(old('bagian', $pnpp->bagian)),
            email: @js(old('email', $pnpp->email)),
            alamat: @js(old('alamat', $pnpp->alamat)),
            noHp: @js(old('no_hp', $pnpp->no_hp)),
            tanggalLahir: @js(old('tanggal_lahir', $pnpp->tanggal_lahir?->format('Y-m-d'))),
            jk: @js(old('jenis_kelamin', $pnpp->jenis_kelamin)),
            statusAktif: @js(old('status_aktif', $pnpp->status_aktif)),
            penyakit: @js(old('penyakit', $pnpp->penyakit->pluck('id')->map(fn ($id) => (string) $id)->all())),
            saving: false,
            satkers: @js($satkers->map(fn ($s) => ['id' => $s->id, 'nama' => $s->nama])->values()),
            penyakits: @js($penyakits->map(fn ($p) => ['id' => $p->id, 'nama' => $p->nama])->values()),

            get initials() {
                return this.nama.trim().split(/\s+/).map(w => w[0]).slice(0, 2).join('').toUpperCase();
            },
            get usia() {
                if (!this.tanggalLahir) return null;
                const birth = new Date(this.tanggalLahir);
                const now = new Date();
                let age = now.getFullYear() - birth.getFullYear();
                const m = now.getMonth() - birth.getMonth();
                if (m < 0 || (m === 0 && now.getDate() < birth.getDate())) age--;
                return age >= 0 ? age : null;
            },
            get satkerName() {
                return this.satkers.find(s => String(s.id) === String(this.satkerId))?.nama ?? '—';
            },
            penyakitName(id) {
                return this.penyakits.find(p => String(p.id) === String(id))?.nama ?? id;
            },
        }));
    });
</script>
@endsection
