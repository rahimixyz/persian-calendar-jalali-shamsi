import { assertValidDate, YMD } from './core.js';
import { normalizePersian } from './locale.js';

export interface ParseOptions {
  /**
   * How to read an all-numeric date whose year cannot be recognised by its 3–4 digits
   * (never guessed by default). 'ymd' and 'dmy' only apply when both are possible.
   */
  order?: 'ymd' | 'dmy';
}

const MONTH_ALIASES: [string, number][] = [];
const add = (m: number, ...names: string[]) => names.forEach((n) => MONTH_ALIASES.push([n, m]));
add(1, 'فروردین', 'farvardin', 'حمل', 'hamal');
add(2, 'اردیبهشت', 'ordibehesht', 'ordibehest', 'ثور', 'sawr', 'saur', 'sowr');
add(3, 'خرداد', 'khordad', 'khordaad', 'جوزا', 'جوزاء', 'jawza', 'jowza', 'jauza');
add(4, 'تیر', 'tir', 'سرطان', 'saratan');
add(5, 'مرداد', 'امرداد', 'mordad', 'amordad', 'mordaad', 'اسد', 'asad');
add(6, 'شهریور', 'shahrivar', 'shahrivar', 'سنبله', 'sonbola', 'sunbula', 'sombola');
add(7, 'مهر', 'mehr', 'میزان', 'mizan');
add(8, 'آبان', 'ابان', 'aban', 'aaban', 'عقرب', 'aqrab', 'akrab');
add(9, 'آذر', 'اذر', 'azar', 'aazar', 'قوس', 'qaws', 'qaus', 'qows');
add(10, 'دی', 'dey', 'dei', 'جدی', 'jadi', 'jady');
add(11, 'بهمن', 'bahman', 'دلو', 'dalw', 'dalv');
add(12, 'اسفند', 'esfand', 'espand', 'حوت', 'hut', 'hoot');
// longest first so "امرداد" wins over "مرداد" and "جدی" over "دی"
MONTH_ALIASES.sort((a, b) => b[0].length - a[0].length);

const WEEKDAY_RE = /(?<!\p{L})(?:(?:(?:یک|دو|سه|چهار|پنج)\s?)?شنبه|جمعه|saturday|sunday|monday|tuesday|wednesday|thursday|friday|sat|sun|mon|tue|wed|thu|fri)(?!\p{L})/giu;
const ERA_RE = /(?<!\p{L})(?:(?:هجری\s?)?(?:شمسی|خورشیدی)|ه\s?\.?\s?ش\.?|h\.?\s?sh\.?|s\.?h\.?|a\.?p\.?|sh|سال|ماه|روز)(?!\p{L})/giu;

function fail(input: string, why: string): never {
  throw new RangeError(`Cannot parse "${input}" as a Jalali date: ${why}`);
}

/**
 * Parse a Jalali date written the way people write it:
 * "1404/01/15", "۱۴۰۴-۱-۱۵", "15/01/1404", "۱۵ فروردین ۱۴۰۴", "شنبه ۱۵ فروردین ۱۴۰۴ ه.ش",
 * "15 Farvardin 1404", "Farvardin 15, 1404", "۱۵ حمل ۱۴۰۴" (Dari month names).
 * Persian/Arabic digits, Arabic ي/ك, ZWNJ and RTL marks are all accepted. The date is
 * validated strictly: "1404/12/30" throws because 1404 is not a leap year.
 */
export function parseJalali(input: string, opts: ParseOptions = {}): YMD {
  if (typeof input !== 'string') throw new TypeError('parseJalali expects a string');
  let s = normalizePersian(input).toLowerCase();
  s = s.replace(WEEKDAY_RE, ' ').replace(ERA_RE, ' ');
  s = s.replace(/(\d+)\s?(?:ام|م|th|st|nd|rd)(?!\p{L})/gu, '$1');
  s = s.replace(/[,،]/g, ' ').replace(/\s+/g, ' ').trim();
  if (!s) fail(input, 'empty input');

  let month: number | undefined;
  for (const [name, m] of MONTH_ALIASES) {
    const re = new RegExp(`(^|[^\\p{L}])${name}(?=$|[^\\p{L}])`, 'u');
    if (re.test(s)) {
      month = m;
      s = s.replace(re, '$1 ');
      break;
    }
  }

  let year: number;
  let day: number;
  if (month !== undefined) {
    const nums = (s.match(/\d+/g) ?? []);
    if (/[^\d\s/.\-]/.test(s)) fail(input, 'unexpected text');
    if (nums.length !== 2) fail(input, 'expected a day and a year next to the month name');
    const [a, b] = nums;
    if (a.length >= 3 && b.length <= 2) { year = +a; day = +b; }
    else if (b.length >= 3 && a.length <= 2) { year = +b; day = +a; }
    else fail(input, 'the year must have 3–4 digits');
  } else {
    const m = /^(\d{1,4})\s*[/.\-\s]\s*(\d{1,2})\s*[/.\-\s]\s*(\d{1,4})$/.exec(s);
    if (!m) fail(input, 'unrecognised format');
    const [, a, b, c] = m;
    if (a.length >= 3 && c.length <= 2) { year = +a; month = +b; day = +c; }
    else if (c.length >= 3 && a.length <= 2) { year = +c; month = +b; day = +a; }
    else if (opts.order === 'ymd') { year = +a; month = +b; day = +c; }
    else if (opts.order === 'dmy') { year = +c; month = +b; day = +a; }
    else fail(input, 'cannot tell the year from the day; write the year with 4 digits');
  }
  try {
    assertValidDate(year, month, day);
  } catch (e) {
    fail(input, (e as Error).message);
  }
  return { year, month, day };
}
