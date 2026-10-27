"""Compute Nowruz (1 Farvardin) for each Solar Hijri year from the March equinox.
Rule: if the equinox happens before noon in Iran, that day is Nowruz, otherwise the next day.
Two noon definitions are computed so near-noon years can be flagged:
 - mean noon of the 52.5°E meridian (12:00 UTC+3:30)
 - apparent (true) noon at Tehran, 51.42°E (mean noon + longitude shift - equation of time)
"""
import json, math, datetime as dt
import astronomy
from skyfield.api import load
from skyfield import almanac


UTC = dt.timezone.utc
IRST = dt.timezone(dt.timedelta(hours=3, minutes=30))

def eq_astronomy(gy):
    s = astronomy.Seasons(gy)
    return s.mar_equinox.Utc().replace(tzinfo=UTC)

_ts = load.timescale()
_eph = None

def eq_skyfield(gy):
    global _eph
    if _eph is None:
        import os, skyfield_data
        _eph = load(os.path.join(skyfield_data.get_skyfield_data_path(), 'de421.bsp'))
    t0 = _ts.utc(gy, 3, 15); t1 = _ts.utc(gy, 3, 25)
    t, y = almanac.find_discrete(t0, t1, almanac.seasons(_eph))
    for ti, yi in zip(t, y):
        if yi == 0:
            return ti.utc_datetime()
    raise ValueError(gy)

def equation_of_time_minutes(d):
    # NOAA approximation, good to ~0.5 min
    n = d.timetuple().tm_yday
    g = 2 * math.pi / 365 * (n - 1)
    return 229.18 * (0.000075 + 0.001868 * math.cos(g) - 0.032077 * math.sin(g)
                     - 0.014615 * math.cos(2 * g) - 0.040849 * math.sin(2 * g))

def nowruz(eq_utc):
    local = eq_utc.astimezone(IRST)
    day = local.date()
    mins = local.hour * 60 + local.minute + local.second / 60
    mean_noon = 12 * 60
    # Tehran 51.4215E: mean noon at Tehran is 4*(52.5-51.4215) min later than 12:00 IRST
    true_noon = 12 * 60 + 4 * (52.5 - 51.4215) - equation_of_time_minutes(day)
    d1 = day if mins < mean_noon else day + dt.timedelta(days=1)
    d2 = day if mins < true_noon else day + dt.timedelta(days=1)
    return d1, d2, mins, true_noon

if __name__ == '__main__':
    import sys
    rows = []
    maxdiff = 0
    for jy in range(1, 3001):
        gy = jy + 621
        e = eq_astronomy(gy)
        sk = None
        if 1900 <= gy <= 2050:
            sk = eq_skyfield(gy)
            maxdiff = max(maxdiff, abs((sk - e).total_seconds()))
        d1, d2, mins, tn = nowruz(e)
        rows.append(dict(jy=jy, gy=gy, equinox_utc=e.isoformat(), local_min=round(mins, 2),
                         nowruz_mean=d1.isoformat(), nowruz_true=d2.isoformat(), true_noon_min=round(tn, 2)))
    json.dump(rows, open(__import__('pathlib').Path(__file__).with_name('equinox.json'), 'w'))
    print('max |astronomy-engine - DE421| seconds (1900-2050):', maxdiff)
