<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Models\BroadcastRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Tab "Aturan Pesan" di modul Setting — pasangkan template ke tiap
 * rule generate (outreach H-7/H-1; follow up H-1/hari-H/tidak datang)
 * dan aktif/nonaktifkan rulenya.
 */
class BroadcastRuleController extends Controller
{
    public function update(Request $request, BroadcastRule $aturan)
    {
        $data = $request->validate([
            'message_template_id' => [
                'nullable',
                Rule::exists('message_templates', 'id')->where('is_active', true),
            ],
            'is_active' => ['required', 'boolean'],
        ]);

        $aturan->update([
            'message_template_id' => $data['message_template_id'] ?? null,
            'is_active' => (bool) $data['is_active'],
        ]);

        $label = $aturan->jenis === 'outreach' ? 'Outreach' : 'Follow Up';

        return back()->with('success', "Aturan {$label} ".strtoupper($aturan->rule).' berhasil diperbarui.');
    }
}
