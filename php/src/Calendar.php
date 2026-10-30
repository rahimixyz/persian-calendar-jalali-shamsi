<?php

declare(strict_types=1);

namespace Taqvim;

/**
 * Core Solar Hijri (Jalali / Shamsi) arithmetic.
 *
 * Every date is reduced to an "epoch day": days since 1970-01-01 in the proleptic
 * Gregorian calendar. Jalali leap years come from an astronomical vernal-equinox
 * table (years 1–2999), not from an arithmetic cycle.
 *
 * Dates are returned as ['year' => int, 'month' => int, 'day' => int].
 */
final class Calendar
{
    public const MIN_YEAR = Data::MIN_YEAR;
    public const MAX_YEAR = Data::MAX_YEAR;

    public const MONTH_NAMES_EN = [
        'Farvardin', 'Ordibehesht', 'Khordad', 'Tir', 'Mordad', 'Shahrivar',
        'Mehr', 'Aban', 'Azar', 'Dey', 'Bahman', 'Esfand',
    ];

    /** @var int[]|null */
    private static ?array $leap = null;
    /** @var int[]|null epoch day of 1 Farvardin, index = year - MIN_YEAR; one extra entry at the end */
    private static ?array $start = null;
    /** @var array<int, true>|null */
    private static ?array $uncertain = null;

    private static function init(): void
    {
        if (self::$start !== null) {
            return;
        }
        $bytes = hex2bin(Data::LEAP_HEX);
        $count = self::MAX_YEAR - self::MIN_YEAR + 1;
        $leap = [];
        $start = [Data::NOWRUZ_EPOCH_DAY];
        for ($i = 0; $i < $count; $i++) {
            $leap[$i] = (ord($bytes[$i >> 3]) >> ($i & 7)) & 1;
            $start[$i + 1] = $start[$i] + 365 + $leap[$i];
        }
        self::$leap = $leap;
        self::$start = $start;
        self::$uncertain = array_fill_keys(Data::UNCERTAIN_YEARS, true);
    }

    private static function checkYear(int $jy): void
    {
        if ($jy < self::MIN_YEAR || $jy > self::MAX_YEAR) {
            throw new InvalidDateException(sprintf(
                'Jalali year %d is outside the supported range %d–%d', $jy, self::MIN_YEAR, self::MAX_YEAR
            ));
        }
    }

    private static function checkMonth(int $jm): void
    {
        if ($jm < 1 || $jm > 12) {
            throw new InvalidDateException(sprintf('Jalali month must be 1–12, got %d', $jm));
        }
    }

    public static function minEpochDay(): int
    {
        self::init();
        return self::$start[0];
    }

    public static function maxEpochDay(): int
    {
        self::init();
        return self::$start[count(self::$start) - 1] - 1;
    }

    /** True when the Jalali year has 366 days (Esfand has 30 days). */
    public static function isLeapYear(int $jy): bool
    {
        self::checkYear($jy);
        self::init();
        return self::$leap[$jy - self::MIN_YEAR] === 1;
    }

    public static function daysInYear(int $jy): int
    {
        return self::isLeapYear($jy) ? 366 : 365;
    }

    /** Months 1–6 have 31 days, 7–11 have 30, Esfand has 29 or 30. */
    public static function daysInMonth(int $jy, int $jm): int
    {
        self::checkMonth($jm);
        if ($jm <= 6) {
            self::checkYear($jy);
            return 31;
        }
        if ($jm <= 11) {
            self::checkYear($jy);
            return 30;
        }
        return self::isLeapYear($jy) ? 30 : 29;
    }

    /** Never throws. */
    public static function isValidDate(int $jy, int $jm, int $jd): bool
    {
        if ($jy < self::MIN_YEAR || $jy > self::MAX_YEAR || $jm < 1 || $jm > 12 || $jd < 1) {
            return false;
        }
        return $jd <= self::daysInMonth($jy, $jm);
    }

    /**
     * True for the rare years whose start depends on the noon convention or lies within
     * the equinox error margin (1309, 1470, 1503, 1536, 1602, 1635, 1668 in 1200–1700).
     */
    public static function isUncertainYear(int $jy): bool
    {
        self::checkYear($jy);
        self::init();
        return isset(self::$uncertain[$jy]);
    }

    public static function checkDate(int $jy, int $jm, int $jd): void
    {
        self::checkYear($jy);
        self::checkMonth($jm);
        $dim = self::daysInMonth($jy, $jm);
        if ($jd < 1 || $jd > $dim) {
            $why = ($jm === 12 && $jd === 30) ? sprintf(' (%d is not a leap year)', $jy) : '';
            throw new InvalidDateException(sprintf(
                '%d/%02d/%02d does not exist: %s %d has %d days%s',
                $jy, $jm, $jd, self::MONTH_NAMES_EN[$jm - 1], $jy, $dim, $why
            ));
        }
    }

    public static function nowruzEpochDay(int $jy): int
    {
        self::checkYear($jy);
        self::init();
        return self::$start[$jy - self::MIN_YEAR];
    }

