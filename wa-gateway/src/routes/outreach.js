import express from 'express';
import { query } from '../config/database.js';
import { authMiddleware } from '../middleware/auth.js';
import { providerManager } from '../providers/manager.js';

const router = express.Router();

router.use(authMiddleware);

// GET /api/outreach/stats - Stats on incoming messages
router.get('/stats', async (req, res) => {
  try {
    const statsRes = await query(
      `SELECT
        COUNT(m.id) AS total_messages,
        COUNT(CASE WHEN m.is_read = FALSE THEN 1 END) AS unread_messages,
        COUNT(DISTINCT m.from_number) AS unique_contacts
       FROM outreach_messages m
       JOIN wa_devices d ON m.device_id = d.id
       WHERE d.user_id = $1`,
      [req.user.id]
    );

    return res.json({ success: true, stats: statsRes.rows[0] });
  } catch (error) {
    return res.status(500).json({ success: false, message: error.message });
  }
});

// GET /api/outreach - List incoming messages
router.get('/', async (req, res) => {
  try {
    const { device_id, is_read, search, limit = 50, offset = 0 } = req.query;

    let sql = `
      SELECT m.*, d.name AS device_name, d.provider AS device_provider
      FROM outreach_messages m
      JOIN wa_devices d ON m.device_id = d.id
      WHERE d.user_id = $1
    `;
    const params = [req.user.id];

    if (device_id) {
      params.push(device_id);
      sql += ` AND m.device_id = $${params.length}`;
    }

    if (is_read !== undefined && is_read !== '') {
      params.push(is_read === 'true');
      sql += ` AND m.is_read = $${params.length}`;
    }

    if (search) {
      params.push(`%${search}%`);
      sql += ` AND (m.from_number ILIKE $${params.length} OR m.from_name ILIKE $${params.length} OR m.message ILIKE $${params.length})`;
    }

    sql += ` ORDER BY m.received_at DESC LIMIT $${params.length + 1} OFFSET $${params.length + 2}`;
    params.push(parseInt(limit, 10), parseInt(offset, 10));

    const result = await query(sql, params);

    return res.json({ success: true, messages: result.rows });
  } catch (error) {
    console.error('Error fetching outreach messages:', error);
    return res.status(500).json({ success: false, message: 'Gagal mengambil pesan outreach.' });
  }
});

// PATCH /api/outreach/:id/read - Mark as read
router.patch('/:id/read', async (req, res) => {
  try {
    const check = await query(
      `SELECT m.id FROM outreach_messages m
       JOIN wa_devices d ON m.device_id = d.id
       WHERE m.id = $1 AND d.user_id = $2`,
      [req.params.id, req.user.id]
    );

    if (check.rows.length === 0) {
      return res.status(404).json({ success: false, message: 'Pesan tidak ditemukan.' });
    }

    await query('UPDATE outreach_messages SET is_read = TRUE WHERE id = $1', [req.params.id]);

    return res.json({ success: true, message: 'Pesan ditandai telah dibaca.' });
  } catch (error) {
    return res.status(500).json({ success: false, message: error.message });
  }
});

// POST /api/outreach/:id/reply - Reply directly to user
router.post('/:id/reply', async (req, res) => {
  try {
    const { reply_message } = req.body;
    if (!reply_message) {
      return res.status(400).json({ success: false, message: 'Pesan balasan wajib diisi.' });
    }

    const check = await query(
      `SELECT m.*, d.id AS device_id FROM outreach_messages m
       JOIN wa_devices d ON m.device_id = d.id
       WHERE m.id = $1 AND d.user_id = $2`,
      [req.params.id, req.user.id]
    );

    if (check.rows.length === 0) {
      return res.status(404).json({ success: false, message: 'Pesan tidak ditemukan.' });
    }

    const msg = check.rows[0];
    const provider = await providerManager.get(msg.device_id);
    const sendResult = await provider.sendMessage(msg.from_number, reply_message);

    // Auto mark as read
    await query('UPDATE outreach_messages SET is_read = TRUE WHERE id = $1', [req.params.id]);

    return res.json({
      success: true,
      message: 'Balasan berhasil dikirim.',
      data: sendResult,
    });
  } catch (error) {
    return res.status(500).json({ success: false, message: error.message });
  }
});

export default router;
