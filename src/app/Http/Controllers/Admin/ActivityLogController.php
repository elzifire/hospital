<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    /**
     * Daftar jejak perubahan data (before & after) dari seluruh modul.
     * Filter: jenis event, nama log, pencarian subject/deskripsi.
     */
    public function index(Request $request): View
    {
        $events = ['dibuat', 'diubah', 'dihapus', 'dipulihkan'];

        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->when($request->query('event'), function ($q, string $event): void {
                if (in_array($event, ['created', 'updated', 'deleted', 'restored'], true)) {
                    $q->where('event', $event);
                }
            })
            ->when($request->query('log'), function ($q, string $log): void {
                $q->where('log_name', $log);
            })
            ->when($request->query('cari'), function ($q, string $cari): void {
                $q->where(function ($q) use ($cari): void {
                    $q->where('description', 'like', '%'.$cari.'%')
                        ->orWhere('subject_type', 'like', '%'.$cari.'%')
                        ->orWhere('log_name', 'like', '%'.$cari.'%');
                });
            })
            ->latest()
            ->limit(500)
            ->get();

        return view('admin.activity-log.index', [
            'activities' => $activities,
            'events' => $events,
            'logNames' => Activity::query()
                ->select('log_name')
                ->distinct()
                ->orderBy('log_name')
                ->pluck('log_name')
                ->map(fn ($nama): string => (string) $nama)
                ->filter(fn ($nama): bool => $nama !== '')
                ->values(),
        ]);
    }

    /**
     * Detail satu aktivitas — menampilkan before & after tiap kolom.
     */
    public function show(Activity $activity): View
    {
        $activity->load(['causer', 'subject']);

        return view('admin.activity-log.show', ['activity' => $activity]);
    }
}
