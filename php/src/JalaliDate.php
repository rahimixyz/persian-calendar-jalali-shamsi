<?php

declare(strict_types=1);

namespace Taqvim;

/**
 * An immutable date in the Solar Hijri (Jalali / Shamsi / Persian) calendar.
 *
 * ```php
 * $d = new JalaliDate(1404, 1, 15);
 * $d->format('dddd D MMMM YYYY');   // 'جمعه ۱۵ فروردین ۱۴۰۴'
 * $d->addMonths(1)->toString();      // '1404/02/15'
 * $d->toDateTime()->format('Y-m-d'); // '2025-04-04'
 * JalaliDate::parse('۱۵ فروردین ۱۴۰۴')->equals($d); // true
 * ```
 */
final class JalaliDate implements \JsonSerializable, \Stringable
{
    public readonly int $year;
    public readonly int $month;
    public readonly int $day;
    /** Days since 1970-01-01 (proleptic Gregorian). */
    public readonly int $epochDay;

    /** @throws InvalidDateException for dates that do not exist, e.g. new JalaliDate(1404, 12, 30) */
    public function __construct(int $year, int $month, int $day)
    {
        $this->epochDay = Calendar::jalaliToEpochDay($year, $month, $day);
        $this->year = $year;
        $this->month = $month;
        $this->day = $day;
    }

    // ------------------------------------------------------------------ construction

    public static function fromEpochDay(int $epochDay): self
    {
        ['year' => $y, 'month' => $m, 'day' => $d] = Calendar::epochDayToJalali($epochDay);
        return new self($y, $m, $d);
    }

    public static function fromGregorian(int $year, int $month, int $day): self
    {
        return self::fromEpochDay(Calendar::gregorianToEpochDay($year, $month, $day));
    }

    /**
     * The calendar date of an instant in a time zone (default Asia/Tehran).
     * Accepts a DateTimeInterface or a unix timestamp.
     */
    public static function fromDateTime(\DateTimeInterface|int $instant, string $timeZone = TimeZone::DEFAULT): self
    {
        $ts = is_int($instant) ? $instant : $instant->getTimestamp();
        return self::fromEpochDay(TimeZone::toWallTime($ts, $timeZone)['epochDay']);
    }

    /** Today in Tehran (or in the given zone). */
    public static function today(string $timeZone = TimeZone::DEFAULT): self
    {
        return self::fromDateTime(time(), $timeZone);
    }

    /** See Parser::parse for the accepted formats. Throws InvalidDateException with a readable reason. */
    public static function parse(string $text, ?string $order = null): self
    {
        ['year' => $y, 'month' => $m, 'day' => $d] = Parser::parse($text, $order);
        return new self($y, $m, $d);
    }

    /** Like parse(), but returns null instead of throwing. */
    public static function tryParse(string $text, ?string $order = null): ?self
    {
        try {
            return self::parse($text, $order);
        } catch (InvalidDateException) {
            return null;
        }
    }

    public static function isValid(int $year, int $month, int $day): bool
    {
        return Calendar::isValidDate($year, $month, $day);
    }

    public static function compare(self $a, self $b): int
    {
        return $a->epochDay <=> $b->epochDay;
    }

    // ------------------------------------------------------------------ properties

    /** 0 = Saturday (شنبه) … 6 = Friday (جمعه). */
    public function dayOfWeek(): int
    {
        return Calendar::weekdayOfEpochDay($this->epochDay);
    }

    /** 1 … 366. */
    public function dayOfYear(): int
    {
        return $this->epochDay - Calendar::nowruzEpochDay($this->year) + 1;
    }

    /** Week 1 contains 1 Farvardin; weeks start on Saturday. */
    public function weekOfYear(): int
    {
        $nz = Calendar::nowruzEpochDay($this->year);
        return intdiv($this->epochDay - $nz + Calendar::weekdayOfEpochDay($nz), 7) + 1;
    }

    /** Season 1–4 (spring = Farvardin–Khordad). */
    public function season(): int
    {
        return intdiv($this->month - 1, 3) + 1;
    }

    public function isLeapYear(): bool
    {
        return Calendar::isLeapYear($this->year);
    }

    public function daysInMonth(): int
    {
        return Calendar::daysInMonth($this->year, $this->month);
    }

    public function daysInYear(): int
    {
        return Calendar::daysInYear($this->year);
    }

    public function isUncertainYear(): bool
    {
        return Calendar::isUncertainYear($this->year);
    }

    // ------------------------------------------------------------------ arithmetic

    public function addDays(int $days): self
    {
        return self::fromEpochDay($this->epochDay + $days);
    }

    public function addWeeks(int $weeks): self
    {
        return $this->addDays($weeks * 7);
    }

