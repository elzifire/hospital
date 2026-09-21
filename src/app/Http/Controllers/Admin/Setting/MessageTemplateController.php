<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Broadcasting\WhatsApp\MetaTemplateRegistrar;
use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Models\TemplateCategory;
use App\Support\TextSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MessageTemplateController extends Controller
{
    /**
     * Form pembuatan template baru. Template yang dibuat lewat sistem bisa
     * langsung didaftarkan ke Meta WhatsApp untuk persetujuan.
     */
    public function create()
    {
        return view('admin.setting.template.create', [
            'categories' => TemplateCategory::orderBy('nama')->get(),
            'variables' => MessageTemplate::variables(),
            'metaSiap' => $this->metaTerkonfigurasi(),
            'bahasaDefaultMeta' => (string) config('whatsapp.meta.default_language', 'en_US'),
        ]);
    }

    /**
     * Simpan template baru. Bila dicentang "Daftarkan ke Meta", template
     * langsung dikirim ke Meta WhatsApp; bila gagal / Meta belum dikonfigurasi,
     * template tetap tersimpan lokal dengan pesan yang jelas.
     */
    public function store(Request $request, MetaTemplateRegistrar $registrar)
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'template_category_id' => ['nullable', 'exists:template_categories,id'],
            'channel' => ['required', 'string', 'in:WhatsApp,SMS,Email'],
            'konten' => ['required', 'string'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'daftar_ke_meta' => ['nullable', 'boolean'],
            'meta_language' => ['nullable', 'string', 'in:id,id_ID,en_US,en_GB'],
            'meta_category' => ['nullable', 'string', 'in:UTILITY,MARKETING,AUTHENTICATION'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['konten'] = TextSanitizer::win1252($data['konten']);
        $data['deskripsi'] = $data['deskripsi'] ?? null;
        if ($data['deskripsi'] !== null) {
            $data['deskripsi'] = TextSanitizer::win1252($data['deskripsi']);
        }

        $template = MessageTemplate::create($data);

        $daftarMeta = $request->boolean('daftar_ke_meta') && $data['channel'] === 'WhatsApp';

        if (! $daftarMeta) {
            return redirect()
                ->route('admin.setting.index')
                ->with('success', 'Template pesan "'.$template->judul.'" berhasil dibuat (tersimpan lokal).');
        }

        $hasil = $registrar->daftar(
            $template,
            (string) $request->input('meta_language', ''),
            (string) $request->input('meta_category', 'UTILITY'),
        );

        if ($hasil['error'] !== null) {
            return redirect()
                ->route('admin.setting.index')
                ->with('error', 'Template berhasil disimpan lokal, tetapi pendaftaran ke Meta gagal: '.$hasil['error']);
        }

        $template->update([
            'meta_template_id' => $hasil['id'],
            'meta_template_name' => $hasil['name'],
            'meta_language' => $hasil['language'],
            'meta_status' => $hasil['status'],
            'meta_category' => $hasil['category'],
            'meta_components' => $hasil['components'],
            'last_synced_at' => now(),
            'is_active' => $data['is_active'] && $hasil['status'] === 'APPROVED',
        ]);

        return redirect()
            ->route('admin.setting.index')
            ->with(
                'success',
                'Template "'.$template->judul.'" berhasil dibuat dan didaftarkan ke Meta (status: '.$hasil['status'].'). '
                .'Template otomatis aktif setelah disetujui Meta.'
            );
    }

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
            'judul' => ['required', 'string', 'max:255'],
            'template_category_id' => ['nullable', 'exists:template_categories,id'],
            'channel' => ['required', 'string', 'in:WhatsApp,SMS,Email'],
            'konten' => ['required', 'string'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
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

    protected function metaTerkonfigurasi(): bool
    {
        $config = config('whatsapp.meta');

        return filled(trim((string) ($config['business_account_id'] ?? '')))
            && filled(trim((string) ($config['token'] ?? '')));
    }
}
