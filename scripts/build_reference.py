"""Derive the Nowruz table from scripts/equinox.py output (equinox.json).

Rule used (the de facto standard, identical to Borkowski / jalaali-js in 1200-1700 AP):
1 Farvardin is the day on which the March equinox occurs before 12:00 mean time of the
52.5°E meridian (Iran Standard Time, UTC+03:30); otherwise it is the following day.
A year is flagged "uncertain" when the alternative convention (Tehran apparent noon)
gives a different day, or when the equinox lies closer to noon than the ephemeris /
Delta-T error margin (2 + 0.6 * t^2 minutes, t = centuries from AD 2000).
"""
import json, datetime as dt, pathlib
here = pathlib.Path(__file__).resolve().parent
R = json.load(open(here / 'equinox.json'))
out = []
for i, r in enumerate(R[:-1]):
    t = (r['gy'] - 2000) / 100
    margin = 2 + 0.6 * t * t
    nz = dt.date.fromisoformat(r['nowruz_mean'])
    nxt = dt.date.fromisoformat(R[i + 1]['nowruz_mean'])
    uncertain = r['nowruz_mean'] != r['nowruz_true'] or abs(r['local_min'] - 720) < margin
    out.append(dict(jy=r['jy'], nowruz=nz.isoformat(), leap=(nxt - nz).days == 366,
                    uncertain=uncertain, equinox_utc=r['equinox_utc'][:19] + 'Z'))
json.dump(out, open(here.parent / 'reference' / 'nowruz.json', 'w'))
print(len(out), 'years written')
