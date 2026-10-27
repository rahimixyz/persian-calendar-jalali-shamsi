"""Core Solar Hijri (Jalali / Shamsi) arithmetic.

Every date is reduced to an "epoch day": days since 1970-01-01 in the proleptic
Gregorian calendar. Jalali leap years come from an astronomical vernal-equinox table
(years 1-2999), not from an arithmetic cycle.
"""
from __future__ import annotations

import bisect
import datetime as _dt
from typing import NamedTuple

from ._data import LEAP_HEX, MAX_YEAR, MIN_YEAR, NOWRUZ_EPOCH_DAY, UNCERTAIN_YEARS

__all__ = [
    'MIN_YEAR', 'MAX_YEAR', 'YMD', 'MONTH_NAMES_EN', 'is_leap_year', 'days_in_year', 'days_in_month',
    'is_valid_date', 'is_uncertain_year', 'to_jalali', 'to_gregorian', 'nowruz', 'jalali_to_epoch_day',
    'epoch_day_to_jalali', 'gregorian_to_epoch_day', 'epoch_day_to_gregorian',
]


class YMD(NamedTuple):
    year: int
    month: int
    day: int


MONTH_NAMES_EN = ('Farvardin', 'Ordibehesht', 'Khordad', 'Tir', 'Mordad', 'Shahrivar',
                  'Mehr', 'Aban', 'Azar', 'Dey', 'Bahman', 'Esfand')

_COUNT = MAX_YEAR - MIN_YEAR + 1
_bits = int.from_bytes(bytes.fromhex(LEAP_HEX), 'little')
_LEAP = bytes((_bits >> i) & 1 for i in range(_COUNT))
_START = [NOWRUZ_EPOCH_DAY]
for _i in range(_COUNT):
    _START.append(_START[-1] + 365 + _LEAP[_i])
MIN_EPOCH_DAY = _START[0]
MAX_EPOCH_DAY = _START[-1] - 1
_EPOCH = _dt.date(1970, 1, 1).toordinal()


def _is_int(n) -> bool:
    return isinstance(n, int) and not isinstance(n, bool)


def _check_year(jy) -> None:
    if not _is_int(jy):
        raise TypeError(f'Jalali year must be an integer, got {jy!r}')
    if not MIN_YEAR <= jy <= MAX_YEAR:
        raise ValueError(f'Jalali year {jy} is outside the supported range {MIN_YEAR}-{MAX_YEAR}')


def _check_month(jm) -> None:
    if not _is_int(jm) or not 1 <= jm <= 12:
        raise ValueError(f'Jalali month must be an integer 1-12, got {jm!r}')


def is_leap_year(jy: int) -> bool:
    """True when the Jalali year has 366 days (Esfand has 30 days)."""
    _check_year(jy)
    return _LEAP[jy - MIN_YEAR] == 1


def days_in_year(jy: int) -> int:
    return 366 if is_leap_year(jy) else 365


def days_in_month(jy: int, jm: int) -> int:
    """Months 1-6 have 31 days, 7-11 have 30, Esfand has 29 or 30."""
    _check_year(jy)
    _check_month(jm)
    if jm <= 6:
        return 31
    if jm <= 11:
        return 30
    return 30 if _LEAP[jy - MIN_YEAR] else 29


def is_valid_date(jy: int, jm: int, jd: int) -> bool:
    """Never raises."""
    if not (_is_int(jy) and _is_int(jm) and _is_int(jd)):
        return False
    if not (MIN_YEAR <= jy <= MAX_YEAR and 1 <= jm <= 12 and jd >= 1):
        return False
    return jd <= days_in_month(jy, jm)


def is_uncertain_year(jy: int) -> bool:
    """True for the rare years whose start depends on the noon convention or lies within
    the equinox error margin (1309, 1470, 1503, 1536, 1602, 1635, 1668 in 1200-1700)."""
    _check_year(jy)
    return jy in UNCERTAIN_YEARS


def check_date(jy: int, jm: int, jd: int) -> None:
    _check_year(jy)
    _check_month(jm)
    if not _is_int(jd):
        raise TypeError(f'Jalali day must be an integer, got {jd!r}')
    dim = days_in_month(jy, jm)
    if not 1 <= jd <= dim:
        why = f' ({jy} is not a leap year)' if jm == 12 and jd == 30 else ''
        raise ValueError(f'{jy}/{jm:02d}/{jd:02d} does not exist: '
                         f'{MONTH_NAMES_EN[jm - 1]} {jy} has {dim} days{why}')


def nowruz_epoch_day(jy: int) -> int:
    _check_year(jy)
    return _START[jy - MIN_YEAR]


def day_of_year_index(jm: int, jd: int) -> int:
    return (jm - 1) * 31 + jd - 1 if jm <= 7 else 186 + (jm - 7) * 30 + jd - 1


def jalali_to_epoch_day(jy: int, jm: int, jd: int) -> int:
    check_date(jy, jm, jd)
    return _START[jy - MIN_YEAR] + day_of_year_index(jm, jd)


def epoch_day_to_jalali(n: int) -> YMD:
    if not _is_int(n):
        raise TypeError(f'epoch day must be an integer, got {n!r}')
    if not MIN_EPOCH_DAY <= n <= MAX_EPOCH_DAY:
        raise ValueError(f'date is outside the supported range (Jalali {MIN_YEAR}-{MAX_YEAR})')
    i = bisect.bisect_right(_START, n) - 1
    doy = n - _START[i]
    if doy < 186:
        return YMD(MIN_YEAR + i, doy // 31 + 1, doy % 31 + 1)
    r = doy - 186
    return YMD(MIN_YEAR + i, 7 + r // 30, r % 30 + 1)


def gregorian_to_epoch_day(gy: int, gm: int, gd: int) -> int:
    return _dt.date(gy, gm, gd).toordinal() - _EPOCH


def epoch_day_to_gregorian(n: int) -> YMD:
    d = _dt.date.fromordinal(n + _EPOCH)
    return YMD(d.year, d.month, d.day)


def to_jalali(gy: int, gm: int, gd: int) -> YMD:
    """Gregorian -> Jalali. Raises ValueError for non-existent or out-of-range dates."""
    return epoch_day_to_jalali(gregorian_to_epoch_day(gy, gm, gd))


def to_gregorian(jy: int, jm: int, jd: int) -> YMD:
    """Jalali -> Gregorian. Raises ValueError for non-existent dates such as 30 Esfand of a common year."""
    return epoch_day_to_gregorian(jalali_to_epoch_day(jy, jm, jd))


def nowruz(jy: int) -> YMD:
    """Gregorian date of 1 Farvardin of the given Jalali year."""
    return epoch_day_to_gregorian(nowruz_epoch_day(jy))


def weekday_of_epoch_day(n: int) -> int:
    """0 = Saturday ... 6 = Friday."""
    return (n + 5) % 7
