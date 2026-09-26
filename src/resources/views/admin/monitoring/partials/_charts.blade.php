{{-- Kartu grafik tab "Grafik" — dirender Highcharts lokal (offline-ready).
     Konsumsi variabel: $chartData (list spec), $chartHasData (bool). --}}

@if (! $chartHasData)
    <div class="rounded-2xl bg-white px-6 py-16 text-center shadow-sm ring-1 ring-slate-200">
        <div class="mx-auto flex max-w-md flex-col items-center">
            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 ring-8 ring-slate-50">
                <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>
            </div>
            <h3 class="text-base font-bold text-slate-900">Belum ada data grafik</h3>
            <p class="mt-1 text-sm text-slate-500">Grafik akan tampil saat ada data {{ $config['label'] }} yang sesuai dengan rentang dan filter aktif.</p>
        </div>
    </div>
@else
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        @foreach ($chartData as $chart)
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 {{ ($chart['span'] ?? 1) === 2 ? 'lg:col-span-2' : '' }}">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 pb-3 pt-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">{{ $chart['title'] }}</h3>
                        @if (! empty($chart['subtitle']))
                            <p class="mt-0.5 text-xs text-slate-400">{{ $chart['subtitle'] }}</p>
                        @endif
                    </div>
                    <svg class="h-4 w-4 flex-shrink-0 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>
                </div>
                <div id="monChart-{{ $chart['key'] }}" style="height: {{ $chart['height'] ?? 320 }}px" class="px-3 pb-3"></div>
            </div>
        @endforeach
    </div>
@endif

{{-- HIGHCHARTS CORE & CLIENT-SIDE EXPORT MODULES (OFFLINE READY) --}}
<script src="{{ asset('vendor/highcharts/highcharts.js') }}"></script>
<script src="{{ asset('vendor/highcharts/exporting.js') }}"></script>
<script src="{{ asset('vendor/highcharts/offline-exporting.js') }}"></script>
<script src="{{ asset('vendor/highcharts/export-data.js') }}"></script>
<script src="{{ asset('vendor/highcharts/accessibility.js') }}"></script>

<script>
    // Konfigurasi global Highcharts untuk halaman monitor (bahasa Indonesia).
    Highcharts.setOptions({
        lang: {
            contextButtonTitle: 'Menu Ekspor Grafik',
            downloadPNG: 'Unduh Gambar PNG',
            downloadJPEG: 'Unduh Gambar JPEG',
            downloadPDF: 'Unduh Dokumen PDF',
            downloadSVG: 'Unduh Vektor SVG',
            downloadCSV: 'Unduh Format CSV',
            downloadXLS: 'Unduh Format Excel (XLS)',
            viewData: 'Lihat Tabel Data',
            viewFullscreen: 'Tampilkan Layar Penuh',
            printChart: 'Cetak Grafik',
            months: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            shortMonths: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            weekdays: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
            thousandsSep: '.',
            decimalPoint: ','
        },
        exporting: {
            fallbackToExportServer: false
        }
    });

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
                        const el = this.$el.querySelector('#monChart-' + c.key);
                        if (el) Highcharts.chart(el, this.chartOptions(c));
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