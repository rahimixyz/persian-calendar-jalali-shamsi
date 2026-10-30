import {
  daysInMonth, daysInYear, epochDayToGregorian, epochDayToJalali, gregorianToEpochDay, isLeapYear,
  isUncertainYear, jalaliToEpochDay, MAX_YEAR, MIN_YEAR, nowruzEpochDay, weekdayOfEpochDay, YMD,
} from './core.js';
import { FormatOptions, formatFields } from './format.js';
import { parseJalali, ParseOptions } from './parse.js';
import { DEFAULT_TIME_ZONE, fromWallTime, toWallTime } from './timezone.js';

export interface ZoneOptions {
  /** IANA time zone. Defaults to 'Asia/Tehran' (resolved from embedded tzdata). */
  timeZone?: string;
}

export interface TimeOptions extends ZoneOptions {
  hour?: number;
  minute?: number;
  second?: number;
  millisecond?: number;
}

const pad = (n: number, w = 2) => String(n).padStart(w, '0');

/**
 * An immutable calendar date in the Solar Hijri (Jalali / Shamsi / Persian) calendar.
 *
 * ```ts
 * const d = new JalaliDate(1404, 1, 15);
 * d.toGregorian();             // { year: 2025, month: 4, day: 4 }
 * d.addMonths(1).format('D MMMM YYYY'); // '۱۵ اردیبهشت ۱۴۰۴'
 * JalaliDate.parse('۱۵ فروردین ۱۴۰۴').equals(d); // true
 * ```
 */
export class JalaliDate {
  readonly year: number;
  readonly month: number;
  readonly day: number;
  /** Days since 1970-01-01 (proleptic Gregorian). */
  readonly epochDay: number;

  /** Throws RangeError for dates that do not exist, e.g. new JalaliDate(1404, 12, 30). */
  constructor(year: number, month: number, day: number) {
    this.epochDay = jalaliToEpochDay(year, month, day);
    this.year = year;
    this.month = month;
    this.day = day;
    Object.freeze(this);
  }

  // ------------------------------------------------------------------ construction

  static fromEpochDay(epochDay: number): JalaliDate {
    const { year, month, day } = epochDayToJalali(epochDay);
    return new JalaliDate(year, month, day);
  }

  static fromGregorian(year: number, month: number, day: number): JalaliDate {
    return JalaliDate.fromEpochDay(gregorianToEpochDay(year, month, day));
  }

  /** The calendar date of an instant in a time zone (default Asia/Tehran). */
  static fromDate(date: Date | number, opts: ZoneOptions = {}): JalaliDate {
    const ms = typeof date === 'number' ? date : date.getTime();
    if (!Number.isFinite(ms)) throw new RangeError('Invalid Date');
    return JalaliDate.fromEpochDay(toWallTime(ms, opts.timeZone ?? DEFAULT_TIME_ZONE).epochDay);
  }

  /** Today in Tehran (or in the given time zone). */
  static today(opts: ZoneOptions = {}): JalaliDate {
    return JalaliDate.fromDate(Date.now(), opts);
  }

  /** See parseJalali for the accepted formats. Throws RangeError with a readable reason. */
  static parse(text: string, opts?: ParseOptions): JalaliDate {
    const { year, month, day } = parseJalali(text, opts);
    return new JalaliDate(year, month, day);
  }

  /** Like parse, but returns null instead of throwing. */
  static tryParse(text: string, opts?: ParseOptions): JalaliDate | null {
    try {
      return JalaliDate.parse(text, opts);
    } catch {
      return null;
    }
  }

  static isValid(year: number, month: number, day: number): boolean {
    try {
      jalaliToEpochDay(year, month, day);
      return true;
    } catch {
      return false;
    }
  }

  static compare(a: JalaliDate, b: JalaliDate): number {
    return a.epochDay - b.epochDay;
  }

  static min(...dates: JalaliDate[]): JalaliDate {
    return dates.reduce((a, b) => (b.epochDay < a.epochDay ? b : a));
  }

  static max(...dates: JalaliDate[]): JalaliDate {
    return dates.reduce((a, b) => (b.epochDay > a.epochDay ? b : a));
  }

  // ------------------------------------------------------------------ properties

  /** 0 = Saturday (شنبه) … 6 = Friday (جمعه). */
  get dayOfWeek(): number {
    return weekdayOfEpochDay(this.epochDay);
  }

  /** 1 … 366. */
  get dayOfYear(): number {
    return this.epochDay - nowruzEpochDay(this.year) + 1;
  }

  /** Week number; week 1 contains 1 Farvardin and weeks start on Saturday. */
  get weekOfYear(): number {
    const nz = nowruzEpochDay(this.year);
    return Math.floor((this.epochDay - nz + weekdayOfEpochDay(nz)) / 7) + 1;
  }

  /** Season 1–4 (spring = Farvardin–Khordad). */
  get season(): number {
    return Math.floor((this.month - 1) / 3) + 1;
  }

  isLeapYear(): boolean {
    return isLeapYear(this.year);
  }

  daysInMonth(): number {
    return daysInMonth(this.year, this.month);
  }

  daysInYear(): number {
    return daysInYear(this.year);
  }

