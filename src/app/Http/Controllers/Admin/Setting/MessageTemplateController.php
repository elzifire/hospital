<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use Illuminate\Support\Str;

class MessageTemplateController extends Controller
{
    public function destroy(MessageTemplate $template)
    {
        $judul = $template->judul;
        $template->delete();

        return redirect()->route('admin.setting.index')
            ->with('success', "Template pesan \"{$judul}\" berhasil dihapus.");
    }

    public function duplicate(MessageTemplate $template)
    {
        $replica = $template->replicate();
        $replica->judul = $template->judul.' (Salinan)';
        $replica->kode = 'TMP-'.strtoupper(Str::random(6));
        $replica->dipakai_count = 0;
        $replica->save();

        return redirect()->route('admin.setting.index')
            ->with('success', "Template pesan berhasil diduplikasi sebagai \"{$replica->judul}\".");
    }
}