    /** Clamps to the month end: 31 Shahrivar + 1 month = 30 Mehr. */
    public function addMonths(int $months): self
    {
        $total = $this->year * 12 + ($this->month - 1) + $months;
        $y = intdiv($total, 12);
        $m = $total - $y * 12 + 1;
        if ($y < Calendar::MIN_YEAR || $y > Calendar::MAX_YEAR) {
            throw new InvalidDateException(sprintf(
                'Result is outside the supported range %d–%d', Calendar::MIN_YEAR, Calendar::MAX_YEAR
            ));
        }
        return new self($y, $m, min($this->day, Calendar::daysInMonth($y, $m)));
    }

    /** Clamps 30 Esfand to 29 Esfand in common years. */
    public function addYears(int $years): self
    {
        return $this->addMonths($years * 12);
    }

    /** Replace fields; the result must exist (no silent clamping). */
    public function with(?int $year = null, ?int $month = null, ?int $day = null): self
    {
        return new self($year ?? $this->year, $month ?? $this->month, $day ?? $this->day);
    }

    public function startOfMonth(): self
    {
        return new self($this->year, $this->month, 1);
    }

    public function endOfMonth(): self
    {
        return new self($this->year, $this->month, $this->daysInMonth());
    }

    public function startOfYear(): self
    {
        return new self($this->year, 1, 1);
    }

    public function endOfYear(): self
    {
        return new self($this->year, 12, Calendar::daysInMonth($this->year, 12));
    }

    /** The Saturday on or before this date. */
    public function startOfWeek(): self
    {
        return $this->addDays(-$this->dayOfWeek());
    }

    /** The Friday on or after this date. */
    public function endOfWeek(): self
    {
        return $this->addDays(6 - $this->dayOfWeek());
    }

    /** $this − $other, in days. */
    public function diffInDays(self $other): int
    {
        return $this->epochDay - $other->epochDay;
    }

    /** Whole calendar months from $other to $this (negative if $this is earlier). */
    public function diffInMonths(self $other): int
    {
        if ($this->epochDay < $other->epochDay) {
            return -$other->diffInMonths($this);
        }
        $months = ($this->year - $other->year) * 12 + ($this->month - $other->month);
        if ($this->day < $other->day && $this->day !== $this->daysInMonth()) {
            $months--;
        }
        return $months;
    }

    /** Whole years; a 30 Esfand birthday counts on 29 Esfand in common years. */
    public function diffInYears(self $other): int
    {
        return intdiv($this->diffInMonths($other), 12);
    }

    // ------------------------------------------------------------------ comparison

    public function equals(self $other): bool
    {
        return $this->epochDay === $other->epochDay;
    }

    public function isBefore(self $other): bool
    {
        return $this->epochDay < $other->epochDay;
    }

    public function isAfter(self $other): bool
    {
        return $this->epochDay > $other->epochDay;
    }

    /** Inclusive. */
    public function isBetween(self $start, self $end): bool
    {
        return $this->epochDay >= $start->epochDay && $this->epochDay <= $end->epochDay;
    }

    // ------------------------------------------------------------------ conversion

    /** @return array{year:int, month:int, day:int} */
    public function toGregorian(): array
    {
        return Calendar::epochDayToGregorian($this->epochDay);
    }

    /** Gregorian ISO-8601 date, e.g. "2025-04-04". */
    public function toISODate(): string
    {
        $g = $this->toGregorian();
        return sprintf('%04d-%02d-%02d', $g['year'], $g['month'], $g['day']);
    }

    /**
     * A DateTimeImmutable at the given wall-clock time (default 00:00) in Tehran or another zone.
     * For Asia/Tehran the result carries a fixed +03:30 / +04:30 offset computed from embedded
     * tzdata, so it is correct even when PHP's own timezonedb is outdated.
     */
    public function toDateTime(int $hour = 0, int $minute = 0, int $second = 0,
                               string $timeZone = TimeZone::DEFAULT): \DateTimeImmutable
    {
        $ts = TimeZone::fromWallTime($this->epochDay, $hour, $minute, $second, $timeZone);
        $offset = TimeZone::offsetMinutes($ts, $timeZone);
        return (new \DateTimeImmutable('@' . $ts))->setTimezone(TimeZone::fixed($offset));
    }

    /** Format with tokens such as 'YYYY/MM/DD' or 'dddd D MMMM YYYY'. Persian digits by default. */
    public function format(string $pattern = 'YYYY/MM/DD', string $locale = 'fa', ?string $digits = null): string
    {
        return Formatter::format([
            'year' => $this->year, 'month' => $this->month, 'day' => $this->day,
            'weekday' => $this->dayOfWeek(), 'dayOfYear' => $this->dayOfYear(), 'week' => $this->weekOfYear(),
        ], $pattern, $locale, $digits);
    }

    /** "1404/01/15" with Latin digits — stable for logs, keys and JSON. */
    public function toString(): string
    {
        return sprintf('%04d/%02d/%02d', $this->year, $this->month, $this->day);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function jsonSerialize(): string
    {
        return $this->toString();
    }
}
