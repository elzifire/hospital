// Broadcast & Redis Queue Frontend Logic

let broadcastPollInterval = null;

async function loadDeviceOptionsForBroadcast() {
  const select = document.getElementById('bcDeviceSelect');
  if (!select) return;

  try {
    const res = await fetch(`${API_BASE}/devices`, { headers: authHeaders() });
    const data = await res.json();

    if (res.ok && data.success) {
      if (data.devices.length === 0) {
        select.innerHTML = '<option value="">(Belum ada device, silakan tambahkan di Tab Devices)</option>';
        return;
      }
      select.innerHTML = data.devices.map(d => `
        <option value="${d.id}" ${d.live_status !== 'connected' ? 'disabled' : ''}>
          ${d.name} [${d.provider.toUpperCase()}] ${d.live_status === 'connected' ? '✅ Terhubung' : '⚠️ Terputus'}
        </option>
      `).join('');

      if (data.devices[0]) {
        updateDeviceQuotaDisplay(data.devices[0].id);
      }
    }
  } catch (err) {
    console.error('Error loading devices for broadcast:', err);
  }
}

async function loadBroadcastMetrics() {
  try {
    const res = await fetch(`${API_BASE}/broadcasts/metrics`, { headers: authHeaders() });
    const data = await res.json();
    if (res.ok && data.success) {
      const m = data.metrics;
      document.getElementById('statTotalCampaigns').textContent = m.total_campaigns || 0;
      document.getElementById('statTotalMessages').textContent = m.total_messages || 0;
      document.getElementById('statTotalSent').textContent = m.total_sent || 0;
      document.getElementById('statTotalFailed').textContent = m.total_failed || 0;
    }
  } catch (err) {}
}

async function loadBroadcasts() {
  const container = document.getElementById('broadcastsList');
  if (!container) return;

  try {
    const res = await fetch(`${API_BASE}/broadcasts`, { headers: authHeaders() });
    const data = await res.json();

    if (!res.ok || !data.success) {
      container.innerHTML = `<div class="p-4 bg-red-50 text-red-700 text-xs rounded-xl">${data.message || 'Gagal memuat kampanye.'}</div>`;
      return;
    }

    if (data.broadcasts.length === 0) {
      container.innerHTML = `
        <div class="text-center py-12 text-slate-400 text-xs">
          Belum ada riwayat broadcast. Buat kampanye di formulir sebelah kiri.
        </div>
      `;
      return;
    }

    let hasProcessing = false;

    container.innerHTML = data.broadcasts.map(bc => {
      if (bc.status === 'processing') hasProcessing = true;

      const total = bc.total_recipients || 0;
      const sent = bc.sent_count || 0;
      const failed = bc.failed_count || 0;
      const processed = sent + failed;
      const percent = total > 0 ? Math.round((processed / total) * 100) : 0;

      let statusBadge = '';
      if (bc.status === 'completed') {
        statusBadge = `<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">SELESAI</span>`;
      } else if (bc.status === 'processing') {
        statusBadge = `<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 flex items-center space-x-1">
          <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-ping"></span>
          <span>ANTRIAN REDIS AKTIF (${percent}%)</span>
        </span>`;
      } else if (bc.status === 'scheduled') {
        statusBadge = `<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">TERJADWAL</span>`;
      } else {
        statusBadge = `<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">PENDING</span>`;
      }

      return `
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-3">
          <div class="flex items-start justify-between">
            <div>
              <span class="text-[10px] font-semibold text-slate-400">Device: ${bc.device_name || 'N/A'} [${(bc.device_provider || '').toUpperCase()}]</span>
              <h4 class="text-xs font-bold text-slate-800">${bc.title}</h4>
              <p class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">${bc.message}</p>
            </div>
            ${statusBadge}
          </div>

          <!-- Progress Bar -->
          <div>
            <div class="flex justify-between text-[11px] font-semibold mb-1">
              <span class="text-slate-600">${processed} / ${total} Pesan</span>
              <span class="text-slate-500">${percent}%</span>
            </div>
            <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden flex">
              <div class="bg-emerald-500 h-2 transition-all duration-500" style="width: ${total > 0 ? (sent / total) * 100 : 0}%"></div>
              <div class="bg-red-500 h-2 transition-all duration-500" style="width: ${total > 0 ? (failed / total) * 100 : 0}%"></div>
            </div>
            <div class="flex items-center space-x-3 text-[10px] mt-1 text-slate-500">
              <span class="text-emerald-600 font-semibold">✓ ${sent} Terkirim</span>
              <span class="text-red-500 font-semibold">✗ ${failed} Gagal</span>
              <span class="text-slate-400">Delay: ${bc.delay_min_ms}-${bc.delay_max_ms}ms</span>
            </div>
          </div>

          <div class="flex justify-between items-center text-[10px] text-slate-400 pt-2 border-t border-slate-200/60">
            <span>Dibuat: ${new Date(bc.created_at).toLocaleString('id-ID')}</span>
            <div class="space-x-2">
              ${bc.status === 'pending' ? `
                <button onclick="dispatchBroadcast(${bc.id})" class="px-2.5 py-1 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700">Kirim Sekarang</button>
              ` : ''}
              <button onclick="deleteBroadcast(${bc.id})" class="text-slate-400 hover:text-red-600">Hapus</button>
            </div>
          </div>
        </div>
      `;
    }).join('');

    // Auto poll if there are active queue jobs
    if (hasProcessing && !broadcastPollInterval) {
      broadcastPollInterval = setInterval(() => {
        loadBroadcasts();
        loadBroadcastMetrics();
      }, 3000);
    } else if (!hasProcessing && broadcastPollInterval) {
      clearInterval(broadcastPollInterval);
      broadcastPollInterval = null;
    }
  } catch (err) {
    console.error('Load broadcasts error:', err);
  }
}

