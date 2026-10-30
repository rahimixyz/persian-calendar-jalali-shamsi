<?php

declare(strict_types=1);

namespace Taqvim;

/**
 * Convenience entry point with the same function names as the JavaScript package.
 *
 * ```php
 * use Taqvim\Taqvim;
 * Taqvim::toJalali(2025, 3, 21);   // ['year' => 1404, 'month' => 1, 'day' => 1]
 * Taqvim::toGregorian(1403, 12, 30); // ['year' => 2025, 'month' => 3, 'day' => 20]
 * Taqvim::formatDateTime(new DateTime(), 'YYYY/MM/DD HH:mm');
 * ```
 */
final class Taqvim
{
    public const VERSION = '1.0.0';

    /** @return array{year:int, month:int, day:int} */
    public static function toJalali(int $gy, int $gm, int $gd): array
    {
        return Calendar::toJalali($gy, $gm, $gd);
    }

    /** @return array{year:int, month:int, day:int} */
    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        return Calendar::toGregorian($jy, $jm, $jd);
    }

    public static function isLeapYear(int $jy): bool
    {
        return Calendar::isLeapYear($jy);
    }

    public static function daysInMonth(int $jy, int $jm): int
    {
        return Calendar::daysInMonth($jy, $jm);
    }

    public static function isValidDate(int $jy, int $jm, int $jd): bool
    {
        return Calendar::isValidDate($jy, $jm, $jd);
    }

    public static function isUncertainYear(int $jy): bool
    {
        return Calendar::isUncertainYear($jy);
    }

    /** @return array{year:int, month:int, day:int} */
    public static function nowruz(int $jy): array
    {
        return Calendar::nowruz($jy);
    }

    /** @return array{year:int, month:int, day:int} */
    public static function parseJalali(string $text, ?string $order = null): array
    {
        return Parser::parse($text, $order);
    }

    /**
     * Split an instant into Jalali date + wall-clock time in a zone (default Asia/Tehran).
     * @return array{year:int, month:int, day:int, hour:int, minute:int, second:int, weekday:int, offset:int}
     */
    public static function toJalaliParts(\DateTimeInterface|int $instant, string $timeZone = TimeZone::DEFAULT): array
    {
        $ts = is_int($instant) ? $instant : $instant->getTimestamp();
        $w = TimeZone::toWallTime($ts, $timeZone);
        $j = Calendar::epochDayToJalali($w['epochDay']);
        return $j + [
            'hour' => $w['hour'], 'minute' => $w['minute'], 'second' => $w['second'],
            'weekday' => Calendar::weekdayOfEpochDay($w['epochDay']), 'offset' => $w['offset'],
        ];
    }

    /** Build an instant from a Jalali date and wall-clock time in a zone (default Asia/Tehran). */
    public static function fromJalaliParts(int $year, int $month, int $day, int $hour = 0, int $minute = 0,
                                           int $second = 0, string $timeZone = TimeZone::DEFAULT): \DateTimeImmutable
    {
        return (new JalaliDate($year, $month, $day))->toDateTime($hour, $minute, $second, $timeZone);
    }

    /** formatDateTime(new DateTime(), 'dddd D MMMM YYYY، HH:mm') → 'شنبه ۹ آبان ۱۴۰۵، ۱۴:۳۰' */
    public static function formatDateTime(\DateTimeInterface|int $instant, string $pattern = 'YYYY/MM/DD HH:mm',
                                          string $locale = 'fa', ?string $digits = null,
                                          string $timeZone = TimeZone::DEFAULT): string
    {
        $p = self::toJalaliParts($instant, $timeZone);
        $d = new JalaliDate($p['year'], $p['month'], $p['day']);
        return Formatter::format($p + [
            'dayOfYear' => $d->dayOfYear(), 'week' => $d->weekOfYear(),
        ], $pattern, $locale, $digits);
    }

    public static function toPersianDigits(string|int $text): string
    {
        return Locale::toPersianDigits($text);
    }

    public static function toLatinDigits(string|int $text): string
    {
        return Locale::toLatinDigits($text);
    }

    public static function normalizePersian(string $text): string
    {
        return Locale::normalizePersian($text);
    }
}
