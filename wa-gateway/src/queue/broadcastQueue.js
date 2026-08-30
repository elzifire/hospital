import { Queue } from 'bullmq';
import { getRedisConfig } from '../config/redis.js';

export const BROADCAST_QUEUE_NAME = 'wa-broadcast-queue';

export const broadcastQueue = new Queue(BROADCAST_QUEUE_NAME, {
  connection: getRedisConfig(),
  defaultJobOptions: {
    attempts: 3,
    backoff: {
      type: 'exponential',
      delay: 5000,
    },
    removeOnComplete: {
      count: 1000,
      age: 24 * 3600, // keep 24 hours
    },
    removeOnFail: {
      count: 2000,
      age: 7 * 24 * 3600, // keep 7 days
    },
  },
});

export const addBroadcastJob = async (recipientData, opts = {}) => {
  return await broadcastQueue.add('send-message', recipientData, opts);
};

export const addBroadcastJobBatch = async (recipients) => {
  const jobs = recipients.map((r) => ({
    name: 'send-message',
    data: r,
    opts: {
      jobId: `rec_${r.recipientId}_${Date.now()}`,
    },
  }));
  return await broadcastQueue.addBulk(jobs);
};
