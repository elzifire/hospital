// Device & Provider Management

let activeQrPollInterval = null;
let currentQrDeviceId = null;

function switchTab(tabId) {
  const tabs = ['devices', 'broadcast', 'outreach', 'logs', 'docs'];
  tabs.forEach(t => {
    const section = document.getElementById(`tab${t.charAt(0).toUpperCase() + t.slice(1)}`);
    const btn = document.getElementById(`tabBtn${t.charAt(0).toUpperCase() + t.slice(1)}`);
    if (section) section.classList.toggle('hidden', t !== tabId);
    if (btn) {
      if (t === tabId) {
        btn.className = 'px-3.5 py-1.5 rounded-lg transition-all bg-white text-emerald-700 shadow-sm font-semibold';
      } else {
        btn.className = 'px-3.5 py-1.5 rounded-lg transition-all text-slate-600 hover:text-slate-900 font-semibold';
      }
    }
  });

  if (tabId === 'devices') loadDevices();
  if (tabId === 'broadcast') {
    loadBroadcastMetrics();
    loadBroadcasts();
    loadDeviceOptionsForBroadcast();
  }
  if (tabId === 'outreach') {
    loadOutreachStats();
    loadOutreachMessages();
  }
  if (tabId === 'logs') {
    loadLogs();
  }
}

function openModal(modalId) {
  document.getElementById(modalId)?.classList.remove('hidden');
}

function closeModal(modalId) {
  document.getElementById(modalId)?.classList.add('hidden');
}

function openAddDeviceModal() {
  document.getElementById('addDeviceForm')?.reset();
  toggleProviderConfig();
  openModal('addDeviceModal');
}

function toggleProviderConfig() {
  const provider = document.getElementById('newDevProvider').value;
  const fonnteField = document.getElementById('fonnteConfigFields');
  if (provider === 'fonnte') {
    fonnteField.classList.remove('hidden');
  } else {
    fonnteField.classList.add('hidden');
  }
}

async function loadDevices() {
  const container = document.getElementById('devicesContainer');
  if (!container) return;

  try {
    const res = await fetch(`${API_BASE}/devices`, { headers: authHeaders() });
    const data = await res.json();

    if (!res.ok || !data.success) {
      container.innerHTML = `<div class="col-span-full p-4 bg-red-50 text-red-700 text-xs rounded-xl">${data.message || 'Gagal memuat device.'}</div>`;
      return;
    }

    if (data.devices.length === 0) {
      container.innerHTML = `
        <div class="col-span-full text-center py-12 bg-white rounded-2xl border border-slate-200 border-dashed p-6">
          <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
          </div>
          <h3 class="text-sm font-bold text-slate-700">Belum ada Device</h3>
          <p class="text-xs text-slate-400 mt-1 mb-4">Tambahkan sesi WhatsApp atau token API Fonnte untuk mulai mengirim pesan.</p>
          <button onclick="openAddDeviceModal()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-md shadow-emerald-200">
            Tambah Device Pertama
          </button>
        </div>
      `;
      return;
    }

    container.innerHTML = data.devices.map(dev => {
      const isConnected = dev.live_status === 'connected';
      const isConnecting = dev.live_status === 'connecting';
      const isBaileys = dev.provider === 'baileys';

      let statusBadge = '';
      if (isConnected) {
        statusBadge = `<span class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
          <span>Terhubung</span>
        </span>`;
      } else if (isConnecting) {
        statusBadge = `<span class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
          <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-spin"></span>
          <span>Menghubungkan...</span>
        </span>`;
      } else {
        statusBadge = `<span class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
          <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
          <span>Terputus</span>
        </span>`;
      }

      return `
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
          <div>
            <div class="flex items-start justify-between mb-3">
              <div>
                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-slate-100 text-slate-500 mb-1 inline-block">
                  ${dev.provider.toUpperCase()}
                </span>
                <h3 class="text-sm font-bold text-slate-800">${dev.name}</h3>
                <p class="text-xs text-slate-400 mt-0.5">${dev.phone_number ? '+' + dev.phone_number : 'Belum tertaut nomor'}</p>
              </div>
              ${statusBadge}
            </div>

            ${dev.webhook_url ? `
              <div class="mt-2 text-[10px] text-slate-500 bg-slate-50 p-2 rounded-lg border border-slate-100 truncate">
                <span class="font-semibold text-slate-600">Webhook:</span> ${dev.webhook_url}
              </div>
            ` : ''}
          </div>

          <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between gap-2">
            <div class="flex items-center space-x-2">
              ${!isConnected ? `
                <button onclick="startConnect(${dev.id}, '${dev.provider}', '${dev.name}')" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-sm">
                  ${isBaileys ? '📷 Scan QR' : '🔗 Hubungkan'}
                </button>
              ` : `
                <button onclick="disconnectDevice(${dev.id})" class="px-3 py-1.5 bg-slate-100 hover:bg-red-50 hover:text-red-700 text-slate-700 rounded-xl text-xs font-semibold">
                  Putuskan
                </button>
                <button onclick="openTestMsgModal(${dev.id})" class="px-3 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-xl text-xs font-semibold">
                  ✉️ Tes Pesan
                </button>
              `}
            </div>
            <button onclick="deleteDevice(${dev.id})" title="Hapus Device" class="p-1.5 text-slate-300 hover:text-red-600 rounded-lg hover:bg-red-50 transition-all">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            </button>
          </div>
        </div>
      `;
    }).join('');
  } catch (err) {
    console.error('Load devices error:', err);
  }
}

