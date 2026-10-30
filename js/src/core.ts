/**
 * Core Solar Hijri (Jalali / Shamsi) arithmetic.
 *
 * Every date is reduced to an "epoch day": the number of days since 1970-01-01
 * in the proleptic Gregorian calendar. Jalali years are looked up in an
 * astronomical table (see reference/ and scripts/), not computed with an
 * arithmetic cycle, so leap years match the vernal-equinox rule used in Iran.
 */
import { LEAP_HEX, MAX_YEAR, MIN_YEAR, NOWRUZ_EPOCH_DAY, UNCERTAIN_YEARS } from './data.js';

export { MAX_YEAR, MIN_YEAR };

export interface YMD {
  year: number;
  month: number;
  day: number;
}

const COUNT = MAX_YEAR - MIN_YEAR + 1;
const LEAP = new Uint8Array(COUNT);
for (let i = 0; i < COUNT; i++) {
  const byte = parseInt(LEAP_HEX.substr((i >> 3) * 2, 2), 16);
  LEAP[i] = (byte >> (i & 7)) & 1;
}
/** START[i] = epoch day of 1 Farvardin of year MIN_YEAR + i; START[COUNT] = day after the last supported day. */
const START = new Int32Array(COUNT + 1);
START[0] = NOWRUZ_EPOCH_DAY;
for (let i = 0; i < COUNT; i++) START[i + 1] = START[i] + 365 + LEAP[i];
const UNCERTAIN = new Set(UNCERTAIN_YEARS);

export const MONTH_NAMES_EN = [
  'Farvardin', 'Ordibehesht', 'Khordad', 'Tir', 'Mordad', 'Shahrivar',
  'Mehr', 'Aban', 'Azar', 'Dey', 'Bahman', 'Esfand',
] as const;

/** First supported epoch day (1 Farvardin MIN_YEAR) and last supported epoch day (last day of MAX_YEAR). */
export const MIN_EPOCH_DAY = START[0];
export const MAX_EPOCH_DAY = START[COUNT] - 1;

function isInt(n: unknown): n is number {
  return typeof n === 'number' && Number.isInteger(n);
}

function assertYear(jy: number): void {
  if (!isInt(jy)) throw new TypeError(`Jalali year must be an integer, got ${String(jy)}`);
  if (jy < MIN_YEAR || jy > MAX_YEAR) {
    throw new RangeError(`Jalali year ${jy} is outside the supported range ${MIN_YEAR}–${MAX_YEAR}`);
  }
}

function assertMonth(jm: number): void {
  if (!isInt(jm) || jm < 1 || jm > 12) throw new RangeError(`Jalali month must be an integer 1–12, got ${String(jm)}`);
}

/** True when the Jalali year has 366 days (Esfand has 30 days). */
export function isLeapYear(jy: number): boolean {
  assertYear(jy);
  return LEAP[jy - MIN_YEAR] === 1;
}

/** 365 or 366. */
export function daysInYear(jy: number): number {
  return isLeapYear(jy) ? 366 : 365;
}

/** Months 1–6 have 31 days, 7–11 have 30, Esfand (12) has 29 or 30. */
export function daysInMonth(jy: number, jm: number): number {
  assertYear(jy);
  assertMonth(jm);
  if (jm <= 6) return 31;
  if (jm <= 11) return 30;
  return LEAP[jy - MIN_YEAR] ? 30 : 29;
}

/** Never throws: false for anything that is not an existing Jalali date in the supported range. */
export function isValidDate(jy: number, jm: number, jd: number): boolean {
  if (!isInt(jy) || !isInt(jm) || !isInt(jd)) return false;
  if (jy < MIN_YEAR || jy > MAX_YEAR || jm < 1 || jm > 12 || jd < 1) return false;
  return jd <= daysInMonth(jy, jm);
}

/**
 * True for the rare years whose start depends on the exact noon convention
 * (52.5°E mean noon vs. Tehran apparent noon) or lies within the error margin of
 * the equinox computation. In 1200–1700 these are 1309, 1470, 1503, 1536, 1602, 1635, 1668.
 */
export function isUncertainYear(jy: number): boolean {
  assertYear(jy);
  return UNCERTAIN.has(jy);
}

export function assertValidDate(jy: number, jm: number, jd: number): void {
  assertYear(jy);
  assertMonth(jm);
  if (!isInt(jd)) throw new TypeError(`Jalali day must be an integer, got ${String(jd)}`);
  const dim = daysInMonth(jy, jm);
  if (jd < 1 || jd > dim) {
    const why = jm === 12 && jd === 30 ? ` (${jy} is not a leap year)` : '';
    throw new RangeError(
      `${jy}/${pad(jm)}/${pad(jd)} does not exist: ${MONTH_NAMES_EN[jm - 1]} ${jy} has ${dim} days${why}`,
    );
  }
}

