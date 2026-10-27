"""Parse Jalali dates the way people actually write them (same rules as the JS package)."""
from __future__ import annotations

import re

from .core import YMD, check_date
from .locale import normalize_persian

_L = r'[^\W\d_]'  # any letter
_ALIASES: list[tuple[str, int]] = []


def _add(m: int, *names: str) -> None:
    _ALIASES.extend((n, m) for n in names)


_add(1, 'فروردین', 'farvardin', 'حمل', 'hamal')
_add(2, 'اردیبهشت', 'ordibehesht', 'ordibehest', 'ثور', 'sawr', 'saur', 'sowr')
_add(3, 'خرداد', 'khordad', 'khordaad', 'جوزا', 'جوزاء', 'jawza', 'jowza', 'jauza')
_add(4, 'تیر', 'tir', 'سرطان', 'saratan')
_add(5, 'مرداد', 'امرداد', 'mordad', 'amordad', 'mordaad', 'اسد', 'asad')
_add(6, 'شهریور', 'shahrivar', 'سنبله', 'sonbola', 'sunbula', 'sombola')
_add(7, 'مهر', 'mehr', 'میزان', 'mizan')
_add(8, 'آبان', 'ابان', 'aban', 'aaban', 'عقرب', 'aqrab', 'akrab')
_add(9, 'آذر', 'اذر', 'azar', 'aazar', 'قوس', 'qaws', 'qaus', 'qows')
_add(10, 'دی', 'dey', 'dei', 'جدی', 'jadi', 'jady')
_add(11, 'بهمن', 'bahman', 'دلو', 'dalw', 'dalv')
_add(12, 'اسفند', 'esfand', 'espand', 'حوت', 'hut', 'hoot')
_ALIASES.sort(key=lambda a: -len(a[0]))
_MONTH_RES = [(re.compile(rf'(?<!{_L}){re.escape(n)}(?!{_L})'), m) for n, m in _ALIASES]

_WEEKDAY_RE = re.compile(rf'(?<!{_L})(?:(?:(?:یک|دو|سه|چهار|پنج)\s?)?شنبه|جمعه|saturday|sunday|monday|tuesday|wednesday'
                         rf'|thursday|friday|sat|sun|mon|tue|wed|thu|fri)(?!{_L})')
_ERA_RE = re.compile(rf'(?<!{_L})(?:(?:هجری\s?)?(?:شمسی|خورشیدی)|ه\s?\.?\s?ش\.?|h\.?\s?sh\.?|s\.?h\.?|a\.?p\.?|sh|سال|ماه|روز)'
                     rf'(?!{_L})')
_ORDINAL_RE = re.compile(rf'(\d+)\s?(?:ام|م|th|st|nd|rd)(?!{_L})')
_NUMERIC_RE = re.compile(r'^(\d{1,4})\s*[/.\-\s]\s*(\d{1,2})\s*[/.\-\s]\s*(\d{1,4})$')


def _fail(text: str, why: str):
    raise ValueError(f'Cannot parse {text!r} as a Jalali date: {why}')


def parse_jalali(text: str, order: str | None = None) -> YMD:
    """Parse "1404/01/15", "۱۴۰۴-۱-۱۵", "15/01/1404", "۱۵ فروردین ۱۴۰۴", "شنبه ۱۵ فروردین ۱۴۰۴ ه.ش",
    "15 Farvardin 1404", "Farvardin 15, 1404" or "۱۵ حمل ۱۴۰۴" (Dari). Validates strictly.

    ``order`` ('ymd' or 'dmy') is only used when the year cannot be recognised by its 3-4 digits.
    """
    if not isinstance(text, str):
        raise TypeError('parse_jalali expects a string')
    s = normalize_persian(text).lower()
    s = _WEEKDAY_RE.sub(' ', s)
    s = _ERA_RE.sub(' ', s)
    s = _ORDINAL_RE.sub(r'\1', s)
    s = re.sub(r'\s+', ' ', re.sub('[,،]', ' ', s)).strip()
    if not s:
        _fail(text, 'empty input')

    month = None
    for rx, m in _MONTH_RES:
        if rx.search(s):
            month = m
            s = rx.sub(' ', s, count=1)
            break

    if month is not None:
        if re.search(r'[^\d\s/.\-]', s):
            _fail(text, 'unexpected text')
        nums = re.findall(r'\d+', s)
        if len(nums) != 2:
            _fail(text, 'expected a day and a year next to the month name')
        a, b = nums
        if len(a) >= 3 and len(b) <= 2:
            year, day = int(a), int(b)
        elif len(b) >= 3 and len(a) <= 2:
            year, day = int(b), int(a)
        else:
            _fail(text, 'the year must have 3-4 digits')
    else:
        m = _NUMERIC_RE.match(s)
        if not m:
            _fail(text, 'unrecognised format')
        a, b, c = m.groups()
        if len(a) >= 3 and len(c) <= 2:
            year, month, day = int(a), int(b), int(c)
        elif len(c) >= 3 and len(a) <= 2:
            year, month, day = int(c), int(b), int(a)
        elif order == 'ymd':
            year, month, day = int(a), int(b), int(c)
        elif order == 'dmy':
            year, month, day = int(c), int(b), int(a)
        else:
            _fail(text, 'cannot tell the year from the day; write the year with 4 digits')
    try:
        check_date(year, month, day)
    except ValueError as e:
        _fail(text, str(e))
    return YMD(year, month, day)
