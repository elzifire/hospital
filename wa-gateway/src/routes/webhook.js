import express from 'express';
import { query } from '../config/database.js';
import { handleIncomingMessage } from '../services/outreachService.js';

const router = express.Router();

// POST /api/webhook/fonnte - Inbound webhook from Fonnte
router.post('/fonnte', async (req, res) => {
  try {
    const { sender, message, name, device, url } = req.body;

    console.log('[Webhook Fonnte] Inbound payload:', req.body);

    if (!sender) {
      return res.status(400).json({ status: false, message: 'Missing sender' });
    }

    // Try to find the matching device in DB
    let deviceId = null;
    if (device) {
      const devRes = await query(
        "SELECT id FROM wa_devices WHERE provider = 'fonnte' AND (phone_number = $1 OR provider_config->>'device' = $1) LIMIT 1",
        [device]
      );
      if (devRes.rows.length > 0) {
        deviceId = devRes.rows[0].id;
      }
    }

    if (!deviceId) {
      // Fallback: Pick the first fonnte device
      const fallbackDev = await query("SELECT id FROM wa_devices WHERE provider = 'fonnte' LIMIT 1");
      if (fallbackDev.rows.length > 0) {
        deviceId = fallbackDev.rows[0].id;
      }
    }

    if (deviceId) {
      await handleIncomingMessage({
        deviceId,
        fromNumber: sender,
        fromName: name || 'User',
        message: message || '',
        messageType: url ? 'image' : 'text',
        mediaUrl: url || null,
        rawData: req.body,
        receivedAt: new Date(),
      });
    }

    return res.json({ status: true, message: 'Webhook processed' });
  } catch (error) {
    console.error('Fonnte Webhook Error:', error);
    return res.status(500).json({ status: false, message: error.message });
  }
});

// POST /api/webhook/general - Generic webhook receiver
router.post('/general', async (req, res) => {
  try {
    const { device_id, from, name, message, message_type, media_url } = req.body;

    if (!device_id || !from) {
      return res.status(400).json({ success: false, message: 'device_id and from are required' });
    }

    await handleIncomingMessage({
      deviceId: device_id,
      fromNumber: from,
      fromName: name || 'Contact',
      message: message || '',
      messageType: message_type || 'text',
      mediaUrl: media_url || null,
      rawData: req.body,
      receivedAt: new Date(),
    });

    return res.json({ success: true, message: 'Message logged in outreach' });
  } catch (error) {
    return res.status(500).json({ success: false, message: error.message });
  }
});

export default router;
