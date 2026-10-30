import { epochDayToJalali, jalaliToEpochDay, weekdayOfEpochDay, nowruzEpochDay } from './core.js';
import { FormatOptions, formatFields } from './format.js';
import { DEFAULT_TIME_ZONE, fromWallTime, toWallTime } from './timezone.js';

export interface JalaliDateTimeParts {
  year: number;
  month: number;
  day: number;
  hour: number;
  minute: number;
  second: number;
  millisecond: number;
  /** 0 = Saturday … 6 = Friday */
  weekday: number;
  /** UTC offset in minutes at that instant, e.g. 210 */
  offset: number;
}

/** Split an instant into Jalali date + wall-clock time in a zone (default Asia/Tehran). */
export function toJalaliParts(instant: Date | number, opts: { timeZone?: string } = {}): JalaliDateTimeParts {
  const ms = typeof instant === 'number' ? instant : instant.getTime();
  if (!Number.isFinite(ms)) throw new RangeError('Invalid Date');
  const w = toWallTime(ms, opts.timeZone ?? DEFAULT_TIME_ZONE);
  const { year, month, day } = epochDayToJalali(w.epochDay);
  return { year, month, day, hour: w.hour, minute: w.minute, second: w.second, millisecond: w.millisecond,
    weekday: weekdayOfEpochDay(w.epochDay), offset: w.offset };
}

/** Build an instant from a Jalali date and wall-clock time in a zone (default Asia/Tehran). */
export function fromJalaliParts(
  p: { year: number; month: number; day: number; hour?: number; minute?: number; second?: number; millisecond?: number },
  opts: { timeZone?: string } = {},
): Date {
  for (const [k, max] of [['hour', 23], ['minute', 59], ['second', 59], ['millisecond', 999]] as const) {
    const v = p[k];
    if (v !== undefined && (!Number.isInteger(v) || v < 0 || v > max)) throw new RangeError(`${k} must be an integer 0–${max}`);
  }
  const n = jalaliToEpochDay(p.year, p.month, p.day);
  return new Date(fromWallTime(n, p.hour ?? 0, p.minute ?? 0, p.second ?? 0, p.millisecond ?? 0, opts.timeZone ?? DEFAULT_TIME_ZONE));
}

/**
 * Format an instant as a Jalali date-time.
 * formatDateTime(new Date(), 'dddd D MMMM YYYY، HH:mm') → 'شنبه ۱۵ فروردین ۱۴۰۴، ۰۹:۳۰'
 */
export function formatDateTime(
  instant: Date | number,
  pattern = 'YYYY/MM/DD HH:mm',
  opts: FormatOptions & { timeZone?: string } = {},
): string {
  const p = toJalaliParts(instant, opts);
  const nz = nowruzEpochDay(p.year);
  const n = jalaliToEpochDay(p.year, p.month, p.day);
  return formatFields({
    year: p.year, month: p.month, day: p.day, weekday: p.weekday, dayOfYear: n - nz + 1,
    week: Math.floor((n - nz + weekdayOfEpochDay(nz)) / 7) + 1,
    hour: p.hour, minute: p.minute, second: p.second, offset: p.offset,
  }, pattern, opts);
}
