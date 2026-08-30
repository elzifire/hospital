import express from 'express';
import { query } from '../config/database.js';
import { authMiddleware } from '../middleware/auth.js';
import { providerManager } from '../providers/manager.js';
import { createLog } from '../services/logService.js';

const router = express.Router();

// Apply auth middleware to all device routes
router.use(authMiddleware);

// GET /api/devices - List all devices for current user
router.get('/', async (req, res) => {
  try {
    const result = await query(
      'SELECT * FROM wa_devices WHERE user_id = $1 ORDER BY id DESC',
      [req.user.id]
    );

    // Merge in-memory live status
    const devices = result.rows.map((dev) => {
      const live = providerManager.providers.get(dev.id);
      return {
        ...dev,
        live_status: live ? live.status : dev.status,
        has_qr: live ? !!live.qrCode : false,
      };
    });

    return res.json({ success: true, devices });
  } catch (error) {
    console.error('Error fetching devices:', error);
    return res.status(500).json({ success: false, message: 'Gagal mengambil daftar device.' });
  }
});

// POST /api/devices - Create new device
router.post('/', async (req, res) => {
  try {
    const { name, provider = 'baileys', provider_config = {}, webhook_url = '' } = req.body;

    if (!name) {
      return res.status(400).json({ success: false, message: 'Nama device wajib diisi.' });
    }

    const validProviders = ['baileys', 'fonnte'];
    if (!validProviders.includes(provider.toLowerCase())) {
      return res.status(400).json({
        success: false,
        message: `Provider tidak valid. Pilihan: ${validProviders.join(', ')}`,
      });
    }

    const result = await query(
      `INSERT INTO wa_devices (user_id, name, provider, provider_config, webhook_url, status)
       VALUES ($1, $2, $3, $4, $5, 'disconnected')
       RETURNING *`,
      [req.user.id, name, provider.toLowerCase(), JSON.stringify(provider_config), webhook_url || null]
    );

    const device = result.rows[0];

    return res.status(201).json({
      success: true,
      message: 'Device berhasil ditambahkan.',
      device,
    });
  } catch (error) {
    console.error('Error creating device:', error);
    return res.status(500).json({ success: false, message: 'Gagal menambahkan device.' });
  }
});

// GET /api/devices/:id - Detail device
router.get('/:id', async (req, res) => {
  try {
    const result = await query(
      'SELECT * FROM wa_devices WHERE id = $1 AND user_id = $2',
      [req.params.id, req.user.id]
    );

    if (result.rows.length === 0) {
      return res.status(404).json({ success: false, message: 'Device tidak ditemukan.' });
    }

    const device = result.rows[0];
    const live = providerManager.providers.get(device.id);

    return res.json({
      success: true,
      device: {
        ...device,
        live_status: live ? live.status : device.status,
        has_qr: live ? !!live.qrCode : false,
      },
    });
  } catch (error) {
    return res.status(500).json({ success: false, message: 'Gagal mengambil data device.' });
  }
});

// GET /api/devices/:id/qr - Get QR code for Baileys
router.get('/:id/qr', async (req, res) => {
  try {
    const deviceId = parseInt(req.params.id, 10);
    const check = await query('SELECT * FROM wa_devices WHERE id = $1 AND user_id = $2', [
      deviceId,
      req.user.id,
    ]);

    if (check.rows.length === 0) {
      return res.status(404).json({ success: false, message: 'Device tidak ditemukan.' });
    }

    const provider = await providerManager.get(deviceId);
    const qr = provider.getQrCode();

    return res.json({
      success: true,
      deviceId,
      status: provider.status,
      qrCode: qr,
    });
  } catch (error) {
    return res.status(500).json({ success: false, message: error.message });
  }
});

// POST /api/devices/:id/connect - Start connection / QR generation
router.post('/:id/connect', async (req, res) => {
  try {
    const deviceId = parseInt(req.params.id, 10);
    const check = await query('SELECT * FROM wa_devices WHERE id = $1 AND user_id = $2', [
      deviceId,
      req.user.id,
    ]);

    if (check.rows.length === 0) {
      return res.status(404).json({ success: false, message: 'Device tidak ditemukan.' });
    }

    const connectRes = await providerManager.connectDevice(deviceId);

    return res.json({
      success: true,
      message: 'Inisialisasi koneksi dimulai. Silakan cek QR Code.',
      data: connectRes,
    });
  } catch (error) {
    console.error('Connect error:', error);
    return res.status(500).json({ success: false, message: error.message });
  }
});

// POST /api/devices/:id/disconnect - Disconnect & Logout
router.post('/:id/disconnect', async (req, res) => {
  try {
    const deviceId = parseInt(req.params.id, 10);
    const check = await query('SELECT * FROM wa_devices WHERE id = $1 AND user_id = $2', [
      deviceId,
      req.user.id,
    ]);

    if (check.rows.length === 0) {
      return res.status(404).json({ success: false, message: 'Device tidak ditemukan.' });
    }

    await providerManager.disconnectDevice(deviceId);

    return res.json({
      success: true,
      message: 'Device berhasil diputus/logout.',
    });
  } catch (error) {
    return res.status(500).json({ success: false, message: error.message });
  }
});

// DELETE /api/devices/:id - Delete device
router.delete('/:id', async (req, res) => {
  try {
    const deviceId = parseInt(req.params.id, 10);
    const check = await query('SELECT * FROM wa_devices WHERE id = $1 AND user_id = $2', [
      deviceId,
      req.user.id,
    ]);

    if (check.rows.length === 0) {
      return res.status(404).json({ success: false, message: 'Device tidak ditemukan.' });
    }

    providerManager.remove(deviceId);
    await query('DELETE FROM wa_devices WHERE id = $1', [deviceId]);

    return res.json({
      success: true,
      message: 'Device berhasil dihapus.',
    });
  } catch (error) {
    return res.status(500).json({ success: false, message: 'Gagal menghapus device.' });
  }
});

// POST /api/devices/:id/send-test - Test message to a specific number
router.post('/:id/send-test', async (req, res) => {
  try {
    const { to, message } = req.body;
    if (!to || !message) {
      return res.status(400).json({ success: false, message: 'Nomor tujuan dan pesan wajib diisi.' });
    }

    const deviceId = parseInt(req.params.id, 10);
    const provider = await providerManager.get(deviceId);
    const sendResult = await provider.sendMessage(to, message);

    await createLog({
      userId: req.user.id,
      deviceId,
      type: 'direct',
      level: 'success',
      action: 'MESSAGE_SENT',
      recipient: to,
      message: message.substring(0, 200),
      details: {
        isTest: true,
        messageId: sendResult.messageId,
      },
    });

    return res.json({
      success: true,
      message: 'Pesan tes berhasil dikirim langsung (NOW).',
      data: sendResult,
    });
  } catch (error) {
    return res.status(500).json({ success: false, message: error.message });
  }
});

export default router;
