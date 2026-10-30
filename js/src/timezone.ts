/**
 * Time-zone handling.
 *
 * Asia/Tehran is resolved from an embedded copy of the IANA history (tzdata 2026e),
 * so results do not depend on the user's operating system or browser. This matters
 * because Iran abolished daylight saving time in 2022; systems with older tzdata still
 * add an hour every spring. Other zones use the host's Intl implementation.
 */
import { TEHRAN_INITIAL_OFFSET, TEHRAN_TRANSITIONS } from './data.js';

const TEHRAN_NAMES = new Set(['Asia/Tehran', 'Iran']);
export const DEFAULT_TIME_ZONE = 'Asia/Tehran';

function tehranOffset(utcMs: number): number {
  const s = Math.floor(utcMs / 1000);
  let lo = 0;
  let hi = TEHRAN_TRANSITIONS.length - 1;
  if (s < TEHRAN_TRANSITIONS[0][0]) return TEHRAN_INITIAL_OFFSET;
  while (lo < hi) {
    const mid = (lo + hi + 1) >> 1;
    if (TEHRAN_TRANSITIONS[mid][0] <= s) lo = mid;
    else hi = mid - 1;
  }
  return TEHRAN_TRANSITIONS[lo][1];
}

const formatters = new Map<string, Intl.DateTimeFormat>();

function intlOffset(utcMs: number, timeZone: string): number {
  let f = formatters.get(timeZone);
  if (!f) {
    try {
      f = new Intl.DateTimeFormat('en-US', { timeZone, timeZoneName: 'longOffset' });
    } catch {
      throw new RangeError(`Unknown time zone: ${timeZone}`);
    }
    formatters.set(timeZone, f);
  }
  const name = f.formatToParts(new Date(utcMs)).find((p) => p.type === 'timeZoneName')?.value ?? 'GMT';
  if (name === 'GMT' || name === 'UTC') return 0;
  const m = /([+-])(\d{1,2}):?(\d{2})?/.exec(name);
  if (!m) throw new RangeError(`Cannot read the UTC offset of ${timeZone}`);
  return (m[1] === '-' ? -1 : 1) * (Number(m[2]) * 60 + Number(m[3] ?? 0));
}

/** UTC offset in minutes of a time zone at an instant (e.g. 210 for Tehran today). */
export function offsetMinutes(utcMs: number, timeZone: string = DEFAULT_TIME_ZONE): number {
  if (TEHRAN_NAMES.has(timeZone)) return tehranOffset(utcMs);
  if (timeZone === 'UTC' || timeZone === 'Etc/UTC' || timeZone === 'GMT') return 0;
  return intlOffset(utcMs, timeZone);
}

export interface WallTime {
  epochDay: number; // days since 1970-01-01 of the local (wall-clock) date
  hour: number;
  minute: number;
  second: number;
  millisecond: number;
  offset: number; // minutes
}

/** Instant → local wall-clock time in the zone. */
export function toWallTime(utcMs: number, timeZone: string = DEFAULT_TIME_ZONE): WallTime {
  const offset = offsetMinutes(utcMs, timeZone);
  const local = utcMs + offset * 60000;
  const epochDay = Math.floor(local / 86400000);
  let ms = local - epochDay * 86400000;
  const hour = Math.floor(ms / 3600000);
  ms -= hour * 3600000;
  const minute = Math.floor(ms / 60000);
  ms -= minute * 60000;
  const second = Math.floor(ms / 1000);
  return { epochDay, hour, minute, second, millisecond: ms - second * 1000, offset };
}

/**
 * Local wall-clock time → instant. Non-existent times (spring-forward gap) move forward by
 * the gap; ambiguous times (autumn overlap) resolve to the earlier instant — the same
 * "compatible" behaviour as Temporal and most date libraries.
 */
export function fromWallTime(
  epochDay: number, hour = 0, minute = 0, second = 0, millisecond = 0,
  timeZone: string = DEFAULT_TIME_ZONE,
): number {
  const local = epochDay * 86400000 + hour * 3600000 + minute * 60000 + second * 1000 + millisecond;
  const before = offsetMinutes(local - 86400000, timeZone);
  const after = offsetMinutes(local + 86400000, timeZone);
  const candidates: number[] = [];
  for (const o of new Set([before, after])) {
    const t = local - o * 60000;
    if (offsetMinutes(t, timeZone) === o) candidates.push(t);
  }
  if (candidates.length) return Math.min(...candidates);
  return local - before * 60000; // gap
}

export function formatOffset(minutes: number, withColon = true): string {
  const sign = minutes < 0 ? '-' : '+';
  const a = Math.abs(minutes);
  const h = String(Math.floor(a / 60)).padStart(2, '0');
  const m = String(a % 60).padStart(2, '0');
  return sign + h + (withColon ? ':' : '') + m;
}
