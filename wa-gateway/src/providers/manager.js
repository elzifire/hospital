import { BaileysProvider } from './baileys.js';
import { FonnteProvider } from './fonnte.js';
import { query } from '../config/database.js';
import { handleIncomingMessage } from '../services/outreachService.js';

class ProviderManager {
  constructor() {
    this.providers = new Map(); // Map<deviceId, ProviderInstance>
  }

  createProviderInstance(device) {
    let provider;
    switch (device.provider?.toLowerCase()) {
      case 'fonnte':
        provider = new FonnteProvider(device);
        break;
      case 'baileys':
      default:
        provider = new BaileysProvider(device);
        break;
    }

    // Attach Outreach listener
    provider.on('message.received', async (data) => {
      try {
        await handleIncomingMessage(data);
      } catch (err) {
        console.error(`[Outreach] Error handling incoming message from Device #${device.id}:`, err);
      }
    });

    return provider;
  }

  async get(deviceId) {
    const id = parseInt(deviceId, 10);
    if (this.providers.has(id)) {
      return this.providers.get(id);
    }

    // Load device from database if not in memory
    const res = await query('SELECT * FROM wa_devices WHERE id = $1', [id]);
    if (res.rows.length === 0) {
      throw new Error(`Device dengan ID ${deviceId} tidak ditemukan.`);
    }

    const device = res.rows[0];
    const provider = this.createProviderInstance(device);
    this.providers.set(id, provider);
    return provider;
  }

  async connectDevice(deviceId) {
    const provider = await this.get(deviceId);
    return await provider.connect();
  }

  async disconnectDevice(deviceId) {
    const provider = await this.get(deviceId);
    const res = await provider.disconnect();
    return res;
  }

  remove(deviceId) {
    const id = parseInt(deviceId, 10);
    if (this.providers.has(id)) {
      const provider = this.providers.get(id);
      provider.disconnect().catch(() => {});
      this.providers.delete(id);
    }
  }

  // Restore active devices on server startup
  async bootActiveDevices() {
    console.log('🔄 Initializing active WhatsApp devices from database...');
    try {
      const res = await query(
        "SELECT * FROM wa_devices WHERE status IN ('connected', 'connecting')"
      );

      for (const device of res.rows) {
        try {
          console.log(`📡 Restoring session for Device #${device.id} (${device.name}) [${device.provider}]`);
          const provider = this.createProviderInstance(device);
          this.providers.set(device.id, provider);
          await provider.connect().catch((err) => {
            console.warn(`⚠️ Warning: Could not auto-reconnect device #${device.id}:`, err.message);
          });
        } catch (devErr) {
          console.error(`❌ Error restoring device #${device.id}:`, devErr.message);
        }
      }
      console.log(`✅ Loaded ${res.rows.length} device sessions.`);
    } catch (err) {
      console.error('❌ Failed to boot active devices:', err);
    }
  }
}

export const providerManager = new ProviderManager();
