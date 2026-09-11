<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MessageTemplateController extends Controller
{
    /**
     * Token internal yang boleh dipetakan jadi parameter template Meta.
     */
    private const TOKEN_PARAM = ['nama', 'nip', 'satker', 'obat', 'poli', 'dokter', 'tanggal', 'jam'];

    public function store(Request $request)
    {
        $data = $this->validasi($request);

        if ($data instanceof RedirectResponse) {
            return $data;
        }

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
        $data = $this->validasi($request, $template);

        if ($data instanceof RedirectResponse) {
            return $data;
        }

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

    /**
     * Validasi form + normalisasi pemetaan template Meta (nama, bahasa,
     * urutan parameter dari input "nama,poli,tanggal").
     *
     * @return array<string, mixed>|RedirectResponse
     */
    protected function validasi(Request $request, ?MessageTemplate $template = null): array|RedirectResponse
    {
        $data = $request->validate([
            'template_category_id' => ['nullable', 'exists:template_categories,id'],
            'judul'                => ['required', 'string', 'max:200'],
            'kode'                 => ['nullable', 'string', 'max:50', 'unique:message_templates,kode' . ($template ? ',' . $template->id : '')],
            'channel'              => ['required', 'string', 'in:WhatsApp,SMS,Email'],
            'konten'               => ['required', 'string'],
            'deskripsi'            => ['nullable', 'string', 'max:255'],
            'meta_template_name'   => ['nullable', 'string', 'max:255'],
            'meta_language'        => ['nullable', 'string', 'max:10'],
            'meta_param_tokens'    => ['nullable', 'string', 'max:255'],
        ]);

        $tokens = $this->tokensParam($data['meta_param_tokens'] ?? null);

        if ($tokens === false) {
            return back()->withInput()->with(
                'error',
                'Token parameter tidak dikenal. Yang valid: '
                    .implode(', ', array_map(fn ($t) => '{'.$t.'}', self::TOKEN_PARAM)).'.'
            );
        }

        $data['meta_param_tokens'] = $tokens;
        $data['meta_template_name'] = blank($data['meta_template_name'] ?? null) ? null : $data['meta_template_name'];
        $data['meta_language'] = blank($data['meta_language'] ?? null) ? null : $data['meta_language'];

        return $data;
    }

    /**
     * Ubah input "nama,poli,tanggal" menjadi array token parameter —
     * token ke-1 menjadi parameter {{1}} di template Meta, dst.
     * False bila ada token yang tidak dikenal.
     *
     * @return array<int, string>|false|null
     */
    protected function tokensParam(?string $input): array|bool|null
    {
        if (blank($input)) {
            return null;
        }

        $tokens = array_values(array_unique(array_filter(array_map(
            fn (string $t) => strtolower(trim($t)),
            explode(',', $input),
        ))));

        foreach ($tokens as $token) {
            if (! in_array($token, self::TOKEN_PARAM, true)) {
                return false;
            }
        }

        return $tokens === [] ? null : $tokens;
    }
}
