"""taqvim — accurate Solar Hijri (Jalali / Shamsi / Persian) calendar for Python.

Zero dependencies. Leap years come from an astronomical vernal-equinox table (years 1-2999)
and Asia/Tehran time is resolved from embedded tzdata (no daylight saving since 2022).
The JavaScript/TypeScript package with the same behaviour is published as ``taqvim`` on npm.
"""
from __future__ import annotations

import datetime as _dt

from . import core as _core
from .core import (MAX_YEAR, MIN_YEAR, MONTH_NAMES_EN, YMD, days_in_month, days_in_year,
                   epoch_day_to_gregorian, epoch_day_to_jalali, gregorian_to_epoch_day, is_leap_year,
                   is_uncertain_year, is_valid_date, jalali_to_epoch_day, nowruz, to_gregorian, to_jalali)
from .date import JalaliDate
from .fmt import format_fields as _format_fields
from .holidays import FIXED_SOLAR_HOLIDAYS, Holiday, add_business_days, is_holiday, is_weekend, solar_holidays
from .locale import (MONTHS, SEASONS, WEEKDAYS, convert_digits, normalize_persian, to_latin_digits,
                     to_persian_digits)
from .parse import parse_jalali
from .tz import DEFAULT_TIME_ZONE, TEHRAN, get_tzinfo, offset_minutes, to_zone

__version__ = '1.0.0'


def format_datetime(moment: _dt.datetime, pattern: str = 'YYYY/MM/DD HH:mm', locale: str = 'fa',
                    digits: str | None = None, time_zone: str = DEFAULT_TIME_ZONE) -> str:
    """Format an instant as a Jalali date-time in Tehran (or another zone).

    >>> format_datetime(datetime(2025, 3, 20, 20, 30, tzinfo=timezone.utc), 'YYYY/MM/DD HH:mm Z', 'en')
    '1404/01/01 00:00 +03:30'
    """
    local = to_zone(moment, time_zone)
    d = JalaliDate.from_gregorian(local.year, local.month, local.day)
    off = int(local.utcoffset().total_seconds() // 60)
    return _format_fields(dict(year=d.year, month=d.month, day=d.day, weekday=d.weekday,
                               day_of_year=d.day_of_year, week=d.week_of_year, hour=local.hour,
                               minute=local.minute, second=local.second, offset=off), pattern, locale, digits)


__all__ = [
    'MIN_YEAR', 'MAX_YEAR', 'MONTH_NAMES_EN', 'YMD', 'JalaliDate',
    'is_leap_year', 'days_in_year', 'days_in_month', 'is_valid_date', 'is_uncertain_year',
    'to_jalali', 'to_gregorian', 'nowruz', 'jalali_to_epoch_day', 'epoch_day_to_jalali',
    'gregorian_to_epoch_day', 'epoch_day_to_gregorian', 'parse_jalali', 'format_datetime',
    'MONTHS', 'WEEKDAYS', 'SEASONS', 'to_persian_digits', 'to_latin_digits', 'convert_digits', 'normalize_persian',
    'DEFAULT_TIME_ZONE', 'TEHRAN', 'get_tzinfo', 'offset_minutes', 'to_zone',
    'FIXED_SOLAR_HOLIDAYS', 'Holiday', 'solar_holidays', 'is_holiday', 'is_weekend', 'add_business_days',
]
