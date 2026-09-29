@php
    // Peta warna ubin ikon & badge (mengikuti tone di masing-masing fitur).
    $tileTone = [
        'sky'     => 'bg-sky-50 text-sky-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'violet'  => 'bg-violet-50 text-violet-600',
        'amber'   => 'bg-amber-50 text-amber-600',
        'rose'    => 'bg-rose-50 text-rose-600',
        'slate'   => 'bg-slate-100 text-slate-500',
        'indigo'  => 'bg-indigo-50 text-indigo-600',
        'teal'    => 'bg-teal-50 text-teal-600',
    ];

    $badgeTone = [
        'sky'     => 'bg-sky-50 text-sky-700 ring-sky-200/70',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200/70',
        'violet'  => 'bg-violet-50 text-violet-700 ring-violet-200/70',
        'amber'   => 'bg-amber-50 text-amber-700 ring-amber-200/70',
        'rose'    => 'bg-rose-50 text-rose-700 ring-rose-200/70',
        'slate'   => 'bg-slate-100 text-slate-500 ring-slate-200/70',
        'indigo'  => 'bg-indigo-50 text-indigo-700 ring-indigo-200/70',
        'teal'    => 'bg-teal-50 text-teal-700 ring-teal-200/70',
    ];
@endphp

<div class="space-y-6">
    @include('admin.monitoring.partials._header')
    @include('admin.monitoring.partials._stats')

    {{-- Tab Data | Grafik — memakai Alpine agar berpindah tanpa reload. --}}
    <div x-data="monMonitoring({ charts: @js($chartData ?? []), hasData: @js($chartHasData ?? false) })">
        <div class="mb-4 inline-flex items-center gap-1 rounded-2xl bg-white p-1 shadow-sm ring-1 ring-slate-200">
            <button type="button" @click="openData()"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold transition"
                    :class="tab === 'data' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                Data
            </button>
            <button type="button" @click="openGrafik()"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold transition"
                    :class="tab === 'grafik' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>
                Grafik
            </button>
        </div>

        <div x-show="tab === 'data'" class="space-y-6">
            @include('admin.monitoring.partials._table')
            @include('admin.monitoring.partials._info')
        </div>

        <div x-show="tab === 'grafik'" x-cloak>
            @include('admin.monitoring.partials._charts')
        </div>
    </div>
</div>