function pad(n: number): string {
  return n < 10 ? '0' + n : String(n);
}

/** Epoch day of 1 Farvardin of the given year. */
export function nowruzEpochDay(jy: number): number {
  assertYear(jy);
  return START[jy - MIN_YEAR];
}

/** Day of year (0-based) for a month/day. */
export function dayOfYearIndex(jm: number, jd: number): number {
  return jm <= 7 ? (jm - 1) * 31 + jd - 1 : 186 + (jm - 7) * 30 + jd - 1;
}

export function jalaliToEpochDay(jy: number, jm: number, jd: number): number {
  assertValidDate(jy, jm, jd);
  return START[jy - MIN_YEAR] + dayOfYearIndex(jm, jd);
}

export function epochDayToJalali(n: number): YMD {
  if (!isInt(n)) throw new TypeError(`Epoch day must be an integer, got ${String(n)}`);
  if (n < MIN_EPOCH_DAY || n > MAX_EPOCH_DAY) {
    throw new RangeError(`Date is outside the supported range (Jalali ${MIN_YEAR}–${MAX_YEAR})`);
  }
  let lo = 0;
  let hi = COUNT - 1;
  while (lo < hi) {
    const mid = (lo + hi + 1) >> 1;
    if (START[mid] <= n) lo = mid;
    else hi = mid - 1;
  }
  const doy = n - START[lo];
  if (doy < 186) return { year: MIN_YEAR + lo, month: Math.floor(doy / 31) + 1, day: (doy % 31) + 1 };
  const r = doy - 186;
  return { year: MIN_YEAR + lo, month: 7 + Math.floor(r / 30), day: (r % 30) + 1 };
}

// ---------------------------------------------------------------- Gregorian (proleptic)

export function isGregorianLeapYear(y: number): boolean {
  return (y % 4 === 0 && y % 100 !== 0) || y % 400 === 0;
}

export function gregorianDaysInMonth(y: number, m: number): number {
  return m === 2 ? (isGregorianLeapYear(y) ? 29 : 28) : [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][m - 1];
}

export function assertValidGregorian(gy: number, gm: number, gd: number): void {
  if (!isInt(gy) || !isInt(gm) || !isInt(gd)) throw new TypeError('Gregorian year, month and day must be integers');
  if (gm < 1 || gm > 12) throw new RangeError(`Gregorian month must be 1–12, got ${gm}`);
  const dim = gregorianDaysInMonth(gy, gm);
  if (gd < 1 || gd > dim) throw new RangeError(`${gy}-${pad(gm)}-${pad(gd)} does not exist in the Gregorian calendar`);
}

/** Howard Hinnant's days_from_civil. */
export function gregorianToEpochDay(gy: number, gm: number, gd: number): number {
  assertValidGregorian(gy, gm, gd);
  const y = gm <= 2 ? gy - 1 : gy;
  const era = Math.floor(y / 400);
  const yoe = y - era * 400;
  const mp = (gm + 9) % 12;
  const doy = Math.floor((153 * mp + 2) / 5) + gd - 1;
  const doe = yoe * 365 + Math.floor(yoe / 4) - Math.floor(yoe / 100) + doy;
  return era * 146097 + doe - 719468;
}

/** Howard Hinnant's civil_from_days. */
export function epochDayToGregorian(n: number): YMD {
  const z = n + 719468;
  const era = Math.floor(z / 146097);
  const doe = z - era * 146097;
  const yoe = Math.floor((doe - Math.floor(doe / 1460) + Math.floor(doe / 36524) - Math.floor(doe / 146096)) / 365);
  const doy = doe - (365 * yoe + Math.floor(yoe / 4) - Math.floor(yoe / 100));
  const mp = Math.floor((5 * doy + 2) / 153);
  const day = doy - Math.floor((153 * mp + 2) / 5) + 1;
  const month = mp < 10 ? mp + 3 : mp - 9;
  return { year: era * 400 + yoe + (month <= 2 ? 1 : 0), month, day };
}

/** Gregorian → Jalali. Throws RangeError for non-existent or out-of-range dates. */
export function toJalali(gy: number, gm: number, gd: number): YMD {
  return epochDayToJalali(gregorianToEpochDay(gy, gm, gd));
}

/** Jalali → Gregorian. Throws RangeError for non-existent dates such as 30 Esfand of a common year. */
export function toGregorian(jy: number, jm: number, jd: number): YMD {
  return epochDayToGregorian(jalaliToEpochDay(jy, jm, jd));
}

/** Gregorian date of Nowruz (1 Farvardin) of the given Jalali year. */
export function nowruz(jy: number): YMD {
  return epochDayToGregorian(nowruzEpochDay(jy));
}

/** 0 = Saturday (شنبه) … 6 = Friday (جمعه), the Iranian week. */
export function weekdayOfEpochDay(n: number): number {
  return (((n + 5) % 7) + 7) % 7;
}
