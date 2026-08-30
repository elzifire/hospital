import { pool } from '../config/database.js';

async function runMigrations() {
  console.log('🚀 Running database migrations for WA Gateway...');

  const client = await pool.connect();
  try {
    await client.query('BEGIN');

    // 1. wa_devices table
    console.log('Creating table: wa_devices');
    await client.query(`
      CREATE TABLE IF NOT EXISTS wa_devices (
        id SERIAL PRIMARY KEY,
        user_id BIGINT NOT NULL,
        name VARCHAR(255) NOT NULL,
        provider VARCHAR(50) NOT NULL DEFAULT 'baileys',
        phone_number VARCHAR(50),
        provider_config JSONB DEFAULT '{}'::jsonb,
        status VARCHAR(50) DEFAULT 'disconnected',
        webhook_url TEXT,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
      );
      CREATE INDEX IF NOT EXISTS idx_wa_devices_user_id ON wa_devices(user_id);
    `);

    // 2. broadcasts table
    console.log('Creating table: broadcasts');
    await client.query(`
      CREATE TABLE IF NOT EXISTS broadcasts (
        id SERIAL PRIMARY KEY,
        user_id BIGINT NOT NULL,
        device_id INTEGER NOT NULL REFERENCES wa_devices(id) ON DELETE CASCADE,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        media_url TEXT,
        status VARCHAR(50) DEFAULT 'pending',
        scheduled_at TIMESTAMP WITH TIME ZONE,
        delay_min_ms INTEGER DEFAULT 1500,
        delay_max_ms INTEGER DEFAULT 3500,
        total_recipients INTEGER DEFAULT 0,
        sent_count INTEGER DEFAULT 0,
        failed_count INTEGER DEFAULT 0,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
      );
      CREATE INDEX IF NOT EXISTS idx_broadcasts_user_id ON broadcasts(user_id);
      CREATE INDEX IF NOT EXISTS idx_broadcasts_device_id ON broadcasts(device_id);
      CREATE INDEX IF NOT EXISTS idx_broadcasts_status ON broadcasts(status);
    `);

    // 3. broadcast_recipients table
    console.log('Creating table: broadcast_recipients');
    await client.query(`
      CREATE TABLE IF NOT EXISTS broadcast_recipients (
        id SERIAL PRIMARY KEY,
        broadcast_id INTEGER NOT NULL REFERENCES broadcasts(id) ON DELETE CASCADE,
        phone_number VARCHAR(50) NOT NULL,
        name VARCHAR(255),
        custom_data JSONB DEFAULT '{}'::jsonb,
        status VARCHAR(50) DEFAULT 'pending',
        error_message TEXT,
        retry_count INTEGER DEFAULT 0,
        job_id VARCHAR(255),
        sent_at TIMESTAMP WITH TIME ZONE,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
      );
      CREATE INDEX IF NOT EXISTS idx_recipients_broadcast_id ON broadcast_recipients(broadcast_id);
      CREATE INDEX IF NOT EXISTS idx_recipients_status ON broadcast_recipients(status);
    `);

    // 4. outreach_messages table
    console.log('Creating table: outreach_messages');
    await client.query(`
      CREATE TABLE IF NOT EXISTS outreach_messages (
        id SERIAL PRIMARY KEY,
        device_id INTEGER NOT NULL REFERENCES wa_devices(id) ON DELETE CASCADE,
        from_number VARCHAR(50) NOT NULL,
        from_name VARCHAR(255),
        message TEXT,
        message_type VARCHAR(50) DEFAULT 'text',
        media_url TEXT,
        raw_data JSONB,
        is_read BOOLEAN DEFAULT FALSE,
        webhook_sent BOOLEAN DEFAULT FALSE,
        webhook_response TEXT,
        received_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
      );
      CREATE INDEX IF NOT EXISTS idx_outreach_device_id ON outreach_messages(device_id);
      CREATE INDEX IF NOT EXISTS idx_outreach_from_number ON outreach_messages(from_number);
      CREATE INDEX IF NOT EXISTS idx_outreach_is_read ON outreach_messages(is_read);
    `);

    await client.query('COMMIT');
    console.log('✅ Migrations completed successfully!');
  } catch (error) {
    await client.query('ROLLBACK');
    console.error('❌ Migration failed:', error);
    process.exit(1);
  } finally {
    client.release();
    await pool.end();
  }
}

runMigrations();
