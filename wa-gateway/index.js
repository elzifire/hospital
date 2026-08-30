import express from 'express';
import cors from 'cors';
import path from 'path';
import { fileURLToPath } from 'url';
import dotenv from 'dotenv';

import authRoutes from './src/routes/auth.js';
import devicesRoutes from './src/routes/devices.js';
import broadcastRoutes from './src/routes/broadcast.js';
import outreachRoutes from './src/routes/outreach.js';
import webhookRoutes from './src/routes/webhook.js';

import { initBroadcastWorker } from './src/queue/broadcastWorker.js';
import { initCronJobs } from './src/cron/scheduler.js';
import { providerManager } from './src/providers/manager.js';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = process.env.PORT || 3000;

// Middlewares
app.use(cors());
app.use(express.json({ limit: '50mb' }));
app.use(express.urlencoded({ extended: true, limit: '50mb' }));

// Serve static frontend assets
app.use(express.static(path.join(__dirname, 'public')));

// API Routes
app.use('/api/auth', authRoutes);
app.use('/api/devices', devicesRoutes);
app.use('/api/broadcasts', broadcastRoutes);
app.use('/api/outreach', outreachRoutes);
app.use('/api/webhook', webhookRoutes);

// Health check endpoint
app.get('/api/health', (req, res) => {
  res.json({
    status: 'ok',
    timestamp: new Date(),
    uptime: process.uptime(),
    service: 'WA Gateway Multi-Provider & Redis Queue',
  });
});

// Fallback route for SPA
app.get('/{*splat}', (req, res, next) => {
  if (req.path.startsWith('/api/')) return next();
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// Error handling middleware
app.use((err, req, res, next) => {
  console.error('Unhandled application error:', err);
  res.status(err.status || 500).json({
    success: false,
    message: err.message || 'Internal Server Error',
  });
});

// Server Initialization
async function startServer() {
  try {
    app.listen(PORT, async () => {
      console.log('====================================================');
      console.log(`🚀 WA Gateway Server running at: http://localhost:${PORT}`);
      console.log(`📱 UI Dashboard available at: http://localhost:${PORT}/dashboard.html`);
      console.log('====================================================');

      // 1. Initialize BullMQ Worker
      try {
        initBroadcastWorker();
      } catch (qErr) {
        console.error('⚠️ Could not start BullMQ Worker (Check Redis connection):', qErr.message);
      }

      // 2. Initialize Cron Schedulers
      try {
        initCronJobs();
      } catch (cErr) {
        console.error('⚠️ Could not start Cron Jobs:', cErr.message);
      }

      // 3. Boot active device sessions from PostgreSQL
      try {
        await providerManager.bootActiveDevices();
      } catch (pErr) {
        console.error('⚠️ Could not boot active WhatsApp devices:', pErr.message);
      }
    });
  } catch (error) {
    console.error('❌ Failed to start WA Gateway server:', error);
    process.exit(1);
  }
}

startServer();
