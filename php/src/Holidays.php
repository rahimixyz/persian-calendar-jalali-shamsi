<?php

declare(strict_types=1);

namespace Taqvim;

/**
 * Fixed-date public holidays of Iran in the Solar Hijri calendar.
 *
 * Islamic (lunar Hijri) holidays are NOT included: Iran fixes them each year by moon
 * sighting, so they cannot be computed reliably in advance. Pass them via $extra.
 */
final class Holidays
{
    public const FIXED_SOLAR = [
        [1, 1, 'جشن نوروز', 'Nowruz'],
        [1, 2, 'عید نوروز', 'Nowruz'],
        [1, 3, 'عید نوروز', 'Nowruz'],
        [1, 4, 'عید نوروز', 'Nowruz'],
        [1, 12, 'روز جمهوری اسلامی', 'Islamic Republic Day'],
        [1, 13, 'روز طبیعت', 'Nature Day (Sizdah Bedar)'],
        [3, 14, 'رحلت امام خمینی', 'Death of Ayatollah Khomeini'],
        [3, 15, 'قیام ۱۵ خرداد', '15 Khordad Uprising'],
        [11, 22, 'پیروزی انقلاب اسلامی', 'Revolution Day'],
        [12, 29, 'ملی شدن صنعت نفت', 'Oil Nationalisation Day'],
    ];

    /** @return list<array{date: JalaliDate, fa: string, en: string}> */
    public static function solarHolidays(int $year): array
    {
        return array_map(
            static fn (array $h) => ['date' => new JalaliDate($year, $h[0], $h[1]), 'fa' => $h[2], 'en' => $h[3]],
            self::FIXED_SOLAR
        );
    }

    /** @param int[] $weekendDays 0 = Saturday … 6 = Friday. Default: Friday. */
    public static function isWeekend(JalaliDate $date, array $weekendDays = [6]): bool
    {
        return in_array($date->dayOfWeek(), $weekendDays, true);
    }

    /**
     * @param JalaliDate[] $extra e.g. this year's officially announced lunar holidays
     * @param int[] $weekendDays
     */
    public static function isHoliday(JalaliDate $date, array $extra = [], array $weekendDays = [6]): bool
    {
        if (self::isWeekend($date, $weekendDays)) {
            return true;
        }
        foreach (self::FIXED_SOLAR as [$m, $d]) {
            if ($date->month === $m && $date->day === $d) {
                return true;
            }
        }
        foreach ($extra as $e) {
            if ($e->equals($date)) {
                return true;
            }
        }
        return false;
    }

    /** Add business days, skipping weekends and holidays. */
    public static function addBusinessDays(JalaliDate $date, int $days, array $extra = [], array $weekendDays = [6]): JalaliDate
    {
        $step = $days < 0 ? -1 : 1;
        $left = abs($days);
        $d = $date;
        while ($left > 0) {
            $d = $d->addDays($step);
            if (!self::isHoliday($d, $extra, $weekendDays)) {
                $left--;
            }
        }
        return $d;
    }
}
