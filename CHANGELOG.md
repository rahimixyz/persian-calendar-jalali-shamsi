# Changelog

## Unreleased

- PHP package `rahimixyz/taqvim` (PHP ≥ 8.1, no dependencies) with the same API, data and shared test suite.
- Persian quick guide at the top of the main README; full Persian guide with PHP/Laravel examples.
- PHP libraries (intl, morilog/jalali, verta) added to the accuracy report.

## 1.0.0 — 2026-10-27

First release of the JavaScript/TypeScript and Python packages.

- Astronomical leap-year table for Solar Hijri years 1–2999 (vernal equinox, 52.5°E noon rule), with uncertain years flagged.
- `JalaliDate` with strict validation, clamped month/year arithmetic, diffs, week/season helpers.
- Formatting in Persian, Dari (Afghanistan) and English with Persian, Arabic or Latin digits.
- Parsing of numeric and written dates with Persian/Arabic digits, Arabic letters, ZWNJ and RTL marks.
- Embedded Asia/Tehran time-zone history (IANA tzdata 2026e; no DST since 2022).
- Fixed solar public holidays of Iran and business-day arithmetic.