// Parse recipients input
function parseRecipients(rawText) {
  const lines = rawText.split('\n');
  const recipients = [];

  for (const line of lines) {
    const trimmed = line.trim();
    if (!trimmed) continue;

    // Support comma or tab separated: "0812345678, Nama"
    const parts = trimmed.split(/[,;\t]/);
    const phone = parts[0]?.trim();
    const name = parts[1]?.trim() || '';

    if (phone) {
      recipients.push({ phone_number: phone, name });
    }
  }
  return recipients;
}

// Handle Form Submit
document.getElementById('broadcastForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const device_id = document.getElementById('bcDeviceSelect').value;
  const title = document.getElementById('bcTitle').value.trim();
  const message = document.getElementById('bcMessage').value.trim();
  const media_url = document.getElementById('bcMediaUrl').value.trim();
  const rawRecipients = document.getElementById('bcRecipients').value.trim();
  const delay_min_ms = parseInt(document.getElementById('bcDelayMin').value, 10);
  const delay_max_ms = parseInt(document.getElementById('bcDelayMax').value, 10);
  const btnSubmit = document.getElementById('btnSubmitBc');

  if (!device_id) {
    alert('Silakan pilih device WhatsApp terlebih dahulu.');
    return;
  }

  const recipients = parseRecipients(rawRecipients);
  if (recipients.length === 0) {
    alert('Daftar penerima tidak valid. Masukkan nomor telepon.');
    return;
  }

  btnSubmit.disabled = true;
  btnSubmit.classList.add('opacity-70');

  try {
    const res = await fetch(`${API_BASE}/broadcasts`, {
      method: 'POST',
      headers: authHeaders(),
      body: JSON.stringify({
        device_id,
        title,
        message,
        media_url: media_url || null,
        delay_min_ms,
        delay_max_ms,
        recipients,
        auto_dispatch: true
      })
    });

    const data = await res.json();
    if (res.ok && data.success) {
      alert(`🚀 Kampanye berhasil dibuat dan ${recipients.length} pesan telah dimasukkan ke Antrian Redis!`);
      document.getElementById('broadcastForm').reset();
      loadBroadcastMetrics();
      loadBroadcasts();
    } else {
      alert(data.message || 'Gagal membuat broadcast.');
    }
  } catch (err) {
    alert('Terjadi kesalahan saat menghubungi server.');
  } finally {
    btnSubmit.disabled = false;
    btnSubmit.classList.remove('opacity-70');
  }
});

async function dispatchBroadcast(broadcastId) {
  try {
    const res = await fetch(`${API_BASE}/broadcasts/${broadcastId}/dispatch`, {
      method: 'POST',
      headers: authHeaders()
    });
    const data = await res.json();
    if (res.ok) {
      loadBroadcasts();
    } else {
      alert(data.message || 'Gagal memulai antrian.');
    }
  } catch (err) {
    alert('Gagal menghubungi server.');
  }
}

async function deleteBroadcast(broadcastId) {
  if (!confirm('Hapus riwayat broadcast ini?')) return;

  try {
    const res = await fetch(`${API_BASE}/broadcasts/${broadcastId}`, {
      method: 'DELETE',
      headers: authHeaders()
    });
    const data = await res.json();
    if (res.ok) {
      loadBroadcastMetrics();
      loadBroadcasts();
    } else {
      alert(data.message || 'Gagal menghapus broadcast.');
    }
  } catch (err) {
    alert('Gagal menghubungi server.');
  }
}
