<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class DigitalReminderController extends Controller
{
    /**
     * Halaman Digital Reminder (referensi UI — belum ada logic).
     */
    public function index()
    {
        return view('admin.digital-reminder.index');
    }

    /**
     * Halaman Template Pesan (referensi UI — belum ada logic).
     */
    public function template()
    {
        return view('admin.digital-reminder.template');
    }

    /**
     * Halaman tambah reminder (referensi UI — belum ada logic).
     */
    public function create()
    {
        return view('admin.digital-reminder.create');
    }

    /**
     * Halaman edit reminder (referensi UI — belum ada logic).
     */
    public function edit()
    {
        return view('admin.digital-reminder.edit');
    }

    /**
     * Halaman import reminder (referensi UI — belum ada logic).
     */
    public function import()
    {
        return view('admin.digital-reminder.import');
    }
}
