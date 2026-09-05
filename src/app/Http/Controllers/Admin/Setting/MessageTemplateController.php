<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MessageTemplateController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'template_category_id' => ['nullable', 'exists:template_categories,id'],
            'judul'                => ['required', 'string', 'max:200'],
            'kode'                 => ['nullable', 'string', 'max:50', 'unique:message_templates,kode'],
            'channel'              => ['required', 'string', 'in:WhatsApp,SMS,Email'],
            'konten'               => ['required', 'string'],
            'deskripsi'            => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_active'] = $request->has('is_active');
        if (empty($data['kode'])) {
            $data['kode'] = 'TMP-' . strtoupper(Str::random(6));
        }

        $template = MessageTemplate::create($data);

        return redirect()->route('admin.setting.index')
            ->with('success', "Template pesan \"{$template->judul}\" berhasil ditambahkan.");
    }

    public function update(Request $request, MessageTemplate $template)
    {
        $data = $request->validate([
            'template_category_id' => ['nullable', 'exists:template_categories,id'],
            'judul'                => ['required', 'string', 'max:200'],
            'kode'                 => ['nullable', 'string', 'max:50', 'unique:message_templates,kode,' . $template->id],
            'channel'              => ['required', 'string', 'in:WhatsApp,SMS,Email'],
            'konten'               => ['required', 'string'],
            'deskripsi'            => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_active'] = $request->has('is_active');

        $template->update($data);

        return redirect()->route('admin.setting.index')
            ->with('success', "Template pesan \"{$template->judul}\" berhasil diperbarui.");
    }

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
        $replica->judul = $template->judul . ' (Salinan)';
        $replica->kode = 'TMP-' . strtoupper(Str::random(6));
        $replica->dipakai_count = 0;
        $replica->save();

        return redirect()->route('admin.setting.index')
            ->with('success', "Template pesan berhasil diduplikasi sebagai \"{$replica->judul}\".");
    }
}