// Add Device Submit
document.getElementById('addDeviceForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const name = document.getElementById('newDevName').value.trim();
  const provider = document.getElementById('newDevProvider').value;
  const webhook_url = document.getElementById('newDevWebhook').value.trim();
  const fonnteToken = document.getElementById('newDevFonnteToken').value.trim();

  const provider_config = {};
  if (provider === 'fonnte') {
    provider_config.api_key = fonnteToken;
  }

  try {
    const res = await fetch(`${API_BASE}/devices`, {
      method: 'POST',
      headers: authHeaders(),
      body: JSON.stringify({ name, provider, provider_config, webhook_url })
    });

    const data = await res.json();
    if (res.ok && data.success) {
      closeModal('addDeviceModal');
      loadDevices();
    } else {
      alert(data.message || 'Gagal menambahkan device.');
    }
  } catch (err) {
    alert('Terjadi kesalahan saat menambahkan device.');
  }
});

async function startConnect(deviceId, provider, devName) {
  try {
    const res = await fetch(`${API_BASE}/devices/${deviceId}/connect`, {
      method: 'POST',
      headers: authHeaders()
    });

    const data = await res.json();
    if (!res.ok) {
      alert(data.message || 'Gagal memulai koneksi.');
      return;
    }

    if (provider === 'baileys') {
      currentQrDeviceId = deviceId;
      document.getElementById('qrModalDeviceName').textContent = `Scan QR Code - ${devName}`;
      openModal('qrModal');
      pollQrCode(deviceId);
    } else {
      alert(data.message || 'Fonnte terhubung!');
      loadDevices();
    }
  } catch (err) {
    alert('Gagal menghubungi server.');
  }
}

function pollQrCode(deviceId) {
  if (activeQrPollInterval) clearInterval(activeQrPollInterval);

  const container = document.getElementById('qrContainer');
  const statusText = document.getElementById('qrStatusText');

  const check = async () => {
    try {
      const res = await fetch(`${API_BASE}/devices/${deviceId}/qr`, { headers: authHeaders() });
      const data = await res.json();

      if (data.status === 'connected') {
        container.innerHTML = `
          <div class="text-emerald-600 flex flex-col items-center">
            <svg class="w-16 h-16 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="text-xs font-bold">Berhasil Terhubung!</span>
          </div>
        `;
        statusText.textContent = 'WhatsApp Aktif';
        clearInterval(activeQrPollInterval);
        setTimeout(() => {
          closeQrModal();
          loadDevices();
        }, 1500);
      } else if (data.qrCode) {
        container.innerHTML = `<img src="${data.qrCode}" alt="WhatsApp QR Code" class="w-56 h-56 rounded-xl">`;
        statusText.textContent = 'Arahkan kamera WhatsApp Anda ke QR Code ini.';
      }
    } catch (e) {}
  };

  check();
  activeQrPollInterval = setInterval(check, 2000);
}

function closeQrModal() {
  if (activeQrPollInterval) clearInterval(activeQrPollInterval);
  activeQrPollInterval = null;
  closeModal('qrModal');
}

async function disconnectDevice(deviceId) {
  if (!confirm('Yakin ingin memutuskan koneksi device ini?')) return;

  try {
    const res = await fetch(`${API_BASE}/devices/${deviceId}/disconnect`, {
      method: 'POST',
      headers: authHeaders()
    });
    const data = await res.json();
    if (res.ok) {
      loadDevices();
    } else {
      alert(data.message || 'Gagal memutuskan device.');
    }
  } catch (err) {
    alert('Gagal menghubungi server.');
  }
}

async function deleteDevice(deviceId) {
  if (!confirm('Yakin ingin menghapus device ini beserta seluruh riwayatnya?')) return;

  try {
    const res = await fetch(`${API_BASE}/devices/${deviceId}`, {
      method: 'DELETE',
      headers: authHeaders()
    });
    const data = await res.json();
    if (res.ok) {
      loadDevices();
    } else {
      alert(data.message || 'Gagal menghapus device.');
    }
  } catch (err) {
    alert('Gagal menghubungi server.');
  }
}

function openTestMsgModal(deviceId) {
  document.getElementById('testDeviceId').value = deviceId;
  openModal('testMsgModal');
}

document.getElementById('testMsgForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const deviceId = document.getElementById('testDeviceId').value;
  const to = document.getElementById('testTo').value.trim();
  const message = document.getElementById('testMsg').value.trim();

  try {
    const res = await fetch(`${API_BASE}/devices/${deviceId}/send-test`, {
      method: 'POST',
      headers: authHeaders(),
      body: JSON.stringify({ to, message })
    });
    const data = await res.json();
    if (res.ok && data.success) {
      alert('✅ Pesan tes berhasil dikirim!');
      closeModal('testMsgModal');
    } else {
      alert(data.message || 'Gagal mengirim pesan tes.');
    }
  } catch (err) {
    alert('Gagal menghubungi server.');
  }
});

document.addEventListener('DOMContentLoaded', () => {
  if (window.location.pathname.includes('dashboard')) {
    loadDevices();
  }
});
