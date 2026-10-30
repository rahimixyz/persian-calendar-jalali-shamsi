<?php

declare(strict_types=1);

namespace Taqvim;

/** Parse Jalali dates the way people actually write them (same rules as the JS and Python packages). */
final class Parser
{
    private const L = '\p{L}';

    private const ALIASES = [
        1 => ['فروردین', 'farvardin', 'حمل', 'hamal'],
        2 => ['اردیبهشت', 'ordibehesht', 'ordibehest', 'ثور', 'sawr', 'saur', 'sowr'],
        3 => ['خرداد', 'khordad', 'khordaad', 'جوزا', 'جوزاء', 'jawza', 'jowza', 'jauza'],
        4 => ['تیر', 'tir', 'سرطان', 'saratan'],
        5 => ['مرداد', 'امرداد', 'mordad', 'amordad', 'mordaad', 'اسد', 'asad'],
        6 => ['شهریور', 'shahrivar', 'سنبله', 'sonbola', 'sunbula', 'sombola'],
        7 => ['مهر', 'mehr', 'میزان', 'mizan'],
        8 => ['آبان', 'ابان', 'aban', 'aaban', 'عقرب', 'aqrab', 'akrab'],
        9 => ['آذر', 'اذر', 'azar', 'aazar', 'قوس', 'qaws', 'qaus', 'qows'],
        10 => ['دی', 'dey', 'dei', 'جدی', 'jadi', 'jady'],
        11 => ['بهمن', 'bahman', 'دلو', 'dalw', 'dalv'],
        12 => ['اسفند', 'esfand', 'espand', 'حوت', 'hut', 'hoot'],
    ];

    /** @var array<int, array{0:string,1:int}>|null longest names first */
    private static ?array $sorted = null;

    private static function aliases(): array
    {
        if (self::$sorted === null) {
            $list = [];
            foreach (self::ALIASES as $m => $names) {
                foreach ($names as $n) {
                    $list[] = [$n, $m];
                }
            }
            usort($list, static fn ($a, $b) => mb_strlen($b[0]) <=> mb_strlen($a[0]));
            self::$sorted = $list;
        }
        return self::$sorted;
    }

    private static function fail(string $text, string $why): never
    {
        throw new InvalidDateException(sprintf('Cannot parse "%s" as a Jalali date: %s', $text, $why));
    }

    /**
     * Parse "1404/01/15", "۱۴۰۴-۱-۱۵", "15/01/1404", "۱۵ فروردین ۱۴۰۴", "شنبه ۱۶ فروردین ۱۴۰۴ ه.ش",
     * "15 Farvardin 1404", "Farvardin 15, 1404" or "۱۵ حمل ۱۴۰۴" (Dari). Validates strictly.
     *
     * @param string|null $order 'ymd' or 'dmy'; only used when the year cannot be recognised by its 3–4 digits
     * @return array{year:int, month:int, day:int}
     */
    public static function parse(string $text, ?string $order = null): array
    {
        $L = self::L;
        $s = mb_strtolower(Locale::normalizePersian($text));
        $s = preg_replace("/(?<!$L)(?:(?:(?:یک|دو|سه|چهار|پنج)\\s?)?شنبه|جمعه|saturday|sunday|monday|tuesday|wednesday|thursday|friday|sat|sun|mon|tue|wed|thu|fri)(?!$L)/u", ' ', $s);
        $s = preg_replace("/(?<!$L)(?:(?:هجری\\s?)?(?:شمسی|خورشیدی)|ه\\s?\\.?\\s?ش\\.?|h\\.?\\s?sh\\.?|s\\.?h\\.?|a\\.?p\\.?|sh|سال|ماه|روز)(?!$L)/u", ' ', $s);
        $s = preg_replace("/(\\d+)\\s?(?:ام|م|th|st|nd|rd)(?!$L)/u", '$1', $s);
        $s = trim(preg_replace('/\s+/u', ' ', preg_replace('/[,،]/u', ' ', $s)));
        if ($s === '') {
            self::fail($text, 'empty input');
        }

        $month = null;
        foreach (self::aliases() as [$name, $m]) {
            $re = '/(?<!' . $L . ')' . preg_quote($name, '/') . '(?!' . $L . ')/u';
            if (preg_match($re, $s)) {
                $month = $m;
                $s = preg_replace($re, ' ', $s, 1);
                break;
            }
        }

        if ($month !== null) {
            if (preg_match('/[^\d\s\/.\-]/u', $s)) {
                self::fail($text, 'unexpected text');
            }
            preg_match_all('/\d+/', $s, $mm);
            $nums = $mm[0];
            if (count($nums) !== 2) {
                self::fail($text, 'expected a day and a year next to the month name');
            }
            [$a, $b] = $nums;
            if (strlen($a) >= 3 && strlen($b) <= 2) {
                [$year, $day] = [(int) $a, (int) $b];
            } elseif (strlen($b) >= 3 && strlen($a) <= 2) {
                [$year, $day] = [(int) $b, (int) $a];
            } else {
                self::fail($text, 'the year must have 3–4 digits');
            }
        } else {
            if (!preg_match('/^(\d{1,4})\s*[\/.\-\s]\s*(\d{1,2})\s*[\/.\-\s]\s*(\d{1,4})$/', $s, $m)) {
                self::fail($text, 'unrecognised format');
            }
            [, $a, $b, $c] = $m;
            if (strlen($a) >= 3 && strlen($c) <= 2) {
                [$year, $month, $day] = [(int) $a, (int) $b, (int) $c];
            } elseif (strlen($c) >= 3 && strlen($a) <= 2) {
                [$year, $month, $day] = [(int) $c, (int) $b, (int) $a];
            } elseif ($order === 'ymd') {
                [$year, $month, $day] = [(int) $a, (int) $b, (int) $c];
            } elseif ($order === 'dmy') {
                [$year, $month, $day] = [(int) $c, (int) $b, (int) $a];
            } else {
                self::fail($text, 'cannot tell the year from the day; write the year with 4 digits');
            }
        }
        try {
            Calendar::checkDate($year, $month, $day);
        } catch (InvalidDateException $e) {
            self::fail($text, $e->getMessage());
        }
        return ['year' => $year, 'month' => $month, 'day' => $day];
    }
}
