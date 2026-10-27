"""JalaliDate: an immutable Solar Hijri calendar date that interoperates with datetime."""
from __future__ import annotations

import datetime as _dt
from functools import total_ordering

from . import core
from .fmt import format_fields
from .parse import parse_jalali
from .tz import DEFAULT_TIME_ZONE, get_tzinfo, to_zone


@total_ordering
class JalaliDate:
    """An immutable date in the Solar Hijri (Jalali / Shamsi / Persian) calendar.

    >>> d = JalaliDate(1404, 1, 15)
    >>> d.to_date()
    datetime.date(2025, 4, 4)
    >>> d.add_months(1).format('D MMMM YYYY')
    '۱۵ اردیبهشت ۱۴۰۴'
    >>> JalaliDate.parse('۱۵ فروردین ۱۴۰۴') == d
    True
    """

    __slots__ = ('_y', '_m', '_d', '_n')

    def __init__(self, year: int, month: int, day: int):
        n = core.jalali_to_epoch_day(year, month, day)
        object.__setattr__(self, '_y', year)
        object.__setattr__(self, '_m', month)
        object.__setattr__(self, '_d', day)
        object.__setattr__(self, '_n', n)

    def __setattr__(self, key, value):
        raise AttributeError('JalaliDate is immutable')

    def __reduce__(self):
        return (JalaliDate, (self._y, self._m, self._d))

    # ------------------------------------------------------------------ construction
    @classmethod
    def from_epoch_day(cls, n: int) -> 'JalaliDate':
        return cls(*core.epoch_day_to_jalali(n))

    @classmethod
    def from_gregorian(cls, year: int, month: int, day: int) -> 'JalaliDate':
        return cls.from_epoch_day(core.gregorian_to_epoch_day(year, month, day))

    @classmethod
    def from_date(cls, value) -> 'JalaliDate':
        """From a datetime.date. A datetime is converted to Tehran time first (see from_datetime)."""
        if isinstance(value, _dt.datetime):
            return cls.from_datetime(value)
        if not isinstance(value, _dt.date):
            raise TypeError('from_date expects a datetime.date')
        return cls.from_gregorian(value.year, value.month, value.day)

    @classmethod
    def from_datetime(cls, moment: _dt.datetime, time_zone: str = DEFAULT_TIME_ZONE) -> 'JalaliDate':
        """The calendar date of an instant in a zone (default Asia/Tehran)."""
        local = to_zone(moment, time_zone)
        return cls.from_gregorian(local.year, local.month, local.day)

    @classmethod
    def from_timestamp(cls, ts: float, time_zone: str = DEFAULT_TIME_ZONE) -> 'JalaliDate':
        return cls.from_datetime(_dt.datetime.fromtimestamp(ts, _dt.timezone.utc), time_zone)

    @classmethod
    def today(cls, time_zone: str = DEFAULT_TIME_ZONE) -> 'JalaliDate':
        return cls.from_datetime(_dt.datetime.now(_dt.timezone.utc), time_zone)

    @classmethod
    def parse(cls, text: str, order: str | None = None) -> 'JalaliDate':
        """See taqvim.parse_jalali. Raises ValueError with a readable reason."""
        return cls(*parse_jalali(text, order))

    @classmethod
    def try_parse(cls, text: str, order: str | None = None) -> 'JalaliDate | None':
        try:
            return cls.parse(text, order)
        except (ValueError, TypeError):
            return None

    @staticmethod
    def is_valid(year: int, month: int, day: int) -> bool:
        return core.is_valid_date(year, month, day)

    # ------------------------------------------------------------------ properties
    year = property(lambda self: self._y)
    month = property(lambda self: self._m)
    day = property(lambda self: self._d)
    epoch_day = property(lambda self: self._n, doc='Days since 1970-01-01 (proleptic Gregorian).')

    @property
    def weekday(self) -> int:
        """0 = Saturday (شنبه) ... 6 = Friday (جمعه)."""
        return core.weekday_of_epoch_day(self._n)

    @property
    def day_of_year(self) -> int:
        return self._n - core.nowruz_epoch_day(self._y) + 1

    @property
    def week_of_year(self) -> int:
        """Week 1 contains 1 Farvardin; weeks start on Saturday."""
        nz = core.nowruz_epoch_day(self._y)
        return (self._n - nz + core.weekday_of_epoch_day(nz)) // 7 + 1

    @property
    def season(self) -> int:
        return (self._m - 1) // 3 + 1

    def is_leap_year(self) -> bool:
        return core.is_leap_year(self._y)

    def days_in_month(self) -> int:
        return core.days_in_month(self._y, self._m)

    def days_in_year(self) -> int:
        return core.days_in_year(self._y)

    def is_uncertain_year(self) -> bool:
        return core.is_uncertain_year(self._y)

    # ------------------------------------------------------------------ arithmetic
    def add_days(self, days: int) -> 'JalaliDate':
        return JalaliDate.from_epoch_day(self._n + days)

    def add_weeks(self, weeks: int) -> 'JalaliDate':
        return self.add_days(weeks * 7)

    def add_months(self, months: int) -> 'JalaliDate':
        """Clamps to the month end: 31 Shahrivar + 1 month = 30 Mehr."""
        total = self._y * 12 + (self._m - 1) + months
        y, m = divmod(total, 12)
        m += 1
        if not core.MIN_YEAR <= y <= core.MAX_YEAR:
            raise ValueError(f'result is outside the supported range {core.MIN_YEAR}-{core.MAX_YEAR}')
        return JalaliDate(y, m, min(self._d, core.days_in_month(y, m)))

    def add_years(self, years: int) -> 'JalaliDate':
        """Clamps 30 Esfand to 29 Esfand in common years."""
        return self.add_months(years * 12)

    def replace(self, year: int | None = None, month: int | None = None, day: int | None = None) -> 'JalaliDate':
        """Strict: the result must exist."""
        return JalaliDate(self._y if year is None else year, self._m if month is None else month,
                          self._d if day is None else day)

    def start_of_month(self) -> 'JalaliDate':
        return JalaliDate(self._y, self._m, 1)

    def end_of_month(self) -> 'JalaliDate':
        return JalaliDate(self._y, self._m, self.days_in_month())

    def start_of_year(self) -> 'JalaliDate':
        return JalaliDate(self._y, 1, 1)

    def end_of_year(self) -> 'JalaliDate':
        return JalaliDate(self._y, 12, core.days_in_month(self._y, 12))

    def start_of_week(self) -> 'JalaliDate':
        """The Saturday on or before this date."""
        return self.add_days(-self.weekday)

    def end_of_week(self) -> 'JalaliDate':
        """The Friday on or after this date."""
        return self.add_days(6 - self.weekday)

    def diff_in_days(self, other: 'JalaliDate') -> int:
        return self._n - other._n

    def diff_in_months(self, other: 'JalaliDate') -> int:
        """Whole calendar months from other to self (negative if self is earlier)."""
        if self._n < other._n:
            return -other.diff_in_months(self)
        months = (self._y - other._y) * 12 + (self._m - other._m)
        if self._d < other._d and self._d != self.days_in_month():
            months -= 1
        return months

    def diff_in_years(self, other: 'JalaliDate') -> int:
        m = self.diff_in_months(other)
        return -(-m // 12) if m < 0 else m // 12

    def __add__(self, other):
        if isinstance(other, _dt.timedelta):
            return self.add_days(other.days)
        return NotImplemented

    __radd__ = __add__

    def __sub__(self, other):
        if isinstance(other, JalaliDate):
            return _dt.timedelta(days=self._n - other._n)
        if isinstance(other, _dt.timedelta):
            return self.add_days(-other.days)
        return NotImplemented

    # ------------------------------------------------------------------ comparison
    def __eq__(self, other):
        return isinstance(other, JalaliDate) and self._n == other._n

    def __lt__(self, other):
        if not isinstance(other, JalaliDate):
            return NotImplemented
        return self._n < other._n

    def __hash__(self):
        return hash(('JalaliDate', self._n))

    def is_between(self, start: 'JalaliDate', end: 'JalaliDate') -> bool:
        """Inclusive."""
        return start._n <= self._n <= end._n

    # ------------------------------------------------------------------ conversion
    def to_gregorian(self) -> core.YMD:
        return core.epoch_day_to_gregorian(self._n)

    def to_date(self) -> _dt.date:
        """The same day as a datetime.date (Gregorian)."""
        return _dt.date(*self.to_gregorian())

    def to_datetime(self, hour: int = 0, minute: int = 0, second: int = 0, microsecond: int = 0,
                    time_zone: str = DEFAULT_TIME_ZONE) -> _dt.datetime:
        """An aware datetime at the given wall-clock time in Tehran (or another zone)."""
        g = self.to_gregorian()
        return _dt.datetime(g.year, g.month, g.day, hour, minute, second, microsecond, tzinfo=get_tzinfo(time_zone))

    def isoformat(self) -> str:
        """Gregorian ISO-8601 date, e.g. '2025-04-04'."""
        return self.to_date().isoformat()

    def format(self, pattern: str = 'YYYY/MM/DD', locale: str = 'fa', digits: str | None = None) -> str:
        """Same tokens as the JavaScript package: 'YYYY/MM/DD', 'dddd D MMMM YYYY', ..."""
        return format_fields(dict(year=self._y, month=self._m, day=self._d, weekday=self.weekday,
                                  day_of_year=self.day_of_year, week=self.week_of_year), pattern, locale, digits)

    def __str__(self):
        return f'{self._y:04d}/{self._m:02d}/{self._d:02d}'

    def __repr__(self):
        return f'JalaliDate({self._y}, {self._m}, {self._d})'
