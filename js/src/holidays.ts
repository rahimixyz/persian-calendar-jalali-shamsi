import { JalaliDate } from './date.js';

export interface Holiday {
  date: JalaliDate;
  fa: string;
  en: string;
}

/**
 * Official public holidays of Iran that fall on fixed Solar Hijri dates.
 * Islamic (lunar Hijri) holidays are NOT included: Iran fixes them each year by moon
 * sighting, so they cannot be computed reliably in advance. Pass them in via `extra`.
 */
export const FIXED_SOLAR_HOLIDAYS: readonly { month: number; day: number; fa: string; en: string }[] = [
  { month: 1, day: 1, fa: 'جشن نوروز', en: 'Nowruz' },
  { month: 1, day: 2, fa: 'عید نوروز', en: 'Nowruz' },
  { month: 1, day: 3, fa: 'عید نوروز', en: 'Nowruz' },
  { month: 1, day: 4, fa: 'عید نوروز', en: 'Nowruz' },
  { month: 1, day: 12, fa: 'روز جمهوری اسلامی', en: 'Islamic Republic Day' },
  { month: 1, day: 13, fa: 'روز طبیعت', en: 'Nature Day (Sizdah Bedar)' },
  { month: 3, day: 14, fa: 'رحلت امام خمینی', en: 'Death of Ayatollah Khomeini' },
  { month: 3, day: 15, fa: 'قیام ۱۵ خرداد', en: '15 Khordad Uprising' },
  { month: 11, day: 22, fa: 'پیروزی انقلاب اسلامی', en: 'Revolution Day' },
  { month: 12, day: 29, fa: 'ملی شدن صنعت نفت', en: 'Oil Nationalisation Day' },
];

/** Fixed solar public holidays of a Jalali year, in date order. */
export function solarHolidays(year: number): Holiday[] {
  return FIXED_SOLAR_HOLIDAYS.map((h) => ({ date: new JalaliDate(year, h.month, h.day), fa: h.fa, en: h.en }));
}

export interface HolidayOptions {
  /** Extra holiday dates, e.g. this year's officially announced lunar holidays. */
  extra?: readonly JalaliDate[];
  /** Weekend days, 0 = Saturday … 6 = Friday. Default [6] (Friday). */
  weekendDays?: readonly number[];
}

export function isWeekend(date: JalaliDate, opts: HolidayOptions = {}): boolean {
  return (opts.weekendDays ?? [6]).includes(date.dayOfWeek);
}

/** Fixed solar holiday, weekend, or one of `extra`. */
export function isHoliday(date: JalaliDate, opts: HolidayOptions = {}): boolean {
  if (isWeekend(date, opts)) return true;
  if (FIXED_SOLAR_HOLIDAYS.some((h) => h.month === date.month && h.day === date.day)) return true;
  return (opts.extra ?? []).some((d) => d.equals(date));
}

/** Add business days, skipping weekends and holidays. */
export function addBusinessDays(date: JalaliDate, days: number, opts: HolidayOptions = {}): JalaliDate {
  if (!Number.isInteger(days)) throw new TypeError('days must be an integer');
  const step = days < 0 ? -1 : 1;
  let d = date;
  let left = Math.abs(days);
  while (left > 0) {
    d = d.addDays(step);
    if (!isHoliday(d, opts)) left--;
  }
  return d;
}
