import { query } from '../config/database.js';
import { addBroadcastJobBatch } from '../queue/broadcastQueue.js';

export async function createBroadcastCampaign({
  userId,
  deviceId,
  title,
  message,
  mediaUrl = null,
  scheduledAt = null,
  delayMinMs = 1500,
  delayMaxMs = 3500,
  recipients = [],
}) {
  if (!deviceId || !title || !message) {
    throw new Error('Device ID, Title, dan Message wajib diisi.');
  }

  if (!recipients || recipients.length === 0) {
    throw new Error('Daftar penerima (recipients) tidak boleh kosong.');
  }

  const initialStatus = scheduledAt && new Date(scheduledAt) > new Date() ? 'scheduled' : 'pending';

  // 1. Insert header
  const headerRes = await query(
    `INSERT INTO broadcasts (
      user_id, device_id, title, message, media_url,
      status, scheduled_at, delay_min_ms, delay_max_ms, total_recipients
    ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
    RETURNING *`,
    [
      userId,
      deviceId,
      title,
      message,
      mediaUrl,
      initialStatus,
      scheduledAt || null,
      delayMinMs,
      delayMaxMs,
      recipients.length,
    ]
  );

  const broadcast = headerRes.rows[0];

  // 2. Batch insert recipients
  for (const r of recipients) {
    await query(
      `INSERT INTO broadcast_recipients (
        broadcast_id, phone_number, name, custom_data, status
      ) VALUES ($1, $2, $3, $4, 'pending')`,
      [
        broadcast.id,
        r.phone_number || r.phone || r.target,
        r.name || '',
        JSON.stringify(r.custom_data || {}),
      ]
    );
  }

  // If not scheduled, dispatch right away if requested
  return broadcast;
}

export async function dispatchBroadcastCampaign(broadcastId) {
  // 1. Fetch broadcast details
  const bRes = await query('SELECT * FROM broadcasts WHERE id = $1', [broadcastId]);
  if (bRes.rows.length === 0) {
    throw new Error(`Broadcast #${broadcastId} tidak ditemukan.`);
  }

  const broadcast = bRes.rows[0];

  // 2. Fetch pending recipients
  const rRes = await query(
    "SELECT * FROM broadcast_recipients WHERE broadcast_id = $1 AND status = 'pending'",
    [broadcastId]
  );

  if (rRes.rows.length === 0) {
    console.log(`Broadcast #${broadcastId} has no pending recipients.`);
    return { success: true, message: 'Tidak ada penerima pending.' };
  }

  // 3. Mark broadcast as processing
  await query("UPDATE broadcasts SET status = 'processing', updated_at = NOW() WHERE id = $1", [
    broadcastId,
  ]);

  // 4. Prepare batch jobs for BullMQ
  const jobsData = rRes.rows.map((r) => ({
    broadcastId: broadcast.id,
    recipientId: r.id,
    deviceId: broadcast.device_id,
    phoneNumber: r.phone_number,
    name: r.name,
    templateMessage: broadcast.message,
    mediaUrl: broadcast.media_url,
    customData: r.custom_data,
    delayMinMs: broadcast.delay_min_ms,
    delayMaxMs: broadcast.delay_max_ms,
  }));

  // 5. Add bulk to Redis BullMQ
  await addBroadcastJobBatch(jobsData);
  console.log(`🚀 Dispatched ${jobsData.length} messages to Redis queue for Broadcast #${broadcastId}`);

  return {
    success: true,
    broadcastId: broadcast.id,
    totalQueued: jobsData.length,
  };
}

export async function getBroadcastMetrics(userId) {
  const res = await query(
    `SELECT
      COUNT(b.id) AS total_campaigns,
      COALESCE(SUM(b.total_recipients), 0) AS total_messages,
      COALESCE(SUM(b.sent_count), 0) AS total_sent,
      COALESCE(SUM(b.failed_count), 0) AS total_failed
    FROM broadcasts b
    WHERE b.user_id = $1`,
    [userId]
  );
  return res.rows[0];
}
