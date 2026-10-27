"""Fixed-date public holidays of Iran in the Solar Hijri calendar.

Islamic (lunar Hijri) holidays are NOT included: Iran fixes them each year by moon
sighting, so they cannot be computed reliably in advance. Pass them via ``extra``.
"""
from __future__ import annotations

from typing import Iterable, NamedTuple

from .date import JalaliDate

FIXED_SOLAR_HOLIDAYS = (
    (1, 1, 'جشن نوروز', 'Nowruz'),
    (1, 2, 'عید نوروز', 'Nowruz'),
    (1, 3, 'عید نوروز', 'Nowruz'),
    (1, 4, 'عید نوروز', 'Nowruz'),
    (1, 12, 'روز جمهوری اسلامی', 'Islamic Republic Day'),
    (1, 13, 'روز طبیعت', 'Nature Day (Sizdah Bedar)'),
    (3, 14, 'رحلت امام خمینی', 'Death of Ayatollah Khomeini'),
    (3, 15, 'قیام ۱۵ خرداد', '15 Khordad Uprising'),
    (11, 22, 'پیروزی انقلاب اسلامی', 'Revolution Day'),
    (12, 29, 'ملی شدن صنعت نفت', 'Oil Nationalisation Day'),
)


class Holiday(NamedTuple):
    date: JalaliDate
    fa: str
    en: str


def solar_holidays(year: int) -> list[Holiday]:
    return [Holiday(JalaliDate(year, m, d), fa, en) for m, d, fa, en in FIXED_SOLAR_HOLIDAYS]


def is_weekend(date: JalaliDate, weekend_days: Iterable[int] = (6,)) -> bool:
    """weekend_days: 0 = Saturday ... 6 = Friday. Default: Friday."""
    return date.weekday in tuple(weekend_days)


def is_holiday(date: JalaliDate, extra: Iterable[JalaliDate] = (), weekend_days: Iterable[int] = (6,)) -> bool:
    if is_weekend(date, weekend_days):
        return True
    if any(m == date.month and d == date.day for m, d, _, _ in FIXED_SOLAR_HOLIDAYS):
        return True
    return date in set(extra)


def add_business_days(date: JalaliDate, days: int, extra: Iterable[JalaliDate] = (),
                      weekend_days: Iterable[int] = (6,)) -> JalaliDate:
    extra = set(extra)
    weekend_days = tuple(weekend_days)
    step = -1 if days < 0 else 1
    left = abs(days)
    d = date
    while left:
        d = d.add_days(step)
        if not is_holiday(d, extra, weekend_days):
            left -= 1
    return d
