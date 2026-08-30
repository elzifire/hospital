// Activity Logs Frontend Logic

async function loadLogs() {
  const table = document.getElementById('logsTableBody');
  const typeFilter = document.getElementById('logTypeFilter')?.value || '';
  const levelFilter = document.getElementById('logLevelFilter')?.value || '';
  const searchInput = document.getElementById('logSearchInput')?.value || '';

  if (!table) return;

  try {
    const url = new URL(`${window.location.origin}${API_BASE}/logs`);
    if (typeFilter) url.searchParams.append('type', typeFilter);
    if (levelFilter) url.searchParams.append('level', levelFilter);
    if (searchInput) url.searchParams.append('search', searchInput);
    url.searchParams.append('limit', '50');

    const res = await fetch(url.toString(), { headers: authHeaders() });
    const data = await res.json();

    if (!res.ok || !data.success) {
      table.innerHTML = `<tr><td colspan="6" class="py-4 text-center text-red-500">${data.message || 'Gagal memuat log.'}</td></tr>`;
      return;
    }

    if (data.logs.length === 0) {
      table.innerHTML = `
        <tr>
          <td colspan="6" class="py-12 text-center text-slate-400 text-xs">
            Belum ada catatan aktivitas di sistem.
          </td>
        </tr>
      `;
      return;
    }

    table.innerHTML = data.logs.map(log => {
      let levelBadge = '';
      if (log.level === 'success') {
        levelBadge = `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">SUCCESS</span>`;
      } else if (log.level === 'error') {
        levelBadge = `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-50 text-red-700 border border-red-200">ERROR</span>`;
      } else if (log.level === 'warn') {
        levelBadge = `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">WARN</span>`;
      } else {
        levelBadge = `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">INFO</span>`;
      }

      return `
        <tr class="hover:bg-slate-50 text-xs transition-all">
          <td class="py-3 px-4 whitespace-nowrap text-slate-400 font-mono text-[11px]">
            ${new Date(log.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })}
            <div class="text-[10px] text-slate-400">${new Date(log.created_at).toLocaleDateString('id-ID')}</div>
          </td>
          <td class="py-3 px-4">
            <span class="font-bold text-slate-700 uppercase text-[10px] bg-slate-100 px-1.5 py-0.5 rounded">${log.type}</span>
          </td>
          <td class="py-3 px-4">${levelBadge}</td>
          <td class="py-3 px-4 font-mono font-semibold text-slate-700 text-[11px]">${log.action}</td>
          <td class="py-3 px-4 font-mono text-slate-500 text-[11px]">${log.recipient ? '+' + log.recipient : '-'}</td>
          <td class="py-3 px-4 text-slate-600 max-w-sm">
            <div class="truncate font-medium">${log.message || '-'}</div>
            ${log.device_name ? `<span class="text-[10px] text-slate-400">Device: ${log.device_name}</span>` : ''}
          </td>
        </tr>
      `;
    }).join('');
  } catch (err) {
    console.error('Load logs error:', err);
  }
}

async function updateDeviceQuotaDisplay(deviceId) {
  const quotaBadge = document.getElementById('deviceQuotaBadge');
  if (!quotaBadge || !deviceId) return;

  try {
    const res = await fetch(`${API_BASE}/logs/quota/${deviceId}`, { headers: authHeaders() });
    const data = await res.json();
    if (res.ok && data.success) {
      quotaBadge.innerHTML = `
        <span class="inline-flex items-center space-x-1 font-semibold ${data.remainingQuota > 20 ? 'text-emerald-700 bg-emerald-50' : 'text-amber-700 bg-amber-50'} px-2 py-1 rounded-lg border border-slate-200">
          <span>🛡️ Kuota Hari Ini: <strong>${data.sentToday} / ${data.dailyLimit}</strong> pesan (${data.remainingQuota} tersisa)</span>
        </span>
      `;
    }
  } catch (err) {}
}
