<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Admin\Monitoring\Concerns\BuildsReportQuery;
use App\Http\Controllers\Controller;
use App\Models\MessageReply;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use BuildsReportQuery;

    /**
     * Halaman detail laporan per entitas:
     * read-only + pagination sisi server + pencarian + filter + sorting.
     *
     * Entitas `respon` memakai layout khusus berbasis partials agar lebih
     * rapi & informatif (rekap pilihan tombol, badge sumber balasan, dst.).
     */
    public function show(Request $request, string $entity)
    {
        $config = $this->resolveReport($entity);

        $rows = $this->reportQuery($config, $request)
            ->paginate($this->reportPerPage($request))
            ->withQueryString();

        $stats = [];

        if (! empty($config['stats'])) {
            $stats = ($config['stats'])();
        }

        if ($entity === 'respon') {
            return view('admin.monitoring.report.respon.index', [
                'entity' => $entity,
                'config' => $config,
                'rows' => $rows,
                'stats' => $stats,
                'filterOptions' => $this->reportFilterOptions($config),
                'rekapPilihan' => $this->rekapPilihanTombol(),
            ]);
        }

        return view('admin.monitoring.report', [
            'entity' => $entity,
            'config' => $config,
            'rows' => $rows,
            'stats' => $stats,
            'filterOptions' => $this->reportFilterOptions($config),
        ]);
    }

    /**
     * Rekap pilihan tombol: hitung tiap label yang diklik user lewat
     * quick-reply / interactive, diurutkan dari yang paling banyak dipilih.
     *
     * @return array<int, array{label: string, jumlah: int}>
     */
    protected function rekapPilihanTombol(): array
    {
        return MessageReply::query()
            ->whereIn('payload->pesan->type', ['button', 'interactive'])
            ->whereNotNull('isi_pesan')
            ->where('isi_pesan', '!=', '')
            ->select('isi_pesan')
            ->selectRaw('COUNT(*) as jumlah')
            ->groupBy('isi_pesan')
            ->orderByDesc('jumlah')
            ->limit(20)
            ->get()
            ->map(fn ($row) => ['label' => (string) $row->isi_pesan, 'jumlah' => (int) $row->jumlah])
            ->all();
    }
}
