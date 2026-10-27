import datetime as dt
import json
import pathlib
import pickle
import unittest

import taqvim
from taqvim import (JalaliDate, add_business_days, days_in_month, format_datetime, is_holiday, is_leap_year,
                    is_uncertain_year, is_valid_date, normalize_persian, nowruz, parse_jalali, solar_holidays,
                    to_gregorian, to_jalali, to_latin_digits, to_persian_digits)

ROOT = pathlib.Path(__file__).resolve().parents[2] / 'reference'
REF = json.loads((ROOT / 'nowruz.json').read_text())
CASES = json.loads((ROOT / 'test-cases.json').read_text(encoding='utf-8'))
UTC = dt.timezone.utc


class Reference(unittest.TestCase):
    def test_nowruz_every_year(self):
        for r in REF:
            self.assertEqual(dt.date(*nowruz(r['jy'])).isoformat(), r['nowruz'], r['jy'])
            self.assertEqual(is_leap_year(r['jy']), r['leap'], r['jy'])
            self.assertEqual(is_uncertain_year(r['jy']), r['uncertain'], r['jy'])

    def test_round_trip_every_day(self):
        n = JalaliDate(taqvim.MIN_YEAR, 1, 1).epoch_day
        end = JalaliDate(taqvim.MAX_YEAR, 12, days_in_month(taqvim.MAX_YEAR, 12)).epoch_day
        prev = None
        for k in range(n, end + 1):
            y, m, d = taqvim.epoch_day_to_jalali(k)
            if prev:
                py, pm, pd = prev
                exp = (py, pm, pd + 1) if pd < days_in_month(py, pm) else (py, pm + 1, 1) if pm < 12 else (py + 1, 1, 1)
                self.assertEqual((y, m, d), exp)
            g = taqvim.epoch_day_to_gregorian(k)
            self.assertEqual(tuple(to_jalali(*g)), (y, m, d))
            prev = (y, m, d)


class Behaviour(unittest.TestCase):
    def test_conversions(self):
        for c in CASES['conversions']:
            self.assertEqual(list(to_gregorian(*c['jalali'])), c['gregorian'], c.get('note'))
            self.assertEqual(list(to_jalali(*c['gregorian'])), c['jalali'])

    def test_leap_years(self):
        for y in CASES['leap_years']['leap']:
            self.assertTrue(is_leap_year(y), y)
        for y in CASES['leap_years']['common']:
            self.assertFalse(is_leap_year(y), y)

    def test_invalid_dates(self):
        for d in CASES['invalid_dates']:
            self.assertFalse(is_valid_date(*d))
            with self.assertRaises(ValueError):
                JalaliDate(*d)
        with self.assertRaisesRegex(ValueError, '1404 is not a leap year'):
            JalaliDate(1404, 12, 30)
        with self.assertRaises(ValueError):
            to_jalali(2025, 2, 29)

    def test_parse(self):
        for c in CASES['parse']:
            if c.get('error'):
                with self.assertRaises(ValueError, msg=c['in']):
                    parse_jalali(c['in'])
                self.assertIsNone(JalaliDate.try_parse(c['in']))
            else:
                self.assertEqual(list(parse_jalali(c['in'])), c['out'], c['in'])

    def test_format(self):
        for c in CASES['format']:
            d = JalaliDate(*c['date'])
            self.assertEqual(d.format(c['pattern'], c.get('locale', 'fa'), c.get('digits')), c['out'], c['pattern'])

    def test_arithmetic(self):
        for c in CASES['arithmetic']:
            r = getattr(JalaliDate(*c['date']), c['op'])(c['n'])
            self.assertEqual([r.year, r.month, r.day], c['out'], c)
        for c in CASES['diff']:
            a, b = JalaliDate(*c['a']), JalaliDate(*c['b'])
            if 'months' in c:
                self.assertEqual(a.diff_in_months(b), c['months'])
            if 'years' in c:
                self.assertEqual(a.diff_in_years(b), c['years'])
            if 'days' in c:
                self.assertEqual(a.diff_in_days(b), c['days'])
                self.assertEqual((a - b).days, c['days'])

    def test_timezone(self):
        for c in CASES['timezone']:
            moment = dt.datetime.fromisoformat(c['utc'].replace('Z', '+00:00'))
            local = moment.astimezone(taqvim.TEHRAN)
            j = JalaliDate.from_datetime(moment)
            self.assertEqual([j.year, j.month, j.day], c['jalali'], c['utc'])
            self.assertEqual([local.hour, local.minute, local.second], c['time'], c['utc'])
            self.assertEqual(int(local.utcoffset().total_seconds() // 60), c['offset'], c['utc'])
            self.assertEqual(local.astimezone(UTC), moment)
        m = dt.datetime(2025, 3, 20, 20, 30, tzinfo=UTC)
        self.assertEqual(format_datetime(m, 'YYYY/MM/DD HH:mm Z', 'en'), '1404/01/01 00:00 +03:30')
        self.assertEqual(str(JalaliDate.from_datetime(m, 'UTC')), '1403/12/30')
        self.assertEqual(JalaliDate(1404, 1, 1).to_datetime().astimezone(UTC), m)

    def test_properties(self):
        d = JalaliDate(1404, 1, 15)
        self.assertEqual(d.weekday, 6)
        self.assertEqual(d.day_of_year, 15)
        self.assertEqual(JalaliDate(1403, 12, 30).day_of_year, 366)
        self.assertEqual(str(d.start_of_week()), '1404/01/09')
        self.assertEqual(str(d.end_of_week()), '1404/01/15')
        self.assertEqual(str(JalaliDate(1404, 12, 1).end_of_month()), '1404/12/29')
        self.assertEqual(d.to_date(), dt.date(2025, 4, 4))
        self.assertEqual(JalaliDate.from_date(dt.date(2025, 4, 4)), d)
        self.assertEqual(d + dt.timedelta(days=1), JalaliDate(1404, 1, 16))
        self.assertLess(JalaliDate(1404, 1, 1), JalaliDate(1404, 1, 2))
        self.assertEqual(len({d, JalaliDate(1404, 1, 15)}), 1)
        self.assertEqual(pickle.loads(pickle.dumps(d)), d)
        with self.assertRaises(AttributeError):
            d.year = 1405

    def test_holidays(self):
        self.assertEqual([str(h.date) for h in solar_holidays(1404)], CASES['holidays_1404'])
        self.assertTrue(is_holiday(JalaliDate(1404, 1, 15)))
        self.assertFalse(is_holiday(JalaliDate(1404, 1, 16)))
        self.assertTrue(is_holiday(JalaliDate(1404, 1, 16), extra=[JalaliDate(1404, 1, 16)]))
        self.assertEqual(str(add_business_days(JalaliDate(1403, 12, 28), 1)), '1403/12/30')

    def test_digits(self):
        self.assertEqual(to_persian_digits('1404/01/15'), '۱۴۰۴/۰۱/۱۵')
        self.assertEqual(to_latin_digits('۱۴۰۴/٠١/۱۵'), '1404/01/15')
        self.assertEqual(normalize_persian('علي ك‌تاب'), 'علی کتاب')


if __name__ == '__main__':
    unittest.main()
