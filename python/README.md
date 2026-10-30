# taqvim — Persian (Jalali / Shamsi / Solar Hijri) calendar for JavaScript, TypeScript, Python and PHP

**[English](#english)** · **[فارسی](#راهنمای-فارسی)** · [راهنمای کامل فارسی](https://github.com/rahimixyz/persian-calendar-jalali-shamsi/blob/main/README.fa.md) · [Accuracy report](https://github.com/rahimixyz/persian-calendar-jalali-shamsi/blob/main/docs/ACCURACY.md) · [Nowruz table 1–2999](https://github.com/rahimixyz/persian-calendar-jalali-shamsi/blob/main/reference/nowruz.csv) · [llms.txt](https://github.com/rahimixyz/persian-calendar-jalali-shamsi/blob/main/llms.txt)

<div dir="rtl">

## راهنمای فارسی

**تقویم (taqvim)** کتابخانه‌ای دقیق و رایگان برای کار با **تاریخ شمسی** در برنامه‌نویسی است؛ با یک رفتار یکسان در **جاوااسکریپت/تایپ‌اسکریپت، پایتون و PHP**.

* **تبدیل تاریخ** شمسی ↔ میلادی، با سال‌های کبیسهٔ نجومی (نه فرمول تقریبی) برای سال ۱ تا ۲۹۹۹
* **رد تاریخ‌های ناموجود** مثل ۳۰ اسفند ۱۴۰۴، به‌جای تبدیل بی‌صدا به روز دیگر
* **خواندن تاریخ از متن فارسی:** `۱۵ فروردین ۱۴۰۴`، `شنبه ۱۶ فروردین ۱۴۰۴ ه.ش`، ارقام فارسی و عربی، «ي» و «ك» عربی
* **نمایش فارسی، دری (حمل، ثور…) و انگلیسی** با ارقام فارسی، عربی یا لاتین
* **ساعت درست تهران** بدون ساعت تابستانی، مستقل از تنظیمات سیستم‌عامل
* **تعطیلات رسمی ثابت شمسی** و محاسبهٔ روز کاری
* بدون هیچ وابستگی؛ مجوز آزاد MIT

### نصب

</div>

```bash
npm install taqvim                  # JavaScript / TypeScript
pip install taqvim                  # Python
composer require rahimixyz/taqvim   # PHP (Laravel و هر پروژهٔ PHP)
```

<div dir="rtl">

### نمونهٔ استفاده

</div>

```ts
// JavaScript / TypeScript
import { JalaliDate } from 'taqvim';
new JalaliDate(1404, 1, 15).format('dddd D MMMM YYYY');   // 'جمعه ۱۵ فروردین ۱۴۰۴'
JalaliDate.parse('۱۵ فروردین ۱۴۰۴').toISODate();          // '2025-04-04'
```

```python
# Python
from taqvim import JalaliDate
JalaliDate(1404, 1, 15).format('dddd D MMMM YYYY')         # 'جمعه ۱۵ فروردین ۱۴۰۴'
JalaliDate.parse('۱۵ فروردین ۱۴۰۴').to_date()              # datetime.date(2025, 4, 4)
```

```php
// PHP
use Taqvim\JalaliDate;
(new JalaliDate(1404, 1, 15))->format('dddd D MMMM YYYY'); // 'جمعه ۱۵ فروردین ۱۴۰۴'
JalaliDate::parse('۱۵ فروردین ۱۴۰۴')->toISODate();         // '2025-04-04'
```

<div dir="rtl">

📖 **[راهنمای کامل فارسی، همراه با توضیح همهٔ توابع و نمونه‌های بیشتر ←](https://github.com/rahimixyz/persian-calendar-jalali-shamsi/blob/main/README.fa.md)**

</div>

---

## English

**Accurate Iranian calendar library with the same API in three languages.** Convert between
Jalali and Gregorian dates, parse dates the way Iranians and Afghans actually type them,
format in Persian, Dari or English, do month arithmetic safely, and get Tehran time right.

| | JavaScript / TypeScript | Python | PHP |
|---|---|---|---|
| Package | [`taqvim`](https://www.npmjs.com/package/taqvim) (npm) | [`taqvim`](https://pypi.org/project/taqvim/) (PyPI) | `rahimixyz/taqvim` (Composer) |
| Source | [`js/`](https://github.com/rahimixyz/persian-calendar-jalali-shamsi/tree/main/js) | [`python/`](https://github.com/rahimixyz/persian-calendar-jalali-shamsi/tree/main/python) | [`php/`](https://github.com/rahimixyz/persian-calendar-jalali-shamsi/tree/main/php) |
| Dependencies | none | none | none |
| Runtimes | Node ≥ 18, browsers, Deno, Bun | Python ≥ 3.9 | PHP ≥ 8.1, Laravel, Symfony, WordPress |

> *Taqvim* (تقویم) means "calendar" in Persian. The calendar itself is called **Solar Hijri**,
> **Jalali**, **Shamsi**, **Iranian** or **Persian** calendar; it is the official calendar of
> Iran and Afghanistan.

## Why another Jalali library?

Existing libraries cover pieces of the problem, and several have bugs that matter
([full report](https://github.com/rahimixyz/persian-calendar-jalali-shamsi/blob/main/docs/ACCURACY.md)):

- **Wrong leap years.** Libraries using an arithmetic cycle drift from the real calendar.
  Python's `khayyam` puts Nowruz 1404 on 20 March 2025 (it was 21 March), so all of 1404 is
  one day off. `persian-date` is wrong for 1963. PHP's `verta` uses a fixed 33-year rule that
  gets 1634 and 1667 wrong. taqvim uses an **astronomical table** of the vernal equinox for
  years 1–2999, cross-checked against NASA JPL ephemeris DE421.
- **Silent rollover.** `1404/12/30` does not exist (1404 is not a leap year). Several libraries
  quietly turn it into 1 Farvardin 1405 or call it valid. taqvim **rejects** it with a clear message.
- **Persian digits and keyboards.** `۱۴۰۴/۰۱/۱۵` makes some libraries fail or return year 621.
  taqvim accepts Persian and Arabic digits, `ي`/`ك`, ZWNJ, RTL marks, month names, weekdays and `ه.ش`.
- **Tehran time.** Iran abolished daylight saving time in 2022. Devices, and PHP builds, with
  old tzdata still show the wrong hour. taqvim **embeds** the Asia/Tehran history.
- **Three ecosystems.** Front-end in JS and back-end in Python or PHP usually means several
  libraries with different rules. taqvim is one specification and one shared test suite
  (`reference/test-cases.json`) behind three packages.

## Install

```bash
npm install taqvim
pip install taqvim
composer require rahimixyz/taqvim
```

<details>
<summary>PHP: installing straight from GitHub (if the package is not found on Packagist)</summary>

Add the repository to your `composer.json`, then require the package:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/rahimixyz/persian-calendar-jalali-shamsi" }
    ],
    "require": { "rahimixyz/taqvim": "^1.0" }
}
```

Without Composer: copy the `php/` folder into your project and `require 'php/autoload.php';`.
</details>

## Quick start

**TypeScript / JavaScript**

```ts
import { JalaliDate, toJalali, toGregorian, formatDateTime } from 'taqvim';

toJalali(2025, 3, 21);            // { year: 1404, month: 1, day: 1 }
toGregorian(1403, 12, 30);        // { year: 2025, month: 3, day: 20 }

const d = new JalaliDate(1404, 1, 15);
d.format('dddd D MMMM YYYY');                     // 'جمعه ۱۵ فروردین ۱۴۰۴'
d.format('dddd D MMMM YYYY', { locale: 'en' });   // 'Friday 15 Farvardin 1404'
d.format('D MMMM YYYY', { locale: 'fa-AF' });     // '۱۵ حمل ۱۴۰۴'  (Afghanistan)
d.addMonths(1).toString();                        // '1404/02/15'
d.toISODate();                                    // '2025-04-04'

JalaliDate.parse('شنبه ۱۶ فروردین ۱۴۰۴ ه.ش');     // JalaliDate 1404/01/16
JalaliDate.today();                               // today in Tehran
formatDateTime(new Date(), 'YYYY/MM/DD HH:mm');   // '۱۴۰۵/۰۸/۰۹ ۱۷:۰۰'

new JalaliDate(1404, 12, 30);
// RangeError: 1404/12/30 does not exist: Esfand 1404 has 29 days (1404 is not a leap year)
```

**Python**

```python
from datetime import date, timedelta
from taqvim import JalaliDate, to_jalali, to_gregorian, format_datetime

to_jalali(2025, 3, 21)            # YMD(year=1404, month=1, day=1)
to_gregorian(1403, 12, 30)        # YMD(year=2025, month=3, day=20)

d = JalaliDate(1404, 1, 15)
d.format('dddd D MMMM YYYY')                # 'جمعه ۱۵ فروردین ۱۴۰۴'
d.format('dddd D MMMM YYYY', locale='en')   # 'Friday 15 Farvardin 1404'
d.add_months(1)                             # JalaliDate(1404, 2, 15)
d.to_date()                                 # datetime.date(2025, 4, 4)
JalaliDate.from_date(date(2025, 4, 4)) == d # True
d + timedelta(days=1)                       # JalaliDate(1404, 1, 16)

JalaliDate.parse('۱۵ فروردین ۱۴۰۴')         # JalaliDate(1404, 1, 15)
JalaliDate.today()                          # today in Tehran
```

**PHP**

```php
use Taqvim\JalaliDate;
use Taqvim\Taqvim;

Taqvim::toJalali(2025, 3, 21);      // ['year' => 1404, 'month' => 1, 'day' => 1]
Taqvim::toGregorian(1403, 12, 30);  // ['year' => 2025, 'month' => 3, 'day' => 20]

$d = new JalaliDate(1404, 1, 15);
$d->format('dddd D MMMM YYYY');          // 'جمعه ۱۵ فروردین ۱۴۰۴'
$d->format('dddd D MMMM YYYY', 'en');    // 'Friday 15 Farvardin 1404'
$d->addMonths(1)->toString();            // '1404/02/15'
$d->toDateTime()->format('Y-m-d H:i P'); // '2025-04-04 00:00 +03:30'

JalaliDate::parse('۱۵ فروردین ۱۴۰۴');     // JalaliDate 1404/01/15
JalaliDate::fromDateTime(new DateTime()); // today's date in Tehran
Taqvim::formatDateTime(time(), 'YYYY/MM/DD HH:mm');

new JalaliDate(1404, 12, 30);
// Taqvim\InvalidDateException: 1404/12/30 does not exist: Esfand 1404 has 29 days (1404 is not a leap year)
```

**Laravel / Eloquent example:** store dates as normal `DATE` columns and convert at the edges.

```php
// Display a model's created_at in Persian
JalaliDate::fromDateTime($order->created_at)->format('D MMMM YYYY');

// Accept a Persian date from a form and save it as a Gregorian date
$order->delivery_date = JalaliDate::parse($request->input('delivery_date'))->toISODate();
```

## API

Names are camelCase in JavaScript and PHP and snake_case in Python; behaviour is identical.

### Conversion and calendar facts

| JavaScript | Python | PHP | |
|---|---|---|---|
| `toJalali(gy, gm, gd)` | `to_jalali(gy, gm, gd)` | `Taqvim::toJalali(...)` | Gregorian → Jalali |
| `toGregorian(jy, jm, jd)` | `to_gregorian(jy, jm, jd)` | `Taqvim::toGregorian(...)` | Jalali → Gregorian |
| `isLeapYear(jy)` | `is_leap_year(jy)` | `Taqvim::isLeapYear(jy)` | 366-day year? |
| `daysInMonth(jy, jm)` | `days_in_month(jy, jm)` | `Taqvim::daysInMonth(...)` | 29–31 |
| `isValidDate(jy, jm, jd)` | `is_valid_date(jy, jm, jd)` | `Taqvim::isValidDate(...)` | never throws |
| `nowruz(jy)` | `nowruz(jy)` | `Taqvim::nowruz(jy)` | Gregorian date of 1 Farvardin |
| `isUncertainYear(jy)` | `is_uncertain_year(jy)` | `Taqvim::isUncertainYear(jy)` | astronomically borderline year ([why](https://github.com/rahimixyz/persian-calendar-jalali-shamsi/blob/main/docs/ACCURACY.md)) |
| `MIN_YEAR`, `MAX_YEAR` | same | `Calendar::MIN_YEAR`, `MAX_YEAR` | 1 and 2999 |

### `JalaliDate` (immutable)

| JavaScript | Python | PHP | |
|---|---|---|---|
| `new JalaliDate(y, m, d)` | `JalaliDate(y, m, d)` | `new JalaliDate(y, m, d)` | throws on non-existent dates |
| `JalaliDate.fromGregorian(y, m, d)` | `from_gregorian(...)` / `from_date(date)` | `JalaliDate::fromGregorian(...)` | |
| `JalaliDate.fromDate(date, {timeZone})` | `from_datetime(dt, time_zone)` | `JalaliDate::fromDateTime(dt, tz)` | date of an instant; default `Asia/Tehran` |
| `JalaliDate.today()` | `JalaliDate.today()` | `JalaliDate::today()` | |
| `JalaliDate.parse(text)` / `tryParse` | `parse` / `try_parse` | `parse` / `tryParse` | see parsing |
| `.year .month .day` | same | `->year ->month ->day` | |
| `.dayOfWeek` | `.weekday` | `->dayOfWeek()` | 0 = Saturday … 6 = Friday |
| `.dayOfYear .weekOfYear .season` | `.day_of_year .week_of_year .season` | `->dayOfYear()` … | weeks start on Saturday |
| `.addDays/addWeeks/addMonths/addYears(n)` | `.add_days/...` | `->addDays(...)` … | month/year arithmetic clamps to month end |
| `.startOfMonth() .endOfMonth() .startOfYear() .endOfYear() .startOfWeek() .endOfWeek()` | snake_case | same names | |
| `.diffInDays/diffInMonths/diffInYears(other)` | snake_case | same names | whole units |
| `.equals .isBefore .isAfter .isBetween`, `<` `>` | `==` `<` `>`, `is_between` | `->equals()` … , `JalaliDate::compare` | |
| `.toGregorian() .toISODate() .toDate({hour, timeZone})` | `.to_gregorian() .to_date() .to_datetime(...)` | `->toGregorian() ->toISODate() ->toDateTime(...)` | |
| `.format(pattern, {locale, digits})` | `.format(pattern, locale, digits)` | `->format(pattern, locale, digits)` | |

### Formatting tokens

`YYYY` 1404 · `YY` 04 · `M` 1 · `MM` 01 · `MMMM` فروردین · `D` 5 · `DD` 05 · `DDD` day of year ·
`d` weekday 1–7 (Saturday = 1) · `ddd` ش · `dddd` شنبه · `w`/`ww` week · `Q` season 1–4 ·
`QQQQ` بهار · `H HH h hh m mm s ss A` time (with `formatDateTime` / `format_datetime`) ·
`Z` +03:30 · `ZZ` +0330 · `[text]` literal.
Locales: `fa` (default), `fa-AF` (Dari month names), `en`. Digits: `persian` (default for fa),
`latin` (default for en), `arabic`.

### Parsing

Accepted: `1404/01/15`, `1404-1-15`, `1404.01.15`, `15/01/1404`, `۱۴۰۴/۰۱/۱۵`,
`١٤٠٤/٠١/١٥`, `۱۵ فروردین ۱۴۰۴`, `شنبه ۱۶ فروردین ۱۴۰۴ ه.ش`, `۲۰ امرداد ۱۴۰۳`,
`15 Farvardin 1404`, `Farvardin 15, 1404`, `۱۵ حمل ۱۴۰۴`. Two-digit years are rejected
rather than guessed. Errors say exactly what is wrong.

### Time zones and date-times

`toJalaliParts(instant)`, `fromJalaliParts(...)`, `formatDateTime(instant, pattern)` in JS and
PHP (`Taqvim::…`); `format_datetime(dt, pattern)`, `TEHRAN` (a `tzinfo`), `to_zone(dt)` in
Python. `Asia/Tehran` comes from embedded tzdata; any other IANA zone works through the host.
Non-existent local times move forward; ambiguous ones resolve to the earlier instant.

### Holidays and business days

`solarHolidays(year)`, `isHoliday(date, …)`, `isWeekend`, `addBusinessDays` (PHP: `Holidays::…`).
Covers Iran's **fixed solar** public holidays (Nowruz 1–4 Farvardin, 12 and 13 Farvardin,
14 and 15 Khordad, 22 Bahman, 29 Esfand). Islamic lunar holidays are announced each year by
moon sighting and cannot be computed in advance — pass them in `extra`. Weekend defaults to Friday.

### Utilities

`toPersianDigits`, `toLatinDigits`, `convertDigits`, `normalizePersian` (Python: snake_case; PHP: `Locale::…`).

## Limits, stated plainly

- Supported years: **1–2999 AP** (622–3621 CE). Dates before 1304 AP (1925) are proleptic.
- A few years are astronomically borderline; the next one is **1470 AP (2091 CE)**. They are
  flagged by `isUncertainYear`, never hidden.
- Lunar Hijri holidays are not computed (see above).

## Development

```bash
cd js && npm install && npm test          # builds, then runs every day 1–2999 + shared cases
cd python && PYTHONPATH=src python -m unittest discover -s tests
php php/tests/run.php                     # or: composer test
```

Rebuild the astronomical data: `python scripts/equinox.py && python scripts/build_reference.py && python scripts/generate_data.py`.
Reproduce the comparison: see [`scripts/compare`](https://github.com/rahimixyz/persian-calendar-jalali-shamsi/tree/main/scripts/compare).

## License

MIT © 2026 Alireza Rahimi

<sub>Keywords: Jalali calendar, Shamsi date, Persian calendar, Solar Hijri, Iranian calendar,
Afghan calendar, Hijri Shamsi, Farsi date, Nowruz, convert Jalali to Gregorian, Laravel Jalali,
PHP Persian date, تاریخ شمسی، تقویم جلالی، تبدیل تاریخ شمسی به میلادی، هجری خورشیدی، تاریخ شمسی در لاراول.</sub>
