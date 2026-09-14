<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Models\TemplateCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MessageTemplateController extends Controller
{
    public function edit(MessageTemplate $template)
    {
        return view('admin.setting.template.edit', [
            'template' => $template,
            'categories' => TemplateCategory::orderBy('nama')->get(),
            'variables' => MessageTemplate::variables(),
        ]);
    }

    public function update(Request $request, MessageTemplate $template)
    {
        $data = $request->validate([
            'judul'                => ['required', 'string', 'max:255'],
            'template_category_id' => ['nullable', 'exists:template_categories,id'],
            'channel'              => ['required', 'string', 'in:WhatsApp,SMS,Email'],
            'konten'               => ['required', 'string'],
            'deskripsi'            => ['nullable', 'string', 'max:255'],
            'is_active'            => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $template->update($data);

        return redirect()->route('admin.setting.index', ['tab' => 'template'])
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
        $replica->judul = $template->judul.' (Salinan)';
        $replica->kode = 'TMP-'.strtoupper(Str::random(6));
        $replica->dipakai_count = 0;
        $replica->save();

        return redirect()->route('admin.setting.index')
            ->with('success', "Template pesan berhasil diduplikasi sebagai \"{$replica->judul}\".");
    }
}
