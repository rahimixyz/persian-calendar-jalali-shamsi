# Accuracy: how the calendar is computed and how other libraries compare

## The rule

The Solar Hijri (Jalali, Shamsi, Iranian) calendar is **observational**: the year starts on
the day of the March (vernal) equinox. If the equinox happens **before noon** in Iran, that
day is 1 Farvardin (Nowruz); otherwise Nowruz is the next day. Leap years are therefore not
a fixed arithmetic pattern — usually every 4th year, sometimes a 5-year gap (e.g. 1403 → 1408).

Many programs instead use an **arithmetic approximation**. The best known is the
2820-year cycle (Birashk). A peer-reviewed review of the Iranian calendar shows it does not
match the equinoxes and names 21 March 2025 (Nowruz 1404) as one of the dates where it fails
([Heydari-Malayeri, *A concise review of the Iranian calendar*](https://arxiv.org/abs/astro-ph/0409620)).

## How taqvim's table was built

1. `scripts/equinox.py` computes the March equinox for every year 622–3622 CE with
   [Astronomy Engine](https://github.com/cosinekitty/astronomy) and, for 1900–2050, with JPL
   ephemeris **DE421** via Skyfield. The two agree within 67 seconds.
2. `scripts/build_reference.py` applies the noon rule. "Noon" is 12:00 of the 52.5°E meridian
   (Iran Standard Time, UTC+03:30). This is the convention that reproduces the official
   calendar and Borkowski's tables used by `jalaali-js`; across 1200–1700 AP it matches
   `jalaali-js` on every year.
3. Some sources state the rule with Tehran *apparent* noon instead. A year is marked
   **uncertain** when the two conventions disagree, or when the equinox falls closer to noon
   than the computational margin (2 min near today, growing with distance because of ΔT).
   In 1200–1700 AP these are: **1309, 1470, 1503, 1536, 1602, 1635, 1668**.
   `isUncertainYear()` / `is_uncertain_year()` exposes this so applications can decide.
4. `scripts/generate_data.py` packs the result into a 375-byte leap-year bitmap that both
   packages embed. The full table is published as [`reference/nowruz.csv`](../reference/nowruz.csv)
   (year, Gregorian date of Nowruz, leap, uncertain, equinox instant in UTC).

Known official values reproduced by the table: Nowruz 1403 = 20 March 2024, Nowruz 1404 =
21 March 2025 (equinox 12:31 IRST, after noon), Nowruz 1405 = 21 March 2026; leap years
…1391, 1395, 1399, 1403, **1408**, 1412….

## Time zone

Iran stopped using daylight saving time in 2022 (last change 21 September 2022, 24:00 → 23:00).
Systems with tzdata older than 2022b still move Tehran to UTC+04:30 every spring. Both packages
embed the full Asia/Tehran history from IANA tzdata 2026e (`scripts/extract_tehran_tz.js`),
so `Asia/Tehran` is always correct regardless of the host system. Other zones use the host's
`Intl` / `zoneinfo`.

## Comparison with existing libraries

Method: for every year 1–2999, ask the library for the Gregorian date of 1 Farvardin and
compare with the reference, ignoring the 211 uncertain years. Run on 27 October 2026 with
`scripts/compare/nowruz.js` and `scripts/compare/nowruz.py`; versions are pinned in
`scripts/compare/package.json` and `requirements.txt`. PHP was checked on 31 October 2026 with
`scripts/compare/nowruz.php`: PHP's `intl` extension was run directly; for morilog/jalali and
verta the leap-year rule was read from their source code and re-implemented in the script
(their code itself was not executed).

| Library | Version | Wrong years (of 2788 certain) | Wrong in 1300–1500 (modern era) |
|---|---|---|---|
| **taqvim** | 1.0.0 | **0** | **none** |
| jalaali-js | 2.0.1 | 0 | none |
| moment-jalaali | 0.10.5 | 0 | none |
| jalali-moment | 3.3.11 | 0 | none |
| persiantools (Python) | 6.2.0 | 2 (1012, 1045) | none |
| convertdate (Python) | 2.5.1 | 6 (1243–1276); refuses 621 years | none |
| Intl / ICU (Node 22) | ICU 78.3 | 33 (years 22–880) | none |
| date-fns-jalali | 4.4.0-0 | 45 (years 22–2559) | none |
| jdatetime (Python) | 6.1.1 | 45 (years 22–2394) | none |
| persian-date | 1.1.0 | 8 | **1342 (1963)**, 1474; borderline 1309 |
| khayyam (Python) | 3.0.17 | 275 (years 13–2997) | **1404 (2025)**, 1437 |
| jalaliday (dayjs) | 3.1.1 | 902 (years 2–999); 19 errors | none |
| PHP intl (ICU) | ICU 74.2 | 45 (years 22–2394) | none |
| morilog/jalali (PHP) | main, 2026-10-29 | same break table as jalaali-js (read from source) | none |
| verta / hekmatinasser/jalali (PHP) | main, 2026-10-25 | fixed 33-year rule: leap flag wrong in 196 years | none (nearest: 1176, **1634**, 1667) |

The most serious finding: **khayyam returns 20 March 2025 for 1 Farvardin 1404**, so every date
of 1404 is off by one day. It also rejects the real leap day 30 Esfand 1403 and accepts the
non-existent 30 Esfand 1404.

### Behaviour beyond leap years (`scripts/compare/probes.js`)

| Situation | Correct result | What other libraries do |
|---|---|---|
| `1404/12/30` (does not exist) | reject | date-fns-jalali, dayjs+jalaliday and persian-date silently roll over to 1 Farvardin 1405; jalali-moment reports it as valid |
| 31 Shahrivar + 1 month | 30 Mehr | all tested JS libraries clamp correctly |
| 30 Esfand 1403 + 1 year | 29 Esfand 1404 | correct in moment-jalaali, date-fns-jalali, persian-date |
| Parse `۱۴۰۴/۰۱/۱۵` (Persian digits) | 1404/01/15 | moment-jalaali and dayjs: invalid; jalali-moment: **year 0621** (silently wrong); persiantools and khayyam (Python): error; jdatetime: correct |

taqvim's answer for every row is covered by `reference/test-cases.json`, which both the
JavaScript and the Python test suites run.
