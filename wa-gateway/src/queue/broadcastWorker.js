import { Worker } from 'bullmq';
import { getRedisConfig } from '../config/redis.js';
import { query } from '../config/database.js';
import { providerManager } from '../providers/manager.js';
import { BROADCAST_QUEUE_NAME } from './broadcastQueue.js';

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

function compileMessageTemplate(template, data) {
  let msg = template;
  msg = msg.replace(/{name}/gi, data.name || '');
  msg = msg.replace(/{phone}/gi, data.phone_number || '');

  // Replace custom variables like {var1}, {clinic_date}, etc.
  if (data.custom_data && typeof data.custom_data === 'object') {
    for (const [key, val] of Object.entries(data.custom_data)) {
      const regex = new RegExp(`{${key}}`, 'gi');
      msg = msg.replace(regex, val || '');
    }
  }
  return msg;
}

export function initBroadcastWorker() {
  const concurrency = parseInt(process.env.QUEUE_CONCURRENCY || '3', 10);

  const worker = new Worker(
    BROADCAST_QUEUE_NAME,
    async (job) => {
      const {
        broadcastId,
        recipientId,
        deviceId,
        phoneNumber,
        name,
        templateMessage,
        mediaUrl,
        customData,
        delayMinMs = 1500,
        delayMaxMs = 3500,
      } = job.data;

      console.log(`[Worker] Processing Recipient #${recipientId} (${phoneNumber}) for Broadcast #${broadcastId}`);

      // 1. Mark recipient as processing
      await query(
        "UPDATE broadcast_recipients SET status = 'processing', job_id = $1 WHERE id = $2",
        [job.id, recipientId]
      );

      // 2. Anti-ban random delay
      const randomDelay = Math.floor(Math.random() * (delayMaxMs - delayMinMs + 1)) + delayMinMs;
      await sleep(randomDelay);

      try {
        // 3. Get provider instance
        const provider = await providerManager.get(deviceId);

        // 4. Compile message
        const finalMessage = compileMessageTemplate(templateMessage, {
          name,
          phone_number: phoneNumber,
          custom_data: customData,
        });

        // 5. Send message via provider
        let sendResult;
        if (mediaUrl) {
          sendResult = await provider.sendImage(phoneNumber, mediaUrl, finalMessage);
        } else {
          sendResult = await provider.sendMessage(phoneNumber, finalMessage);
        }

        // 6. Update recipient status to 'sent'
        await query(
          "UPDATE broadcast_recipients SET status = 'sent', sent_at = NOW(), error_message = NULL WHERE id = $1",
          [recipientId]
        );

        // 7. Increment sent_count in broadcast header
        await query(
          'UPDATE broadcasts SET sent_count = sent_count + 1, updated_at = NOW() WHERE id = $1',
          [broadcastId]
        );

        console.log(`[Worker] ✅ Message successfully sent to ${phoneNumber}`);
        return { success: true, recipientId, sendResult };
      } catch (err) {
        console.error(`[Worker] ❌ Failed to send message to ${phoneNumber}:`, err.message);

        // Update recipient status to 'failed'
        await query(
          "UPDATE broadcast_recipients SET status = 'failed', error_message = $1, retry_count = retry_count + 1 WHERE id = $2",
          [err.message, recipientId]
        );

        // Increment failed_count in broadcast header
        await query(
          'UPDATE broadcasts SET failed_count = failed_count + 1, updated_at = NOW() WHERE id = $1',
          [broadcastId]
        );

        throw err;
      } finally {
        // 8. Check if all recipients for this broadcast have completed
        await checkAndUpdateBroadcastCompletion(broadcastId);
      }
    },
    {
      connection: getRedisConfig(),
      concurrency,
      limiter: {
        max: 30,
        duration: 10000, // max 30 messages per 10s per worker
      },
    }
  );

  worker.on('completed', (job) => {
    console.log(`[Worker] Job ${job.id} has completed.`);
  });

  worker.on('failed', (job, err) => {
    console.error(`[Worker] Job ${job?.id} failed with error: ${err.message}`);
  });

  console.log(`⚡ Broadcast Worker started with concurrency: ${concurrency}`);
  return worker;
}

async function checkAndUpdateBroadcastCompletion(broadcastId) {
  try {
    const pendingRes = await query(
      "SELECT COUNT(*) FROM broadcast_recipients WHERE broadcast_id = $1 AND status IN ('pending', 'processing')",
      [broadcastId]
    );

    const remaining = parseInt(pendingRes.rows[0].count, 10);
    if (remaining === 0) {
      await query(
        "UPDATE broadcasts SET status = 'completed', updated_at = NOW() WHERE id = $1 AND status != 'completed'",
        [broadcastId]
      );
      console.log(`🎉 Broadcast #${broadcastId} is now marked as COMPLETED.`);
    }
  } catch (err) {
    console.error('Error checking broadcast completion:', err);
  }
}
