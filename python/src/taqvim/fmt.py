"""Pattern formatting shared by JalaliDate and format_datetime (same tokens as the JS package)."""
from __future__ import annotations

import re

from .locale import MERIDIEM, MONTHS, SEASONS, SHORT_WEEKDAYS, WEEKDAYS, convert_digits, default_digits

TOKEN_RE = re.compile(r'\[([^\]]*)\]|YYYY|YY|MMMM|MM|M|DDD|DD|D|dddd|ddd|d|ww|w|QQQQ|Q|HH|H|hh|h|mm|m|ss|s|A|ZZ|Z')


def _offset(minutes: int, colon: bool) -> str:
    sign = '-' if minutes < 0 else '+'
    a = abs(minutes)
    return f'{sign}{a // 60:02d}{":" if colon else ""}{a % 60:02d}'


def format_fields(f: dict, pattern: str, locale: str = 'fa', digits: str | None = None) -> str:
    if locale not in MONTHS:
        raise ValueError(f"Unknown locale {locale!r}. Use 'fa', 'fa-AF' or 'en'.")
    digits = digits or default_digits(locale)

    def num(s) -> str:
        return convert_digits(s, digits)

    def time(tok, key):
        v = f.get(key)
        if v is None:
            raise ValueError(f'Token {tok!r} needs a time. Use format_datetime() for dates with a time of day.')
        return v

    def repl(m: re.Match) -> str:
        if m.group(1) is not None:
            return m.group(1)
        t = m.group(0)
        if t == 'YYYY': return num(f'{f["year"]:04d}')
        if t == 'YY': return num(f'{f["year"] % 100:02d}')
        if t == 'MMMM': return MONTHS[locale][f['month'] - 1]
        if t == 'MM': return num(f'{f["month"]:02d}')
        if t == 'M': return num(f['month'])
        if t == 'DDD': return num(f['day_of_year'])
        if t == 'DD': return num(f'{f["day"]:02d}')
        if t == 'D': return num(f['day'])
        if t == 'dddd': return WEEKDAYS[locale][f['weekday']]
        if t == 'ddd': return SHORT_WEEKDAYS[locale][f['weekday']]
        if t == 'd': return num(f['weekday'] + 1)
        if t == 'ww': return num(f'{f["week"]:02d}')
        if t == 'w': return num(f['week'])
        if t == 'QQQQ': return SEASONS[locale][(f['month'] - 1) // 3]
        if t == 'Q': return num((f['month'] - 1) // 3 + 1)
        if t == 'HH': return num(f'{time(t, "hour"):02d}')
        if t == 'H': return num(time(t, 'hour'))
        if t == 'hh': return num(f'{time(t, "hour") % 12 or 12:02d}')
        if t == 'h': return num(time(t, 'hour') % 12 or 12)
        if t == 'mm': return num(f'{time(t, "minute"):02d}')
        if t == 'm': return num(time(t, 'minute'))
        if t == 'ss': return num(f'{time(t, "second"):02d}')
        if t == 's': return num(time(t, 'second'))
        if t == 'A': return MERIDIEM[locale][0 if time(t, 'hour') < 12 else 1]
        if t == 'Z': return num(_offset(time(t, 'offset'), True))
        if t == 'ZZ': return num(_offset(time(t, 'offset'), False))
        return t

    return TOKEN_RE.sub(repl, pattern)