  /** True when this year's start is astronomically borderline (see isUncertainYear). */
  isUncertainYear(): boolean {
    return isUncertainYear(this.year);
  }

  // ------------------------------------------------------------------ arithmetic

  addDays(days: number): JalaliDate {
    if (!Number.isInteger(days)) throw new TypeError('days must be an integer');
    return JalaliDate.fromEpochDay(this.epochDay + days);
  }

  addWeeks(weeks: number): JalaliDate {
    return this.addDays(weeks * 7);
  }

  /** Clamps to the end of the month: 31 Shahrivar + 1 month = 30 Mehr. */
  addMonths(months: number): JalaliDate {
    if (!Number.isInteger(months)) throw new TypeError('months must be an integer');
    const total = this.year * 12 + (this.month - 1) + months;
    const y = Math.floor(total / 12);
    const m = total - y * 12 + 1;
    if (y < MIN_YEAR || y > MAX_YEAR) throw new RangeError(`Result is outside the supported range ${MIN_YEAR}–${MAX_YEAR}`);
    return new JalaliDate(y, m, Math.min(this.day, daysInMonth(y, m)));
  }

  /** Clamps 30 Esfand to 29 Esfand in common years. */
  addYears(years: number): JalaliDate {
    return this.addMonths(years * 12);
  }

  /** Replace fields; the result must be a real date (no silent clamping). */
  with(fields: Partial<YMD>): JalaliDate {
    return new JalaliDate(fields.year ?? this.year, fields.month ?? this.month, fields.day ?? this.day);
  }

  startOfMonth(): JalaliDate {
    return new JalaliDate(this.year, this.month, 1);
  }

  endOfMonth(): JalaliDate {
    return new JalaliDate(this.year, this.month, this.daysInMonth());
  }

  startOfYear(): JalaliDate {
    return new JalaliDate(this.year, 1, 1);
  }

  endOfYear(): JalaliDate {
    return new JalaliDate(this.year, 12, daysInMonth(this.year, 12));
  }

  /** The Saturday on or before this date. */
  startOfWeek(): JalaliDate {
    return this.addDays(-this.dayOfWeek);
  }

  /** The Friday on or after this date. */
  endOfWeek(): JalaliDate {
    return this.addDays(6 - this.dayOfWeek);
  }

  /** this − other, in days. */
  diffInDays(other: JalaliDate): number {
    return this.epochDay - other.epochDay;
  }

  /** Whole calendar months from other to this (negative if this is earlier). */
  diffInMonths(other: JalaliDate): number {
    if (this.epochDay < other.epochDay) return -other.diffInMonths(this);
    let months = (this.year - other.year) * 12 + (this.month - other.month);
    if (this.day < other.day && this.day !== this.daysInMonth()) months--;
    return months;
  }

  /** Whole years from other to this; a birthday on 30 Esfand counts on 29 Esfand in common years. */
  diffInYears(other: JalaliDate): number {
    const y = Math.trunc(this.diffInMonths(other) / 12);
    return y === 0 ? 0 : y;
  }

  // ------------------------------------------------------------------ comparison

  equals(other: JalaliDate): boolean {
    return this.epochDay === other.epochDay;
  }

  isBefore(other: JalaliDate): boolean {
    return this.epochDay < other.epochDay;
  }

  isAfter(other: JalaliDate): boolean {
    return this.epochDay > other.epochDay;
  }

  /** Inclusive range check. */
  isBetween(start: JalaliDate, end: JalaliDate): boolean {
    return this.epochDay >= start.epochDay && this.epochDay <= end.epochDay;
  }

  // ------------------------------------------------------------------ conversion

  toGregorian(): YMD {
    return epochDayToGregorian(this.epochDay);
  }

  /** Gregorian ISO-8601 date, e.g. "2025-04-04". */
  toISODate(): string {
    const g = this.toGregorian();
    return `${pad(g.year, 4)}-${pad(g.month)}-${pad(g.day)}`;
  }

  /** The instant at the given wall-clock time (default 00:00) in Tehran or another zone. */
  toDate(opts: TimeOptions = {}): Date {
    return new Date(fromWallTime(this.epochDay, opts.hour ?? 0, opts.minute ?? 0, opts.second ?? 0,
      opts.millisecond ?? 0, opts.timeZone ?? DEFAULT_TIME_ZONE));
  }

  /** Format with tokens such as 'YYYY/MM/DD' or 'dddd D MMMM YYYY'. Persian digits by default. */
  format(pattern = 'YYYY/MM/DD', opts?: FormatOptions): string {
    return formatFields({
      year: this.year, month: this.month, day: this.day, weekday: this.dayOfWeek,
      dayOfYear: this.dayOfYear, week: this.weekOfYear,
    }, pattern, opts);
  }

  /** "1404/01/15" with Latin digits — stable for logs, keys and JSON. */
  toString(): string {
    return `${pad(this.year, 4)}/${pad(this.month)}/${pad(this.day)}`;
  }

  toJSON(): string {
    return this.toString();
  }

  /** Allows <, >, sorting by value. */
  valueOf(): number {
    return this.epochDay;
  }
}
