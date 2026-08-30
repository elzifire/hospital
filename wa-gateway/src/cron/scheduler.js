import cron from 'node-cron';
import { query } from '../config/database.js';
import { dispatchBroadcastCampaign } from '../services/broadcastService.js';
import { addBroadcastJob } from '../queue/broadcastQueue.js';

export function initCronJobs() {
  console.log('⏰ Initializing Cron Schedulers...');

  // 1. Check scheduled broadcasts every 1 minute
  cron.schedule('* * * * *', async () => {
    try {
      const res = await query(
        "SELECT id FROM broadcasts WHERE status = 'scheduled' AND scheduled_at <= NOW()"
      );

      if (res.rows.length > 0) {
        console.log(`[Cron] Found ${res.rows.length} scheduled broadcasts ready to dispatch.`);
        for (const row of res.rows) {
          await dispatchBroadcastCampaign(row.id);
        }
      }
    } catch (err) {
      console.error('[Cron] Error checking scheduled broadcasts:', err.message);
    }
  });

  // 2. Auto-retry failed recipients every 5 minutes (max retry count < 3)
  cron.schedule('*/5 * * * *', async () => {
    try {
      const res = await query(`
        SELECT r.id AS recipient_id, r.phone_number, r.name, r.custom_data,
               b.id AS broadcast_id, b.device_id, b.message AS template_message,
               b.media_url, b.delay_min_ms, b.delay_max_ms
        FROM broadcast_recipients r
        JOIN broadcasts b ON r.broadcast_id = b.id
        WHERE r.status = 'failed'
          AND r.retry_count < 3
          AND b.status IN ('processing', 'pending')
        LIMIT 50
      `);

      if (res.rows.length > 0) {
        console.log(`[Cron] Retrying ${res.rows.length} failed broadcast recipients...`);
        for (const row of res.rows) {
          await addBroadcastJob({
            broadcastId: row.broadcast_id,
            recipientId: row.recipient_id,
            deviceId: row.device_id,
            phoneNumber: row.phone_number,
            name: row.name,
            templateMessage: row.template_message,
            mediaUrl: row.media_url,
            customData: row.custom_data,
            delayMinMs: row.delay_min_ms,
            delayMaxMs: row.delay_max_ms,
          });
        }
      }
    } catch (err) {
      console.error('[Cron] Error retrying failed recipients:', err.message);
    }
  });

  console.log('✅ Cron Jobs scheduled.');
}
