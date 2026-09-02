<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class OutreachController extends Controller
{
    /**
     * Halaman Outreach — menangkap balasan pesan WhatsApp (referensi UI).
     */
    public function index()
    {
        return view('admin.outreach.index');
    }

    /**
     * Halaman tambah pesan masuk (referensi UI — belum ada logic).
     */
    public function create()
    {
        return view('admin.outreach.create');
    }

    /**
     * Halaman edit pesan masuk (referensi UI — belum ada logic).
     */
    public function edit()
    {
        return view('admin.outreach.edit');
    }

    /**
     * Halaman import pesan masuk (referensi UI — belum ada logic).
     */
    public function import()
    {
        return view('admin.outreach.import');
    }
}
