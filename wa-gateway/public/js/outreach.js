// Outreach Inbox & Webhook Monitor

async function loadOutreachStats() {
  try {
    const res = await fetch(`${API_BASE}/outreach/stats`, { headers: authHeaders() });
    const data = await res.json();
    if (res.ok && data.success) {
      const unread = parseInt(data.stats.unread_messages || 0, 10);
      const badge = document.getElementById('unreadBadge');
      if (badge) {
        if (unread > 0) {
          badge.textContent = unread;
          badge.classList.remove('hidden');
        } else {
          badge.classList.add('hidden');
        }
      }
    }
  } catch (err) {}
}

async function loadOutreachMessages() {
  const table = document.getElementById('outreachMessagesTable');
  const deviceFilter = document.getElementById('outreachDeviceFilter');
  if (!table) return;

  const deviceId = deviceFilter?.value || '';

  try {
    const url = new URL(`${window.location.origin}${API_BASE}/outreach`);
    if (deviceId) url.searchParams.append('device_id', deviceId);

    const res = await fetch(url.toString(), { headers: authHeaders() });
    const data = await res.json();

    if (!res.ok || !data.success) {
      table.innerHTML = `<tr><td colspan="6" class="py-4 text-center text-red-500">${data.message || 'Gagal memuat pesan.'}</td></tr>`;
      return;
    }

    if (data.messages.length === 0) {
      table.innerHTML = `
        <tr>
          <td colspan="6" class="py-12 text-center text-slate-400">
            Belum ada pesan masuk dari user atau pasien.
          </td>
        </tr>
      `;
      return;
    }

    table.innerHTML = data.messages.map(m => {
      let webhookBadge = '';
      if (m.webhook_sent) {
        webhookBadge = `<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">✓ Terkirim ke Laravel</span>`;
      } else {
        webhookBadge = `<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-500 border border-slate-200">Tidak ada Webhook</span>`;
      }

      return `
        <tr class="${!m.is_read ? 'bg-emerald-50/40 font-semibold' : 'hover:bg-slate-50'} transition-all">
          <td class="py-3 px-4">
            <div class="font-bold text-slate-800">${m.from_name || 'Unknown'}</div>
            <div class="text-[11px] text-slate-400 font-mono">+${m.from_number}</div>
          </td>
          <td class="py-3 px-4">
            <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-[10px] uppercase">${m.device_name || 'Device'}</span>
          </td>
          <td class="py-3 px-4 max-w-xs">
            <div class="text-slate-700 truncate">${m.message || '[Media Message]'}</div>
          </td>
          <td class="py-3 px-4 whitespace-nowrap text-slate-400 text-[11px]">
            ${new Date(m.received_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}
            <div class="text-[10px]">${new Date(m.received_at).toLocaleDateString('id-ID')}</div>
          </td>
          <td class="py-3 px-4 whitespace-nowrap">
            ${webhookBadge}
          </td>
          <td class="py-3 px-4 text-right whitespace-nowrap space-x-2">
            <button onclick="openReplyModal(${m.id}, '${m.from_number}', '${(m.from_name || '').replace(/'/g, "\\'")}', '${(m.message || '').replace(/'/g, "\\'")}')" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-semibold">
              Balas
            </button>
            ${!m.is_read ? `
              <button onclick="markAsRead(${m.id})" class="px-2 py-1 text-slate-400 hover:text-slate-700 text-[11px]">
                Tandai Dibaca
              </button>
            ` : ''}
          </td>
        </tr>
      `;
    }).join('');
  } catch (err) {
    console.error('Load outreach error:', err);
  }
}

function openReplyModal(msgId, fromNumber, fromName, originalMsg) {
  document.getElementById('replyMessageId').value = msgId;
  document.getElementById('replyToLabel').textContent = `${fromName} (+${fromNumber})`;
  document.getElementById('replyOriginalMsg').textContent = `"${originalMsg}"`;
  document.getElementById('replyText').value = '';
  openModal('replyModal');
}

// Reply form submit
document.getElementById('replyForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const msgId = document.getElementById('replyMessageId').value;
  const reply_message = document.getElementById('replyText').value.trim();

  try {
    const res = await fetch(`${API_BASE}/outreach/${msgId}/reply`, {
      method: 'POST',
      headers: authHeaders(),
      body: JSON.stringify({ reply_message })
    });
    const data = await res.json();
    if (res.ok && data.success) {
      alert('✅ Balasan berhasil dikirim!');
      closeModal('replyModal');
      loadOutreachMessages();
      loadOutreachStats();
    } else {
      alert(data.message || 'Gagal mengirim balasan.');
    }
  } catch (err) {
    alert('Gagal menghubungi server.');
  }
});

async function markAsRead(msgId) {
  try {
    await fetch(`${API_BASE}/outreach/${msgId}/read`, {
      method: 'PATCH',
      headers: authHeaders()
    });
    loadOutreachMessages();
    loadOutreachStats();
  } catch (err) {}
}

document.addEventListener('DOMContentLoaded', () => {
  if (window.location.pathname.includes('dashboard')) {
    loadOutreachStats();
    // Refresh stats every 10s
    setInterval(loadOutreachStats, 10000);
  }
});
