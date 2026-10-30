<?php

declare(strict_types=1);

namespace Taqvim;

/** Month and weekday names, digit conversion and Persian text normalisation. */
final class Locale
{
    public const MONTHS = [
        'fa' => ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'],
        'fa-AF' => ['حمل', 'ثور', 'جوزا', 'سرطان', 'اسد', 'سنبله', 'میزان', 'عقرب', 'قوس', 'جدی', 'دلو', 'حوت'],
        'en' => ['Farvardin', 'Ordibehesht', 'Khordad', 'Tir', 'Mordad', 'Shahrivar', 'Mehr', 'Aban', 'Azar', 'Dey', 'Bahman', 'Esfand'],
    ];

    /** Index 0 = Saturday. */
    public const WEEKDAYS = [
        'fa' => ['شنبه', 'یکشنبه', 'دوشنبه', "سه\u{200C}شنبه", 'چهارشنبه', 'پنجشنبه', 'جمعه'],
        'fa-AF' => ['شنبه', 'یکشنبه', 'دوشنبه', "سه\u{200C}شنبه", 'چهارشنبه', 'پنجشنبه', 'جمعه'],
        'en' => ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
    ];

    public const SHORT_WEEKDAYS = [
        'fa' => ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'],
        'fa-AF' => ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'],
        'en' => ['Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
    ];

    public const SEASONS = [
        'fa' => ['بهار', 'تابستان', 'پاییز', 'زمستان'],
        'fa-AF' => ['بهار', 'تابستان', 'خزان', 'زمستان'],
        'en' => ['Spring', 'Summer', 'Autumn', 'Winter'],
    ];

    public const MERIDIEM = ['fa' => ['ق.ظ', 'ب.ظ'], 'fa-AF' => ['ق.ظ', 'ب.ظ'], 'en' => ['AM', 'PM']];

    private const PERSIAN = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    private const ARABIC = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    private const LATIN = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    public static function defaultDigits(string $locale): string
    {
        return $locale === 'en' ? 'latin' : 'persian';
    }

    /** Persian ۰-۹ and Arabic-Indic ٠-٩ → 0-9 (also ٫ → . and ٬ → ,). */
    public static function toLatinDigits(string|int $text): string
    {
        $s = str_replace(self::PERSIAN, self::LATIN, (string) $text);
        $s = str_replace(self::ARABIC, self::LATIN, $s);
        return str_replace(["\u{066B}", "\u{066C}"], ['.', ','], $s);
    }

    public static function toPersianDigits(string|int $text): string
    {
        return self::convertDigits($text, 'persian');
    }

    /** @param string $to 'persian' | 'arabic' | 'latin' */
    public static function convertDigits(string|int $text, string $to): string
    {
        $s = self::toLatinDigits($text);
        return match ($to) {
            'latin' => $s,
            'persian' => str_replace(self::LATIN, self::PERSIAN, $s),
            'arabic' => str_replace(self::LATIN, self::ARABIC, $s),
            default => throw new \InvalidArgumentException("digits must be 'persian', 'arabic' or 'latin'"),
        };
    }

    /**
     * Arabic ي/ى/ك → Persian ی/ک, strip tatweel, diacritics, ZWNJ and RTL marks,
     * collapse whitespace, convert digits to Latin.
     */
    public static function normalizePersian(string $text): string
    {
        $s = self::toLatinDigits($text);
        $s = strtr($s, ['ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک', 'ة' => 'ه', 'أ' => 'ا', 'إ' => 'ا', 'ٱ' => 'ا', 'ـ' => '']);
        $s = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $s);
        $s = preg_replace('/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{061C}\x{FEFF}]/u', '', $s);
        return trim(preg_replace('/\s+/u', ' ', $s));
    }
}
