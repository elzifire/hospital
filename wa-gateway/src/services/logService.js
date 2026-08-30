import { query } from '../config/database.js';

/**
 * Write an entry to the wa_logs table
 */
export async function createLog({
  userId = null,
  deviceId = null,
  broadcastId = null,
  type = 'system',      // 'broadcast' | 'direct' | 'outreach' | 'device' | 'system' | 'auth'
  level = 'info',        // 'info' | 'warn' | 'error' | 'success'
  action,               // e.g. 'MESSAGE_SENT', 'MESSAGE_FAILED', 'BROADCAST_SCHEDULED'
  recipient = null,
  message = '',
  details = {},
  ipAddress = null,
}) {
  try {
    const res = await query(
      `INSERT INTO wa_logs (
        user_id, device_id, broadcast_id, type, level, action,
        recipient, message, details, ip_address, created_at
      ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, NOW())
      RETURNING *`,
      [
        userId,
        deviceId,
        broadcastId,
        type,
        level,
        action,
        recipient,
        message,
        JSON.stringify(details || {}),
        ipAddress,
      ]
    );
    return res.rows[0];
  } catch (err) {
    console.error('[LogService] Failed to write log:', err.message);
  }
}

/**
 * Get daily message count for a specific device today (00:00 - 23:59)
 */
export async function getTodaySentCount(deviceId) {
  try {
    const res = await query(
      `SELECT COUNT(*) AS total
       FROM wa_logs
       WHERE device_id = $1
         AND action = 'MESSAGE_SENT'
         AND created_at >= CURRENT_DATE
         AND created_at < CURRENT_DATE + INTERVAL '1 day'`,
      [deviceId]
    );
    return parseInt(res.rows[0]?.total || '0', 10);
  } catch (err) {
    console.error('[LogService] Error getting today count:', err.message);
    return 0;
  }
}
