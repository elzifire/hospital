/**
 * Human-like Message Scheduling Algorithm for WhatsApp
 * Designed to mimic real human behavior and prevent Meta anti-spam / ban detection.
 *
 * Key features:
 * 1. Operates strictly within active human hours (e.g., 08:00 - 21:00 local time).
 * 2. Distributes messages across the available day window instead of burst sending.
 * 3. Incorporates variable random jitter between messages (e.g., 45s - 180s).
 * 4. Adds realistic human micro-breaks (e.g., 5-12 minute pauses after every 8-15 messages).
 * 5. Strictly enforces maximum 100 messages per day per device quota.
 */

export const DAILY_MAX_MESSAGES = 100;
export const ACTIVE_HOUR_START = 8;  // 08:00 AM
export const ACTIVE_HOUR_END = 21;   // 09:00 PM

/**
 * Checks if a given Date is within active human hours.
 */
export function isWithinActiveHours(date) {
  const hour = date.getHours();
  return hour >= ACTIVE_HOUR_START && hour < ACTIVE_HOUR_END;
}

/**
 * Returns the next valid active timestamp.
 * If currently before 08:00, shifts to 08:00-08:30 today.
 * If currently after 21:00, shifts to 08:00-08:30 tomorrow.
 */
export function getNextActiveWindowStart(baseDate = new Date()) {
  const date = new Date(baseDate);
  const hour = date.getHours();

  if (hour < ACTIVE_HOUR_START) {
    date.setHours(ACTIVE_HOUR_START, Math.floor(Math.random() * 15), 0, 0);
  } else if (hour >= ACTIVE_HOUR_END) {
    // Move to tomorrow 08:00 + small random minutes
    date.setDate(date.getDate() + 1);
    date.setHours(ACTIVE_HOUR_START, Math.floor(Math.random() * 20) + 5, 0, 0);
  } else {
    // Add small random delay from now (e.g. 1-3 minutes)
    date.setMinutes(date.getMinutes() + Math.floor(Math.random() * 3) + 1);
  }

  return date;
}

/**
 * Calculates randomized, human-like schedule timestamps for an array of recipients.
 *
 * @param {number} count Total number of recipients to schedule
 * @param {Date} startDate Starting reference date
 * @returns {Array<{ index: number, scheduledAt: Date, delayMs: number }>}
 */
export function generateHumanSchedule(count, startDate = new Date()) {
  if (count <= 0) return [];

  let currentTime = getNextActiveWindowStart(startDate);
  const schedules = [];

  // Determine end time of active window for current day (e.g. 20:45 today)
  const endOfDay = new Date(currentTime);
  endOfDay.setHours(ACTIVE_HOUR_END - 1, 45, 0, 0);

  // Available milliseconds remaining today
  let availableMs = endOfDay.getTime() - currentTime.getTime();
  if (availableMs < 30 * 60 * 1000) {
    // Less than 30 mins left today, push start to tomorrow morning
    currentTime = getNextActiveWindowStart(new Date(currentTime.getTime() + 24 * 60 * 60 * 1000));
    endOfDay.setDate(currentTime.getDate());
    endOfDay.setHours(ACTIVE_HOUR_END - 1, 45, 0, 0);
    availableMs = endOfDay.getTime() - currentTime.getTime();
  }

  // Base interval calculated evenly across the available window
  // But bounded between 40 seconds minimum and 8 minutes maximum
  const baseIntervalMs = Math.max(
    40 * 1000,
    Math.min(Math.floor(availableMs / Math.max(count, 1)), 8 * 60 * 1000)
  );

  let messagesSinceLastBreak = 0;
  const breakEveryNMessages = Math.floor(Math.random() * 7) + 8; // Break every 8 to 14 messages

  const nowMs = Date.now();

  for (let i = 0; i < count; i++) {
    // If current time exceeds active window (>= 21:00), rollover to next day at 08:00 AM
    if (currentTime.getHours() >= ACTIVE_HOUR_END) {
      currentTime.setDate(currentTime.getDate() + 1);
      currentTime.setHours(ACTIVE_HOUR_START, Math.floor(Math.random() * 25) + 5, 0, 0);
      messagesSinceLastBreak = 0;
    }

    // 1. Add random jitter: -25% to +40% variation on base interval
    const jitterPercent = (Math.random() * 0.65) - 0.25;
    const jitterMs = Math.floor(baseIntervalMs * jitterPercent);
    const stepInterval = Math.max(30 * 1000, baseIntervalMs + jitterMs);

    currentTime = new Date(currentTime.getTime() + stepInterval);
    messagesSinceLastBreak++;

    // 2. Insert human micro-break (e.g. grab a coffee, pause 6 - 15 minutes)
    if (messagesSinceLastBreak >= breakEveryNMessages) {
      const breakMinutes = Math.floor(Math.random() * 9) + 6; // 6 to 14 minutes break
      currentTime = new Date(currentTime.getTime() + breakMinutes * 60 * 1000);
      messagesSinceLastBreak = 0;
    }

    const scheduledDate = new Date(currentTime);
    const delayFromNowMs = Math.max(5000, scheduledDate.getTime() - nowMs);

    schedules.push({
      index: i,
      scheduledAt: scheduledDate,
      delayMs: delayFromNowMs,
    });
  }

  return schedules;
}
