import fetch from 'node-fetch';
import { query } from '../config/database.js';

export async function handleIncomingMessage({
  deviceId,
  fromNumber,
  fromName,
  message,
  messageType = 'text',
  mediaUrl = null,
  rawData = {},
  receivedAt = new Date(),
}) {
  console.log(`[Outreach] 📩 New incoming message on Device #${deviceId} from ${fromNumber} (${fromName}): "${message}"`);

  // 1. Save to PostgreSQL outreach_messages table
  const insertRes = await query(
    `INSERT INTO outreach_messages (
      device_id, from_number, from_name, message,
      message_type, media_url, raw_data, is_read, received_at
    ) VALUES ($1, $2, $3, $4, $5, $6, $7, false, $8)
    RETURNING *`,
    [
      deviceId,
      fromNumber,
      fromName,
      message,
      messageType,
      mediaUrl,
      JSON.stringify(rawData),
      receivedAt,
    ]
  );

  const savedMessage = insertRes.rows[0];

  // 2. Dispatch webhook to Laravel if configured
  await forwardWebhookToLaravel(deviceId, savedMessage);

  return savedMessage;
}

export async function forwardWebhookToLaravel(deviceId, messageData) {
  try {
    // Get device webhook URL or fallback to global config
    const devRes = await query('SELECT webhook_url FROM wa_devices WHERE id = $1', [deviceId]);
    const webhookUrl =
      devRes.rows[0]?.webhook_url ||
      process.env.DEFAULT_LARAVEL_WEBHOOK ||
      null;

    if (!webhookUrl) {
      return;
    }

    console.log(`[Webhook] 📤 Forwarding outreach message #${messageData.id} to Laravel: ${webhookUrl}`);

    const payload = {
      event: 'message.received',
      device_id: deviceId,
      message_id: messageData.id,
      from: messageData.from_number,
      sender_name: messageData.from_name,
      message: messageData.message,
      message_type: messageData.message_type,
      media_url: messageData.media_url,
      received_at: messageData.received_at,
    };

    const response = await fetch(webhookUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'User-Agent': 'WA-Gateway-Outreach-Forwarder/1.0',
      },
      body: JSON.stringify(payload),
      timeout: 5000,
    });

    const responseText = await response.text();

    await query(
      'UPDATE outreach_messages SET webhook_sent = $1, webhook_response = $2 WHERE id = $3',
      [response.ok, responseText.substring(0, 500), messageData.id]
    );

    console.log(`[Webhook] ✅ Laravel Webhook Response (${response.status}):`, responseText.substring(0, 100));
  } catch (err) {
    console.error(`[Webhook] ❌ Failed to forward to Laravel:`, err.message);
    await query(
      'UPDATE outreach_messages SET webhook_sent = false, webhook_response = $1 WHERE id = $2',
      [err.message.substring(0, 500), messageData.id]
    );
  }
}
