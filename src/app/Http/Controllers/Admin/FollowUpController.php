<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class FollowUpController extends Controller
{
    /**
     * Halaman Follow Up (referensi UI — belum ada logic).
     */
    public function index()
    {
        return view('admin.follow-up.index');
    }

    /**
     * Halaman tambah follow up (referensi UI — belum ada logic).
     */
    public function create()
    {
        return view('admin.follow-up.create');
    }

    /**
     * Halaman edit follow up (referensi UI — belum ada logic).
     */
    public function edit()
    {
        return view('admin.follow-up.edit');
    }

    /**
     * Halaman import follow up (referensi UI — belum ada logic).
     */
    public function import()
    {
        return view('admin.follow-up.import');
    }
}
