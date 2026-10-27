"""Time-zone handling.

Asia/Tehran is resolved from an embedded copy of the IANA history (tzdata 2026e), so the
result does not depend on the system's tzdata. Iran abolished daylight saving time in 2022;
systems with older tzdata still add an hour every spring. Other zones use zoneinfo.
"""
from __future__ import annotations

import bisect
import datetime as _dt

from ._data import TEHRAN_INITIAL_OFFSET, TEHRAN_TRANSITIONS

DEFAULT_TIME_ZONE = 'Asia/Tehran'
_TEHRAN = {'Asia/Tehran', 'Iran'}
_T_SECONDS = [t for t, _ in TEHRAN_TRANSITIONS]
_UTC = _dt.timezone.utc


def _tehran_offset(utc_seconds: float) -> int:
    i = bisect.bisect_right(_T_SECONDS, utc_seconds) - 1
    return TEHRAN_INITIAL_OFFSET if i < 0 else TEHRAN_TRANSITIONS[i][1]


def offset_minutes(utc_seconds: float, time_zone: str = DEFAULT_TIME_ZONE) -> int:
    """UTC offset in minutes of a zone at an instant (210 for Tehran today)."""
    if time_zone in _TEHRAN:
        return _tehran_offset(utc_seconds)
    if time_zone in ('UTC', 'Etc/UTC', 'GMT'):
        return 0
    from zoneinfo import ZoneInfo
    off = _dt.datetime.fromtimestamp(utc_seconds, ZoneInfo(time_zone)).utcoffset()
    return int(off.total_seconds() // 60)


class TehranTZ(_dt.tzinfo):
    """A tzinfo for Asia/Tehran backed by the embedded transition table."""

    def utcoffset(self, dt):
        if dt is None:
            return None
        return _dt.timedelta(minutes=self._offset_for_local(dt))

    def dst(self, dt):
        if dt is None:
            return None
        return _dt.timedelta(minutes=max(0, self._offset_for_local(dt) - 210))

    def tzname(self, dt):
        m = self._offset_for_local(dt)
        return '+%02d%02d' % (m // 60, m % 60)

    def fromutc(self, dt):
        utc = dt.replace(tzinfo=_UTC).timestamp()
        off = _tehran_offset(utc)
        local = (dt + _dt.timedelta(minutes=off)).replace(tzinfo=self, fold=0)
        if self._offset_for_local(local) != off:  # second occurrence of an ambiguous wall time
            local = local.replace(fold=1)
        return local

    def _offset_for_local(self, dt) -> int:
        naive = dt.replace(tzinfo=None)
        local = (naive - _dt.datetime(1970, 1, 1)).total_seconds()
        before = _tehran_offset(local - 86400)
        after = _tehran_offset(local + 86400)
        cands = [o for o in dict.fromkeys((before, after)) if _tehran_offset(local - o * 60) == o]
        if not cands:
            return before
        if len(cands) == 2:  # ambiguous: fold=0 -> earlier instant (larger offset)
            return max(cands) if getattr(dt, 'fold', 0) == 0 else min(cands)
        return cands[0]

    def __repr__(self):
        return 'TehranTZ()'


TEHRAN = TehranTZ()


def get_tzinfo(time_zone: str = DEFAULT_TIME_ZONE) -> _dt.tzinfo:
    if time_zone in _TEHRAN:
        return TEHRAN
    if time_zone in ('UTC', 'Etc/UTC', 'GMT'):
        return _UTC
    from zoneinfo import ZoneInfo
    return ZoneInfo(time_zone)


def to_zone(moment: _dt.datetime, time_zone: str = DEFAULT_TIME_ZONE) -> _dt.datetime:
    """Convert a datetime to the zone. Like datetime.astimezone, a naive datetime is
    interpreted as the system's local time."""
    return moment.astimezone(get_tzinfo(time_zone))
