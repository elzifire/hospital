@if (session('success'))
    <div class="rounded-2xl bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="rounded-2xl bg-rose-50 px-5 py-4 text-sm font-medium text-rose-700 ring-1 ring-rose-200">{{ session('error') }}</div>
@endif