import express from 'express';
import { query } from '../config/database.js';
import { authMiddleware } from '../middleware/auth.js';
import {
  createBroadcastCampaign,
  dispatchBroadcastCampaign,
  getBroadcastMetrics,
} from '../services/broadcastService.js';

const router = express.Router();

router.use(authMiddleware);

// GET /api/broadcasts/metrics
router.get('/metrics', async (req, res) => {
  try {
    const metrics = await getBroadcastMetrics(req.user.id);
    return res.json({ success: true, metrics });
  } catch (error) {
    return res.status(500).json({ success: false, message: error.message });
  }
});

// GET /api/broadcasts - List all campaigns
router.get('/', async (req, res) => {
  try {
    const result = await query(
      `SELECT b.*, d.name AS device_name, d.provider AS device_provider
       FROM broadcasts b
       LEFT JOIN wa_devices d ON b.device_id = d.id
       WHERE b.user_id = $1
       ORDER BY b.id DESC`,
      [req.user.id]
    );

    return res.json({ success: true, broadcasts: result.rows });
  } catch (error) {
    console.error('Error fetching broadcasts:', error);
    return res.status(500).json({ success: false, message: 'Gagal mengambil daftar broadcast.' });
  }
});

// POST /api/broadcasts - Create campaign
router.post('/', async (req, res) => {
  try {
    const {
      device_id,
      title,
      message,
      media_url,
      scheduled_at,
      delay_min_ms,
      delay_max_ms,
      recipients,
      auto_dispatch = true,
    } = req.body;

    // Verify device ownership
    const devCheck = await query('SELECT id FROM wa_devices WHERE id = $1 AND user_id = $2', [
      device_id,
      req.user.id,
    ]);
    if (devCheck.rows.length === 0) {
      return res.status(400).json({ success: false, message: 'Device tidak valid atau bukan milik Anda.' });
    }

    const broadcast = await createBroadcastCampaign({
      userId: req.user.id,
      deviceId: device_id,
      title,
      message,
      mediaUrl: media_url,
      scheduledAt: scheduled_at,
      delayMinMs: delay_min_ms || 1500,
      delayMaxMs: delay_max_ms || 3500,
      recipients,
    });

    // If auto_dispatch and not scheduled in the future, dispatch to BullMQ immediately
    if (auto_dispatch && broadcast.status === 'pending') {
      await dispatchBroadcastCampaign(broadcast.id);
    }

    return res.status(201).json({
      success: true,
      message: 'Kampanye broadcast berhasil dibuat dan dimasukkan ke antrian Redis.',
      broadcast,
    });
  } catch (error) {
    console.error('Error creating broadcast:', error);
    return res.status(500).json({ success: false, message: error.message });
  }
});

// GET /api/broadcasts/:id - Detail campaign & recipients
router.get('/:id', async (req, res) => {
  try {
    const bRes = await query(
      `SELECT b.*, d.name AS device_name, d.provider AS device_provider
       FROM broadcasts b
       LEFT JOIN wa_devices d ON b.device_id = d.id
       WHERE b.id = $1 AND b.user_id = $2`,
      [req.params.id, req.user.id]
    );

    if (bRes.rows.length === 0) {
      return res.status(404).json({ success: false, message: 'Broadcast tidak ditemukan.' });
    }

    const recipientsRes = await query(
      'SELECT * FROM broadcast_recipients WHERE broadcast_id = $1 ORDER BY id ASC',
      [req.params.id]
    );

    return res.json({
      success: true,
      broadcast: bRes.rows[0],
      recipients: recipientsRes.rows,
    });
  } catch (error) {
    return res.status(500).json({ success: false, message: error.message });
  }
});

// POST /api/broadcasts/:id/dispatch - Manually trigger / retry dispatch
router.post('/:id/dispatch', async (req, res) => {
  try {
    const bRes = await query('SELECT id FROM broadcasts WHERE id = $1 AND user_id = $2', [
      req.params.id,
      req.user.id,
    ]);

    if (bRes.rows.length === 0) {
      return res.status(404).json({ success: false, message: 'Broadcast tidak ditemukan.' });
    }

    const dispatchRes = await dispatchBroadcastCampaign(req.params.id);
    return res.json({ success: true, message: 'Broadcast berhasil didorong ke antrian Redis.', data: dispatchRes });
  } catch (error) {
    return res.status(500).json({ success: false, message: error.message });
  }
});

// DELETE /api/broadcasts/:id - Delete broadcast
router.delete('/:id', async (req, res) => {
  try {
    const bRes = await query('SELECT id FROM broadcasts WHERE id = $1 AND user_id = $2', [
      req.params.id,
      req.user.id,
    ]);

    if (bRes.rows.length === 0) {
      return res.status(404).json({ success: false, message: 'Broadcast tidak ditemukan.' });
    }

    await query('DELETE FROM broadcasts WHERE id = $1', [req.params.id]);

    return res.json({ success: true, message: 'Broadcast berhasil dihapus.' });
  } catch (error) {
    return res.status(500).json({ success: false, message: error.message });
  }
});

export default router;
