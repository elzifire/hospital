import express from 'express';
import { query } from '../config/database.js';
import { authMiddleware } from '../middleware/auth.js';
import { getTodaySentCount } from '../services/logService.js';
import { DAILY_MAX_MESSAGES } from '../utils/humanScheduler.js';

const router = express.Router();

router.use(authMiddleware);

// GET /api/logs/quota/:deviceId - Get device daily quota usage
router.get('/quota/:deviceId', async (req, res) => {
  try {
    const deviceId = parseInt(req.params.deviceId, 10);
    const sentToday = await getTodaySentCount(deviceId);
    const remaining = Math.max(0, DAILY_MAX_MESSAGES - sentToday);

    return res.json({
      success: true,
      deviceId,
      dailyLimit: DAILY_MAX_MESSAGES,
      sentToday,
      remainingQuota: remaining,
      percentageUsed: Math.round((sentToday / DAILY_MAX_MESSAGES) * 100),
    });
  } catch (error) {
    return res.status(500).json({ success: false, message: error.message });
  }
});

// GET /api/logs - List logs with filters and pagination
router.get('/', async (req, res) => {
  try {
    const { device_id, type, level, action, search, limit = 50, offset = 0 } = req.query;

    let sql = `
      SELECT l.*, d.name AS device_name, d.provider AS device_provider
      FROM wa_logs l
      LEFT JOIN wa_devices d ON l.device_id = d.id
      WHERE (l.user_id = $1 OR d.user_id = $1)
    `;
    const params = [req.user.id];

    if (device_id) {
      params.push(device_id);
      sql += ` AND l.device_id = $${params.length}`;
    }

    if (type) {
      params.push(type);
      sql += ` AND l.type = $${params.length}`;
    }

    if (level) {
      params.push(level);
      sql += ` AND l.level = $${params.length}`;
    }

    if (action) {
      params.push(action);
      sql += ` AND l.action = $${params.length}`;
    }

    if (search) {
      params.push(`%${search}%`);
      sql += ` AND (l.message ILIKE $${params.length} OR l.recipient ILIKE $${params.length} OR l.action ILIKE $${params.length})`;
    }

    sql += ` ORDER BY l.created_at DESC LIMIT $${params.length + 1} OFFSET $${params.length + 2}`;
    params.push(parseInt(limit, 10), parseInt(offset, 10));

    const result = await query(sql, params);

    return res.json({
      success: true,
      logs: result.rows,
    });
  } catch (error) {
    console.error('Error fetching logs:', error);
    return res.status(500).json({ success: false, message: 'Gagal mengambil data logs.' });
  }
});

export default router;
