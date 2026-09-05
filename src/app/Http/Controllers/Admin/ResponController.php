<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ResponController extends Controller
{
    /**
     * Halaman Respon — daftar balasan WhatsApp per nomor telepon.
 * Data balasan masuk secara otomatis via webhook (read-only).
     */
    public function index()
    {
        return view('admin.respon.index');
    }

    /**
     * Halaman detail percakapan untuk satu nomor telepon.
     */
    public function show(string $nomor)
    {
        return view('admin.respon.show', ['nomor' => $nomor]);
    }
}
