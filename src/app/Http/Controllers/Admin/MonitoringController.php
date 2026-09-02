<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class MonitoringController extends Controller
{
    /**
     * Halaman Monitoring (referensi UI — belum ada logic).
     */
    public function index()
    {
        return view('admin.monitoring.index');
    }
}
