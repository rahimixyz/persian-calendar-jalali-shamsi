<?php

declare(strict_types=1);

namespace Taqvim;

/**
 * Time-zone handling.
 *
 * Asia/Tehran is resolved from an embedded copy of the IANA history (tzdata 2026e), so
 * results do not depend on the timezonedb compiled into PHP. Iran abolished daylight saving
 * time in 2022; PHP builds with older timezonedb still add an hour every spring.
 * Other zones use PHP's DateTimeZone.
 */
final class TimeZone
{
    public const DEFAULT = 'Asia/Tehran';
    private const TEHRAN = ['Asia/Tehran' => true, 'Iran' => true];

    private static function tehranOffset(int $utcSeconds): int
    {
        $t = Data::TEHRAN_TRANSITIONS;
        if ($utcSeconds < $t[0][0]) {
            return Data::TEHRAN_INITIAL_OFFSET;
        }
        $lo = 0;
        $hi = count($t) - 1;
        while ($lo < $hi) {
            $mid = ($lo + $hi + 1) >> 1;
            if ($t[$mid][0] <= $utcSeconds) {
                $lo = $mid;
            } else {
                $hi = $mid - 1;
            }
        }
        return $t[$lo][1];
    }

    /** UTC offset in minutes of a zone at an instant (210 for Tehran today). */
    public static function offsetMinutes(int $utcSeconds, string $timeZone = self::DEFAULT): int
    {
        if (isset(self::TEHRAN[$timeZone])) {
            return self::tehranOffset($utcSeconds);
        }
        if ($timeZone === 'UTC' || $timeZone === 'Etc/UTC' || $timeZone === 'GMT') {
            return 0;
        }
        try {
            $tz = new \DateTimeZone($timeZone);
        } catch (\Exception) {
            throw new \InvalidArgumentException("Unknown time zone: $timeZone");
        }
        return intdiv($tz->getOffset(new \DateTimeImmutable('@' . $utcSeconds)), 60);
    }

    /**
     * Instant → local wall-clock time in the zone.
     * @return array{epochDay:int, hour:int, minute:int, second:int, offset:int}
     */
    public static function toWallTime(int $utcSeconds, string $timeZone = self::DEFAULT): array
    {
        $offset = self::offsetMinutes($utcSeconds, $timeZone);
        $local = $utcSeconds + $offset * 60;
        $epochDay = (int) floor($local / 86400);
        $s = $local - $epochDay * 86400;
        return [
            'epochDay' => $epochDay,
            'hour' => intdiv($s, 3600),
            'minute' => intdiv($s % 3600, 60),
            'second' => $s % 60,
            'offset' => $offset,
        ];
    }

    /**
     * Local wall-clock time → instant (unix seconds). Non-existent times (spring-forward gap)
     * move forward; ambiguous times resolve to the earlier instant.
     */
    public static function fromWallTime(int $epochDay, int $hour = 0, int $minute = 0, int $second = 0,
                                        string $timeZone = self::DEFAULT): int
    {
        $local = $epochDay * 86400 + $hour * 3600 + $minute * 60 + $second;
        $before = self::offsetMinutes($local - 86400, $timeZone);
        $after = self::offsetMinutes($local + 86400, $timeZone);
        $candidates = [];
        foreach (array_unique([$before, $after]) as $o) {
            $t = $local - $o * 60;
            if (self::offsetMinutes($t, $timeZone) === $o) {
                $candidates[] = $t;
            }
        }
        return $candidates ? min($candidates) : $local - $before * 60;
    }

    public static function formatOffset(int $minutes, bool $colon = true): string
    {
        $a = abs($minutes);
        return ($minutes < 0 ? '-' : '+') . sprintf('%02d', intdiv($a, 60)) . ($colon ? ':' : '') . sprintf('%02d', $a % 60);
    }

    /** A fixed-offset DateTimeZone for the given offset in minutes, e.g. +03:30. */
    public static function fixed(int $offsetMinutes): \DateTimeZone
    {
        return new \DateTimeZone(self::formatOffset($offsetMinutes));
    }
}
