import { query } from '../config/database.js';
import { addBroadcastJob } from '../queue/broadcastQueue.js';
import { generateHumanSchedule, DAILY_MAX_MESSAGES } from '../utils/humanScheduler.js';
import { createLog, getTodaySentCount } from './logService.js';

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

  // 1. Check daily limit (Anti-ban protection: Max 100 messages / day)
  const todaySent = await getTodaySentCount(deviceId);
  const remainingQuotaToday = Math.max(0, DAILY_MAX_MESSAGES - todaySent);

  if (recipients.length > DAILY_MAX_MESSAGES) {
    throw new Error(
      `Jumlah penerima (${recipients.length}) melebihi batas aman harian (${DAILY_MAX_MESSAGES} pesan/hari per device) untuk mencegah pemblokiran oleh Meta.`
    );
  }

  if (remainingQuotaToday <= 0) {
    throw new Error(
      `Kuota harian device ini sudah habis (${todaySent}/${DAILY_MAX_MESSAGES} pesan terkirim hari ini). Silakan jadwalkan untuk besok.`
    );
  }

  // 2. Generate randomized human-like schedule for all recipients
  const baseStartDate = scheduledAt ? new Date(scheduledAt) : new Date();
  const humanSchedules = generateHumanSchedule(recipients.length, baseStartDate);

  const initialStatus = 'processing'; // Queued with delays

  // 3. Insert header
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
      humanSchedules[0]?.scheduledAt || baseStartDate,
      delayMinMs,
      delayMaxMs,
      recipients.length,
    ]
  );

  const broadcast = headerRes.rows[0];

  // 4. Batch insert recipients with their unique human-like scheduled_at
  const insertedRecipients = [];
  for (let i = 0; i < recipients.length; i++) {
    const r = recipients[i];
    const sched = humanSchedules[i];

    const rRes = await query(
      `INSERT INTO broadcast_recipients (
        broadcast_id, phone_number, name, custom_data, status, sent_at
      ) VALUES ($1, $2, $3, $4, 'pending', NULL)
      RETURNING id, phone_number, name, custom_data`,
      [
        broadcast.id,
        r.phone_number || r.phone || r.target,
        r.name || '',
        JSON.stringify(r.custom_data || {}),
      ]
    );

    const inserted = rRes.rows[0];
    insertedRecipients.push({
      recipientId: inserted.id,
      phoneNumber: inserted.phone_number,
      name: inserted.name,
      customData: inserted.custom_data,
      delayMs: sched?.delayMs || 5000,
      scheduledAt: sched?.scheduledAt || new Date(),
    });
  }

  // 5. Push jobs to Redis BullMQ with calculated human delay
  for (const item of insertedRecipients) {
    await addBroadcastJob(
      {
        broadcastId: broadcast.id,
        recipientId: item.recipientId,
        deviceId: broadcast.device_id,
        userId,
        phoneNumber: item.phoneNumber,
        name: item.name,
        templateMessage: broadcast.message,
        mediaUrl: broadcast.media_url,
        customData: item.customData,
        delayMinMs: broadcast.delay_min_ms,
        delayMaxMs: broadcast.delay_max_ms,
      },
      {
        delay: item.delayMs, // BullMQ native delay in ms!
        jobId: `rec_${item.recipientId}_${Date.now()}`,
      }
    );
  }

  // 6. Record activity log
  await createLog({
    userId,
    deviceId,
    broadcastId: broadcast.id,
    type: 'broadcast',
    level: 'info',
    action: 'BROADCAST_SCHEDULED',
    message: `Broadcast "${title}" dijadwalkan dengan algoritma humanis untuk ${recipients.length} penerima (Rentang waktu: ${humanSchedules[0]?.scheduledAt?.toLocaleTimeString()} s/d ${humanSchedules[humanSchedules.length - 1]?.scheduledAt?.toLocaleTimeString()}).`,
    details: {
      totalRecipients: recipients.length,
      firstMessageAt: humanSchedules[0]?.scheduledAt,
      lastMessageAt: humanSchedules[humanSchedules.length - 1]?.scheduledAt,
    },
  });

  console.log(`🚀 Scheduled ${recipients.length} messages with human-like distribution for Broadcast #${broadcast.id}`);

  return {
    ...broadcast,
    scheduleDetails: {
      firstMessageAt: humanSchedules[0]?.scheduledAt,
      lastMessageAt: humanSchedules[humanSchedules.length - 1]?.scheduledAt,
      totalScheduled: recipients.length,
    },
  };
}

export async function dispatchBroadcastCampaign(broadcastId) {
  const bRes = await query('SELECT * FROM broadcasts WHERE id = $1', [broadcastId]);
  if (bRes.rows.length === 0) {
    throw new Error(`Broadcast #${broadcastId} tidak ditemukan.`);
  }

  const broadcast = bRes.rows[0];

  const rRes = await query(
    "SELECT * FROM broadcast_recipients WHERE broadcast_id = $1 AND status = 'pending'",
    [broadcastId]
  );

  if (rRes.rows.length === 0) {
    return { success: true, message: 'Tidak ada penerima pending.' };
  }

  // Generate randomized human-like schedules for remaining pending recipients
  const humanSchedules = generateHumanSchedule(rRes.rows.length, new Date());

  await query("UPDATE broadcasts SET status = 'processing', updated_at = NOW() WHERE id = $1", [
    broadcastId,
  ]);

  for (let i = 0; i < rRes.rows.length; i++) {
    const r = rRes.rows[i];
    const sched = humanSchedules[i];

    await addBroadcastJob(
      {
        broadcastId: broadcast.id,
        recipientId: r.id,
        deviceId: broadcast.device_id,
        userId: broadcast.user_id,
        phoneNumber: r.phone_number,
        name: r.name,
        templateMessage: broadcast.message,
        mediaUrl: broadcast.media_url,
        customData: r.custom_data,
        delayMinMs: broadcast.delay_min_ms,
        delayMaxMs: broadcast.delay_max_ms,
      },
      {
        delay: sched?.delayMs || 5000,
        jobId: `rec_${r.id}_${Date.now()}`,
      }
    );
  }

  return {
    success: true,
    broadcastId: broadcast.id,
    totalQueued: rRes.rows.length,
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

