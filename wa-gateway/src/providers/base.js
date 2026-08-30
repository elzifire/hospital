import EventEmitter from 'events';

export class BaseProvider extends EventEmitter {
  constructor(device) {
    super();
    this.device = device; // { id, name, provider, phone_number, provider_config, status }
    this.deviceId = device.id;
    this.status = device.status || 'disconnected';
    this.qrCode = null;
  }

  async connect() {
    throw new Error('Method connect() must be implemented by provider subclass');
  }

  async disconnect() {
    throw new Error('Method disconnect() must be implemented by provider subclass');
  }

  async sendMessage(to, text, options = {}) {
    throw new Error('Method sendMessage() must be implemented by provider subclass');
  }

  async sendImage(to, imageUrl, caption = '', options = {}) {
    throw new Error('Method sendImage() must be implemented by provider subclass');
  }

  getStatus() {
    return {
      deviceId: this.deviceId,
      provider: this.device.provider,
      status: this.status,
      phoneNumber: this.device.phone_number,
      hasQr: !!this.qrCode,
    };
  }

  getQrCode() {
    return this.qrCode;
  }
}
