@php($responPages = [
    'balasan' => ['label' => 'Balasan WhatsApp', 'route' => 'admin.respon.index'],
    'data'    => ['label' => 'Data Respon', 'route' => 'admin.respon.data'],
    'manual'  => ['label' => 'Input Manual', 'route' => 'admin.respon.manual'],
    'import'  => ['label' => 'Import Excel', 'route' => 'admin.respon.import'],
])
<div class="flex flex-wrap items-center gap-1 border-b border-slate-200">
    @foreach ($responPages as $key => $page)
        <a href="{{ route($page['route']) }}"
           class="inline-flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-semibold transition
                  {{ ($active ?? 'balasan') === $key ? 'border-sky-500 text-sky-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
            {{ $page['label'] }}
        </a>
    @endforeach
</div>