{{-- Registrasi komponen Alpine DI LUAR subtree x-data/x-show, agar selalu
     terdaftar sebelum layout memuat Alpine (pola sama dengan halaman lain). --}}
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('monMonitoring', (cfg) => ({
            tab: 'data',
            charts: cfg.charts || [],
            hasData: !!cfg.hasData,
            inited: false,

            openData() {
                this.tab = 'data';
            },

            openGrafik() {
                this.tab = 'grafik';
                this.$nextTick(() => {
                    if (this.inited || !this.hasData) return;
                    this.inited = true;
                    (this.charts || []).forEach((c) => {
                        const el = this.$root.querySelector('#monChart-' + c.key);
                        if (el && typeof Highcharts !== 'undefined') Highcharts.chart(el, this.chartOptions(c));
                    });
                });
            },

            chartOptions(c) {
                const unit = c.unit || '';
                const numberFmt = (v) => Highcharts.numberFormat(v, 0, ',', '.');

                if (c.type === 'pie') {
                    return {
                        chart: {
                            type: 'pie',
                            height: c.height || 320,
                            backgroundColor: 'transparent',
                            spacing: [8, 12, 12, 12],
                            style: { fontFamily: 'Inter, sans-serif' }
                        },
                        title: typeof c.total === 'number'
                            ? {
                                text: 'Total<br><b style="font-size:26px;color:#0f172a">' + numberFmt(c.total) + '</b>'
                                    + (unit ? '<br><span style="font-size:11px;color:#94a3b8">' + unit + '</span>' : ''),
                                align: 'center',
                                verticalAlign: 'middle',
                                useHTML: true,
                                style: { fontSize: '12px', color: '#64748b' }
                            }
                            : { text: null },
                        credits: { enabled: false },
                        tooltip: {
                            pointFormat: '<b>{point.y:,.0f}' + (unit ? ' ' + unit : '') + ' ({point.percentage:.1f}%)</b>',
                            borderRadius: 10,
                            shadow: true,
                            style: { fontSize: '12px' }
                        },
                        plotOptions: {
                            pie: {
                                innerSize: '68%',
                                dataLabels: { enabled: false },
                                showInLegend: true,
                                borderWidth: 2,
                                borderColor: '#ffffff',
                                states: { hover: { brightness: 0.05 } }
                            }
                        },
                        legend: {
                            enabled: c.legend !== false,
                            itemStyle: { fontSize: '12px', color: '#334155', fontWeight: 'bold' },
                            symbolRadius: 6
                        },
                        exporting: this.exportMenu(),
                        series: [{ type: 'pie', data: c.series || [] }]
                    };
                }

                const isBar = c.type === 'bar';
                const all = (c.series || []).flatMap((s) => s.data || []);
                const maxPoint = Math.max(0, ...all);

                return {
                    chart: {
                        type: isBar ? 'bar' : 'column',
                        height: c.height || 320,
                        backgroundColor: 'transparent',
                        spacing: [8, 12, 12, 12],
                        style: { fontFamily: 'Inter, sans-serif' }
                    },
                    colors: c.colors,
                    title: { text: null },
                    credits: { enabled: false },
                    xAxis: {
                        categories: c.categories || [],
                        lineColor: '#e2e8f0',
                        tickColor: '#e2e8f0',
                        labels: { style: { fontSize: '11px', fontWeight: '600', color: '#475569' } }
                    },
                    yAxis: {
                        min: 0,
                        gridLineColor: '#f1f5f9',
                        title: {
                            text: unit ? unit.charAt(0).toUpperCase() + unit.slice(1) : null,
                            style: { fontSize: '11px', color: '#94a3b8' }
                        },
                        labels: {
                            formatter() { return numberFmt(this.value); },
                            style: { fontSize: '10px', color: '#94a3b8' }
                        }
                    },
                    tooltip: {
                        pointFormat: '<b>{point.y:,.0f}' + (unit ? ' ' + unit : '') + '</b>',
                        borderRadius: 10,
                        shadow: true
                    },
                    legend: {
                        enabled: c.legend !== false,
                        itemStyle: { fontSize: '12px', color: '#334155', fontWeight: 'bold' },
                        symbolRadius: 6
                    },
                    plotOptions: {
                        column: {
                            borderRadius: 5,
                            groupPadding: 0.15,
                            pointPadding: 0.12,
                            borderWidth: 0,
                            dataLabels: {
                                enabled: maxPoint > 0 && maxPoint <= 40,
                                style: { fontSize: '10px', fontWeight: 'bold', color: '#64748b', textOutline: 'none' }
                            }
                        },
                        bar: {
                            colorByPoint: !(c.series || []).some((s) => s.color),
                            borderRadius: 4,
                            borderWidth: 0,
                            dataLabels: {
                                enabled: maxPoint > 0 && maxPoint <= 40,
                                style: { fontSize: '10px', fontWeight: 'bold', color: '#64748b', textOutline: 'none' }
                            }
                        },
                        series: { animation: { duration: 500 } }
                    },
                    exporting: this.exportMenu(),
                    series: c.series || []
                };
            },

            exportMenu() {
                return {
                    enabled: true,
                    buttons: {
                        contextButton: {
                            menuItems: ['viewFullscreen', 'downloadPNG', 'downloadJPEG', 'downloadPDF', 'downloadSVG', 'separator', 'downloadCSV', 'downloadXLS', 'printChart']
                        }
                    }
                };
            }
        }));
    });
</script>