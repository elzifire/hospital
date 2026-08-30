import makeWASocket, {
  DisconnectReason,
  useMultiFileAuthState,
  Browsers,
  fetchLatestBaileysVersion,
} from '@whiskeysockets/baileys';
import { Boom } from '@hapi/boom';
import pino from 'pino';
import QRCode from 'qrcode';
import path from 'path';
import fs from 'fs';
import { BaseProvider } from './base.js';
import { query } from '../config/database.js';

export class BaileysProvider extends BaseProvider {
  constructor(device) {
    super(device);
    this.sock = null;
    this.authPath = path.resolve(`./wa_auth/device_${this.deviceId}`);
    this.isReconnecting = false;
    this.logger = pino({ level: 'silent' });
  }

  formatJid(number) {
    let clean = number.toString().replace(/[^0-9]/g, '');
    if (clean.startsWith('08')) {
      clean = '62' + clean.substring(1);
    } else if (clean.startsWith('8')) {
      clean = '62' + clean;
    }
    if (!clean.endsWith('@s.whatsapp.net')) {
      clean = `${clean}@s.whatsapp.net`;
    }
    return clean;
  }

  async connect() {
    try {
      if (!fs.existsSync(this.authPath)) {
        fs.mkdirSync(this.authPath, { recursive: true });
      }

      this.status = 'connecting';
      this.emit('status.change', { deviceId: this.deviceId, status: this.status });
      await this.updateDbStatus('connecting');

      const { state, saveCreds } = await useMultiFileAuthState(this.authPath);
      const { version } = await fetchLatestBaileysVersion().catch(() => ({ version: [2, 3000, 1015901307] }));

      this.sock = makeWASocket({
        version,
        logger: this.logger,
        printQRInTerminal: false,
        auth: state,
        browser: Browsers.macOS('Desktop'),
        syncFullHistory: false,
        markOnlineOnConnect: false,
        generateHighQualityLinkPreview: true,
      });

      this.sock.ev.on('creds.update', saveCreds);

      this.sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
          try {
            this.qrCode = await QRCode.toDataURL(qr);
            this.emit('qr', { deviceId: this.deviceId, qrCode: this.qrCode });
          } catch (qrErr) {
            console.error(`[Baileys ${this.deviceId}] Error generating QR base64:`, qrErr);
          }
        }

        if (connection === 'close') {
          this.qrCode = null;
          const statusCode = (lastDisconnect?.error instanceof Boom)
            ? lastDisconnect.error.output?.statusCode
            : null;
          const shouldReconnect = statusCode !== DisconnectReason.loggedOut;

          console.log(`[Baileys ${this.deviceId}] Connection closed. Reason:`, statusCode, 'Reconnect:', shouldReconnect);

          if (statusCode === DisconnectReason.loggedOut) {
            this.status = 'disconnected';
            await this.updateDbStatus('disconnected', null);
            this.emit('status.change', { deviceId: this.deviceId, status: this.status });
            this.cleanupAuthFiles();
          } else if (shouldReconnect && !this.isReconnecting) {
            this.isReconnecting = true;
            this.status = 'connecting';
            this.emit('status.change', { deviceId: this.deviceId, status: this.status });
            setTimeout(async () => {
              this.isReconnecting = false;
              await this.connect();
            }, 3000);
          } else {
            this.status = 'disconnected';
            await this.updateDbStatus('disconnected');
            this.emit('status.change', { deviceId: this.deviceId, status: this.status });
          }
        } else if (connection === 'open') {
          this.qrCode = null;
          this.status = 'connected';
          const userPhone = this.sock.user?.id ? this.sock.user.id.split(':')[0] : null;
          console.log(`[Baileys ${this.deviceId}] Connection opened. Logged in as:`, userPhone);

          await this.updateDbStatus('connected', userPhone);
          this.emit('status.change', { deviceId: this.deviceId, status: this.status, phoneNumber: userPhone });
        }
      });

      // Outreach listener: capture incoming messages
      this.sock.ev.on('messages.upsert', async ({ messages, type }) => {
        if (type !== 'notify') return;

        for (const msg of messages) {
          // Ignore messages sent by bot itself or status broadcasts
          if (msg.key.fromMe || msg.key.remoteJid === 'status@broadcast') continue;

          try {
            const senderJid = msg.key.remoteJid;
            const fromNumber = senderJid.replace('@s.whatsapp.net', '').replace('@g.us', '');
            const pushName = msg.pushName || 'Unknown';

            let textContent = '';
            let messageType = 'text';
            let mediaUrl = null;

            if (msg.message?.conversation) {
              textContent = msg.message.conversation;
            } else if (msg.message?.extendedTextMessage?.text) {
              textContent = msg.message.extendedTextMessage.text;
            } else if (msg.message?.imageMessage) {
              messageType = 'image';
              textContent = msg.message.imageMessage.caption || '[Image]';
            } else if (msg.message?.documentMessage) {
              messageType = 'document';
              textContent = msg.message.documentMessage.fileName || '[Document]';
            } else if (msg.message?.audioMessage) {
              messageType = 'audio';
              textContent = '[Voice/Audio]';
            } else if (msg.message?.videoMessage) {
              messageType = 'video';
              textContent = msg.message.videoMessage.caption || '[Video]';
            }

            this.emit('message.received', {
              deviceId: this.deviceId,
              fromNumber,
              fromName: pushName,
              message: textContent,
              messageType,
              mediaUrl,
              rawData: msg,
              receivedAt: new Date(msg.messageTimestamp ? msg.messageTimestamp * 1000 : Date.now()),
            });
          } catch (msgErr) {
            console.error(`[Baileys ${this.deviceId}] Error processing received message:`, msgErr);
          }
        }
      });

      return { success: true, message: 'Baileys connection initialized.' };
    } catch (err) {
      console.error(`[Baileys ${this.deviceId}] Connect error:`, err);
      this.status = 'disconnected';
      await this.updateDbStatus('disconnected');
      throw err;
    }
  }

  async disconnect() {
    try {
      if (this.sock) {
        await this.sock.logout().catch(() => {});
        this.sock.end(undefined);
        this.sock = null;
      }
      this.status = 'disconnected';
      this.qrCode = null;
      await this.updateDbStatus('disconnected');
      this.cleanupAuthFiles();
      this.emit('status.change', { deviceId: this.deviceId, status: this.status });
      return { success: true, message: 'Baileys disconnected successfully.' };
    } catch (err) {
      console.error(`[Baileys ${this.deviceId}] Disconnect error:`, err);
      throw err;
    }
  }

  async sendMessage(to, text, options = {}) {
    if (this.status !== 'connected' || !this.sock) {
      throw new Error(`Device #${this.deviceId} is not connected to WhatsApp.`);
    }

    const jid = this.formatJid(to);
    const sent = await this.sock.sendMessage(jid, { text });
    return {
      success: true,
      messageId: sent.key.id,
      to,
      timestamp: sent.messageTimestamp,
    };
  }

  async sendImage(to, imageUrl, caption = '', options = {}) {
    if (this.status !== 'connected' || !this.sock) {
      throw new Error(`Device #${this.deviceId} is not connected to WhatsApp.`);
    }

    const jid = this.formatJid(to);
    const sent = await this.sock.sendMessage(jid, {
      image: { url: imageUrl },
      caption: caption || undefined,
    });
    return {
      success: true,
      messageId: sent.key.id,
      to,
      timestamp: sent.messageTimestamp,
    };
  }

  async updateDbStatus(status, phoneNumber = null) {
    try {
      if (phoneNumber) {
        await query(
          'UPDATE wa_devices SET status = $1, phone_number = $2, updated_at = NOW() WHERE id = $3',
          [status, phoneNumber, this.deviceId]
        );
        this.device.phone_number = phoneNumber;
      } else {
        await query(
          'UPDATE wa_devices SET status = $1, updated_at = NOW() WHERE id = $2',
          [status, this.deviceId]
        );
      }
      this.device.status = status;
    } catch (err) {
      console.error(`[Baileys ${this.deviceId}] Error updating DB status:`, err.message);
    }
  }

  cleanupAuthFiles() {
    try {
      if (fs.existsSync(this.authPath)) {
        fs.rmSync(this.authPath, { recursive: true, force: true });
        console.log(`[Baileys ${this.deviceId}] Cleaned up auth files.`);
      }
    } catch (err) {
      console.error(`[Baileys ${this.deviceId}] Failed to clean auth folder:`, err.message);
    }
  }
}
