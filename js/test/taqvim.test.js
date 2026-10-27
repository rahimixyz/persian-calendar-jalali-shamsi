import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import {
  JalaliDate, toJalali, toGregorian, isLeapYear, isValidDate, isUncertainYear, nowruz, daysInMonth,
  parseJalali, formatDateTime, toJalaliParts, fromJalaliParts, solarHolidays, isHoliday, addBusinessDays,
  toPersianDigits, toLatinDigits, normalizePersian, MIN_YEAR, MAX_YEAR, offsetMinutes,
} from '../dist/esm/index.js';

const ref = JSON.parse(readFileSync(new URL('../../reference/nowruz.json', import.meta.url)));
const cases = JSON.parse(readFileSync(new URL('../../reference/test-cases.json', import.meta.url)));
const iso = (y, m, d) => `${String(y).padStart(4, '0')}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;

test('Nowruz of every year 1–2999 matches the astronomical reference', () => {
  for (const r of ref) {
    const g = nowruz(r.jy);
    assert.equal(iso(g.year, g.month, g.day), r.nowruz, `year ${r.jy}`);
    assert.equal(isLeapYear(r.jy), r.leap, `leap ${r.jy}`);
    assert.equal(isUncertainYear(r.jy), r.uncertain, `uncertain ${r.jy}`);
  }
});

test('round trip of every supported day', () => {
  let n = new JalaliDate(MIN_YEAR, 1, 1).epochDay;
  const end = new JalaliDate(MAX_YEAR, 12, daysInMonth(MAX_YEAR, 12)).epochDay;
  let prev = null;
  for (; n <= end; n++) {
    const j = JalaliDate.fromEpochDay(n);
    if (prev) {
      // consecutive days must step by exactly one calendar day
      const expectNext = prev.day < prev.daysInMonth() ? [prev.year, prev.month, prev.day + 1]
        : prev.month < 12 ? [prev.year, prev.month + 1, 1] : [prev.year + 1, 1, 1];
      assert.deepEqual([j.year, j.month, j.day], expectNext);
    }
    const g = j.toGregorian();
    const back = toJalali(g.year, g.month, g.day);
    assert.deepEqual([back.year, back.month, back.day], [j.year, j.month, j.day]);
    prev = j;
  }
});

test('known conversions', () => {
  for (const c of cases.conversions) {
    const g = toGregorian(...c.jalali);
    assert.deepEqual([g.year, g.month, g.day], c.gregorian, c.note);
    const j = toJalali(...c.gregorian);
    assert.deepEqual([j.year, j.month, j.day], c.jalali);
  }
});

test('leap years', () => {
  for (const y of cases.leap_years.leap) assert.equal(isLeapYear(y), true, String(y));
  for (const y of cases.leap_years.common) assert.equal(isLeapYear(y), false, String(y));
});

test('invalid dates are rejected, never rolled over', () => {
  for (const d of cases.invalid_dates) {
    assert.equal(isValidDate(...d), false, d.join('/'));
    assert.throws(() => new JalaliDate(...d), RangeError);
    assert.equal(JalaliDate.isValid(...d), false);
  }
  assert.throws(() => new JalaliDate(1404, 12, 30), /1404 is not a leap year/);
  assert.throws(() => toGregorian(1404, 12, 30), RangeError);
  assert.throws(() => toJalali(2025, 2, 29), RangeError);
});

test('parse', () => {
  for (const c of cases.parse) {
    if (c.error) {
      assert.throws(() => parseJalali(c.in), RangeError, c.in);
      assert.equal(JalaliDate.tryParse(c.in), null);
    } else {
      const r = parseJalali(c.in);
      assert.deepEqual([r.year, r.month, r.day], c.out, c.in);
    }
  }
});

test('format', () => {
  for (const c of cases.format) {
    const d = new JalaliDate(...c.date);
    const opts = {};
    if (c.locale) opts.locale = c.locale;
    if (c.digits) opts.digits = c.digits;
    assert.equal(d.format(c.pattern, opts), c.out, c.pattern);
  }
});

test('arithmetic clamps to month end', () => {
  const ops = { add_days: 'addDays', add_months: 'addMonths', add_years: 'addYears' };
  for (const c of cases.arithmetic) {
    const r = new JalaliDate(...c.date)[ops[c.op]](c.n);
    assert.deepEqual([r.year, r.month, r.day], c.out, `${c.date} ${c.op} ${c.n}`);
  }
  for (const c of cases.diff) {
    const a = new JalaliDate(...c.a);
    const b = new JalaliDate(...c.b);
    if ('months' in c) assert.equal(a.diffInMonths(b), c.months);
    if ('years' in c) assert.equal(a.diffInYears(b), c.years);
    if ('days' in c) assert.equal(a.diffInDays(b), c.days);
  }
});

test('Asia/Tehran from embedded tzdata', () => {
  for (const c of cases.timezone) {
    const p = toJalaliParts(new Date(c.utc));
    assert.deepEqual([p.year, p.month, p.day], c.jalali, c.utc);
    assert.deepEqual([p.hour, p.minute, p.second], c.time, c.utc);
    assert.equal(p.offset, c.offset);
    assert.equal(offsetMinutes(Date.parse(c.utc), 'Asia/Tehran'), c.offset);
    const back = fromJalaliParts({ year: p.year, month: p.month, day: p.day, hour: p.hour, minute: p.minute, second: p.second });
    if (!c.ambiguous) assert.equal(back.toISOString().replace('.000', ''), c.utc);
  }
  assert.equal(formatDateTime(new Date('2025-03-20T20:30:00Z'), 'YYYY/MM/DD HH:mm Z', { locale: 'en' }), '1404/01/01 00:00 +03:30');
  assert.equal(JalaliDate.fromDate(new Date('2025-03-20T20:30:00Z')).toString(), '1404/01/01');
  assert.equal(JalaliDate.fromDate(new Date('2025-03-20T20:30:00Z'), { timeZone: 'UTC' }).toString(), '1403/12/30');
  assert.equal(new JalaliDate(1404, 1, 1).toDate().toISOString(), '2025-03-20T20:30:00.000Z');
});

test('properties', () => {
  const d = new JalaliDate(1404, 1, 15);
  assert.equal(d.dayOfWeek, 6);
  assert.equal(d.dayOfYear, 15);
  assert.equal(d.season, 1);
  assert.equal(new JalaliDate(1403, 12, 30).dayOfYear, 366);
  assert.equal(d.startOfWeek().toString(), '1404/01/09');
  assert.equal(d.endOfWeek().toString(), '1404/01/15');
  assert.equal(new JalaliDate(1404, 12, 1).endOfMonth().toString(), '1404/12/29');
  assert.equal(d.toISODate(), '2025-04-04');
  assert.equal(JSON.stringify({ d }), '{"d":"1404/01/15"}');
  assert.ok(new JalaliDate(1404, 1, 1) < new JalaliDate(1404, 1, 2));
  assert.equal(Object.isFrozen(d), true);
});

test('holidays and business days', () => {
  assert.deepEqual(solarHolidays(1404).map((h) => h.date.toString()), cases.holidays_1404);
  assert.equal(isHoliday(new JalaliDate(1404, 1, 15)), true); // Friday
  assert.equal(isHoliday(new JalaliDate(1404, 1, 16)), false);
  assert.equal(isHoliday(new JalaliDate(1404, 1, 16), { extra: [new JalaliDate(1404, 1, 16)] }), true);
  // Wed 1403/12/29 is a holiday; next business day after Thu 1403/12/28 skips 29 (holiday), 30, 1–4 Farvardin...
  assert.equal(addBusinessDays(new JalaliDate(1403, 12, 28), 1).toString(), '1403/12/30');
});

test('digits and normalisation', () => {
  assert.equal(toPersianDigits('1404/01/15'), '۱۴۰۴/۰۱/۱۵');
  assert.equal(toLatinDigits('۱۴۰۴/٠١/۱۵'), '1404/01/15');
  assert.equal(normalizePersian('علي ك‌تاب'), 'علی کتاب');
});