    public static function dayOfYearIndex(int $jm, int $jd): int
    {
        return $jm <= 7 ? ($jm - 1) * 31 + $jd - 1 : 186 + ($jm - 7) * 30 + $jd - 1;
    }

    public static function jalaliToEpochDay(int $jy, int $jm, int $jd): int
    {
        self::checkDate($jy, $jm, $jd);
        return self::$start[$jy - self::MIN_YEAR] + self::dayOfYearIndex($jm, $jd);
    }

    /** @return array{year:int, month:int, day:int} */
    public static function epochDayToJalali(int $n): array
    {
        self::init();
        if ($n < self::minEpochDay() || $n > self::maxEpochDay()) {
            throw new InvalidDateException(sprintf(
                'Date is outside the supported range (Jalali %d–%d)', self::MIN_YEAR, self::MAX_YEAR
            ));
        }
        $lo = 0;
        $hi = count(self::$start) - 2;
        while ($lo < $hi) {
            $mid = ($lo + $hi + 1) >> 1;
            if (self::$start[$mid] <= $n) {
                $lo = $mid;
            } else {
                $hi = $mid - 1;
            }
        }
        $doy = $n - self::$start[$lo];
        $year = self::MIN_YEAR + $lo;
        if ($doy < 186) {
            return ['year' => $year, 'month' => intdiv($doy, 31) + 1, 'day' => $doy % 31 + 1];
        }
        $r = $doy - 186;
        return ['year' => $year, 'month' => 7 + intdiv($r, 30), 'day' => $r % 30 + 1];
    }

    // ---------------------------------------------------------------- Gregorian (proleptic)

    public static function isGregorianLeapYear(int $y): bool
    {
        return ($y % 4 === 0 && $y % 100 !== 0) || $y % 400 === 0;
    }

    public static function gregorianDaysInMonth(int $y, int $m): int
    {
        return $m === 2 ? (self::isGregorianLeapYear($y) ? 29 : 28) : [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][$m - 1];
    }

    private static function fdiv(int $a, int $b): int
    {
        return (int) floor($a / $b);
    }

    /** Howard Hinnant's days_from_civil. */
    public static function gregorianToEpochDay(int $gy, int $gm, int $gd): int
    {
        if ($gm < 1 || $gm > 12 || $gd < 1 || $gd > self::gregorianDaysInMonth($gy, $gm)) {
            throw new InvalidDateException(sprintf('%d-%02d-%02d does not exist in the Gregorian calendar', $gy, $gm, $gd));
        }
        $y = $gm <= 2 ? $gy - 1 : $gy;
        $era = self::fdiv($y, 400);
        $yoe = $y - $era * 400;
        $mp = ($gm + 9) % 12;
        $doy = intdiv(153 * $mp + 2, 5) + $gd - 1;
        $doe = $yoe * 365 + intdiv($yoe, 4) - intdiv($yoe, 100) + $doy;
        return $era * 146097 + $doe - 719468;
    }

    /**
     * Howard Hinnant's civil_from_days.
     * @return array{year:int, month:int, day:int}
     */
    public static function epochDayToGregorian(int $n): array
    {
        $z = $n + 719468;
        $era = self::fdiv($z, 146097);
        $doe = $z - $era * 146097;
        $yoe = intdiv($doe - intdiv($doe, 1460) + intdiv($doe, 36524) - intdiv($doe, 146096), 365);
        $doy = $doe - (365 * $yoe + intdiv($yoe, 4) - intdiv($yoe, 100));
        $mp = intdiv(5 * $doy + 2, 153);
        $day = $doy - intdiv(153 * $mp + 2, 5) + 1;
        $month = $mp < 10 ? $mp + 3 : $mp - 9;
        return ['year' => $era * 400 + $yoe + ($month <= 2 ? 1 : 0), 'month' => $month, 'day' => $day];
    }

    /**
     * Gregorian → Jalali. Throws InvalidDateException for non-existent or out-of-range dates.
     * @return array{year:int, month:int, day:int}
     */
    public static function toJalali(int $gy, int $gm, int $gd): array
    {
        return self::epochDayToJalali(self::gregorianToEpochDay($gy, $gm, $gd));
    }

    /**
     * Jalali → Gregorian. Throws InvalidDateException for e.g. 30 Esfand of a common year.
     * @return array{year:int, month:int, day:int}
     */
    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        return self::epochDayToGregorian(self::jalaliToEpochDay($jy, $jm, $jd));
    }

    /**
     * Gregorian date of 1 Farvardin of the given Jalali year.
     * @return array{year:int, month:int, day:int}
     */
    public static function nowruz(int $jy): array
    {
        return self::epochDayToGregorian(self::nowruzEpochDay($jy));
    }

    /** 0 = Saturday … 6 = Friday. */
    public static function weekdayOfEpochDay(int $n): int
    {
        return (($n + 5) % 7 + 7) % 7;
    }
}
