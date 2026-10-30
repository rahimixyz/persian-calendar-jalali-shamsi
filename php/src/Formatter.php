<?php

declare(strict_types=1);

namespace Taqvim;

/** Pattern formatting with the same tokens as the JavaScript and Python packages. */
final class Formatter
{
    public const TOKEN_RE = '/\[([^\]]*)\]|YYYY|YY|MMMM|MM|M|DDD|DD|D|dddd|ddd|d|ww|w|QQQQ|Q|HH|H|hh|h|mm|m|ss|s|A|ZZ|Z/u';

    /**
     * @param array{year:int, month:int, day:int, weekday:int, dayOfYear:int, week:int,
     *              hour?:int, minute?:int, second?:int, offset?:int} $f
     */
    public static function format(array $f, string $pattern, string $locale = 'fa', ?string $digits = null): string
    {
        if (!isset(Locale::MONTHS[$locale])) {
            throw new \InvalidArgumentException("Unknown locale '$locale'. Use 'fa', 'fa-AF' or 'en'.");
        }
        $digits ??= Locale::defaultDigits($locale);
        $num = static fn ($s): string => Locale::convertDigits((string) $s, $digits);
        $time = static function (string $tok, string $key) use ($f): int {
            if (!isset($f[$key])) {
                throw new \LogicException("Token '$tok' needs a time. Use Taqvim::formatDateTime() for dates with a time of day.");
            }
            return $f[$key];
        };

        return preg_replace_callback(self::TOKEN_RE, static function (array $m) use ($f, $locale, $num, $time): string {
            if (str_starts_with($m[0], '[')) {
                return $m[1];
            }
            return match ($m[0]) {
                'YYYY' => $num(sprintf('%04d', $f['year'])),
                'YY' => $num(sprintf('%02d', $f['year'] % 100)),
                'MMMM' => Locale::MONTHS[$locale][$f['month'] - 1],
                'MM' => $num(sprintf('%02d', $f['month'])),
                'M' => $num($f['month']),
                'DDD' => $num($f['dayOfYear']),
                'DD' => $num(sprintf('%02d', $f['day'])),
                'D' => $num($f['day']),
                'dddd' => Locale::WEEKDAYS[$locale][$f['weekday']],
                'ddd' => Locale::SHORT_WEEKDAYS[$locale][$f['weekday']],
                'd' => $num($f['weekday'] + 1),
                'ww' => $num(sprintf('%02d', $f['week'])),
                'w' => $num($f['week']),
                'QQQQ' => Locale::SEASONS[$locale][intdiv($f['month'] - 1, 3)],
                'Q' => $num(intdiv($f['month'] - 1, 3) + 1),
                'HH' => $num(sprintf('%02d', $time('HH', 'hour'))),
                'H' => $num($time('H', 'hour')),
                'hh' => $num(sprintf('%02d', $time('hh', 'hour') % 12 ?: 12)),
                'h' => $num($time('h', 'hour') % 12 ?: 12),
                'mm' => $num(sprintf('%02d', $time('mm', 'minute'))),
                'm' => $num($time('m', 'minute')),
                'ss' => $num(sprintf('%02d', $time('ss', 'second'))),
                's' => $num($time('s', 'second')),
                'A' => Locale::MERIDIEM[$locale][$time('A', 'hour') < 12 ? 0 : 1],
                'Z' => $num(TimeZone::formatOffset($time('Z', 'offset'))),
                'ZZ' => $num(TimeZone::formatOffset($time('ZZ', 'offset'), false)),
                default => $m[0],
            };
        }, $pattern);
    }
}
