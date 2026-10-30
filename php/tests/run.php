<?php

/**
 * Test runner without dependencies: php php/tests/run.php
 * Runs the same reference data and shared cases as the JavaScript and Python suites.
 */

declare(strict_types=1);

require __DIR__ . '/../autoload.php';

use Taqvim\Calendar;
use Taqvim\Holidays;
use Taqvim\InvalidDateException;
use Taqvim\JalaliDate;
use Taqvim\Locale;
use Taqvim\Parser;
use Taqvim\Taqvim;
use Taqvim\TimeZone;

$root = __DIR__ . '/../../reference/';
$ref = json_decode(file_get_contents($root . 'nowruz.json'), true);
$cases = json_decode(file_get_contents($root . 'test-cases.json'), true);

$failures = 0;
$checks = 0;
$current = '';

function check(bool $ok, string $msg): void
{
    global $failures, $checks, $current;
    $checks++;
    if (!$ok) {
        $failures++;
        if ($failures <= 30) {
            fwrite(STDERR, "  FAIL [$current] $msg\n");
        }
    }
}

function eq($a, $b, string $msg = ''): void
{
    check($a === $b, $msg . ' expected ' . json_encode($b, JSON_UNESCAPED_UNICODE) . ' got ' . json_encode($a, JSON_UNESCAPED_UNICODE));
}

function throws(callable $f, string $msg): void
{
    try {
        $f();
        check(false, "$msg: expected an exception");
    } catch (InvalidDateException) {
        check(true, $msg);
    }
}

function ymd(array $d): array
{
    return [$d['year'], $d['month'], $d['day']];
}

function test(string $name, callable $f): void
{
    global $current, $failures;
    $current = $name;
    $before = $failures;
    $t = microtime(true);
    $f();
    printf("%s %s (%.1fs)\n", $failures === $before ? 'ok  ' : 'FAIL', $name, microtime(true) - $t);
}

test('Nowruz of every year 1–2999 matches the astronomical reference', function () use ($ref) {
    foreach ($ref as $r) {
        $g = Calendar::nowruz($r['jy']);
        eq(sprintf('%04d-%02d-%02d', $g['year'], $g['month'], $g['day']), $r['nowruz'], "year {$r['jy']}");
        eq(Calendar::isLeapYear($r['jy']), $r['leap'], "leap {$r['jy']}");
        eq(Calendar::isUncertainYear($r['jy']), $r['uncertain'], "uncertain {$r['jy']}");
    }
});

test('round trip of every supported day', function () {
    $prev = null;
    for ($n = Calendar::minEpochDay(), $end = Calendar::maxEpochDay(); $n <= $end; $n++) {
        $j = Calendar::epochDayToJalali($n);
        if ($prev) {
            [$py, $pm, $pd] = $prev;
            $exp = $pd < Calendar::daysInMonth($py, $pm) ? [$py, $pm, $pd + 1] : ($pm < 12 ? [$py, $pm + 1, 1] : [$py + 1, 1, 1]);
            if (ymd($j) !== $exp) {
                eq(ymd($j), $exp, "day after " . implode('/', $prev));
            }
        }
        $g = Calendar::epochDayToGregorian($n);
        $back = Calendar::toJalali($g['year'], $g['month'], $g['day']);
        if ($back !== $j) {
            eq($back, $j, "round trip $n");
        }
        $prev = ymd($j);
    }
    check(true, 'done');
});

test('Gregorian arithmetic matches PHP DateTime', function () {
    foreach ([[1970, 1, 1], [2000, 2, 29], [1900, 3, 1], [2025, 3, 21], [622, 3, 22], [3621, 3, 19]] as [$y, $m, $d]) {
        $dt = new DateTimeImmutable(sprintf('%04d-%02d-%02d 00:00:00', $y, $m, $d), new DateTimeZone('UTC'));
        eq(Calendar::gregorianToEpochDay($y, $m, $d), intdiv($dt->getTimestamp(), 86400) - ($dt->getTimestamp() < 0 && $dt->getTimestamp() % 86400 ? 1 : 0), "$y-$m-$d");
    }
});

test('known conversions', function () use ($cases) {
    foreach ($cases['conversions'] as $c) {
        eq(ymd(Calendar::toGregorian(...$c['jalali'])), $c['gregorian'], $c['note'] ?? implode('/', $c['jalali']));
        eq(ymd(Calendar::toJalali(...$c['gregorian'])), $c['jalali']);
    }
});

test('leap years', function () use ($cases) {
    foreach ($cases['leap_years']['leap'] as $y) {
        eq(Calendar::isLeapYear($y), true, (string) $y);
    }
    foreach ($cases['leap_years']['common'] as $y) {
        eq(Calendar::isLeapYear($y), false, (string) $y);
    }
});

test('invalid dates are rejected, never rolled over', function () use ($cases) {
    foreach ($cases['invalid_dates'] as $d) {
        eq(Calendar::isValidDate(...$d), false, implode('/', $d));
        throws(fn () => new JalaliDate(...$d), implode('/', $d));
    }
    try {
        new JalaliDate(1404, 12, 30);
    } catch (InvalidDateException $e) {
        check(str_contains($e->getMessage(), '1404 is not a leap year'), $e->getMessage());
    }
    throws(fn () => Calendar::toJalali(2025, 2, 29), 'Gregorian 2025-02-29');
});

test('parse', function () use ($cases) {
    foreach ($cases['parse'] as $c) {
        if (!empty($c['error'])) {
            throws(fn () => Parser::parse($c['in']), $c['in']);
            eq(JalaliDate::tryParse($c['in']), null, $c['in']);
        } else {
            try {
                eq(ymd(Parser::parse($c['in'])), $c['out'], $c['in']);
            } catch (InvalidDateException $e) {
                check(false, $c['in'] . ': ' . $e->getMessage());
            }
        }
    }
});

test('format', function () use ($cases) {
    foreach ($cases['format'] as $c) {
        $d = new JalaliDate(...$c['date']);
        eq($d->format($c['pattern'], $c['locale'] ?? 'fa', $c['digits'] ?? null), $c['out'], $c['pattern']);
    }
});

test('arithmetic clamps to month end', function () use ($cases) {
    $ops = ['add_days' => 'addDays', 'add_months' => 'addMonths', 'add_years' => 'addYears'];
    foreach ($cases['arithmetic'] as $c) {
        $r = (new JalaliDate(...$c['date']))->{$ops[$c['op']]}($c['n']);
        eq([$r->year, $r->month, $r->day], $c['out'], implode('/', $c['date']) . " {$c['op']} {$c['n']}");
    }
    foreach ($cases['diff'] as $c) {
        $a = new JalaliDate(...$c['a']);
        $b = new JalaliDate(...$c['b']);
        if (isset($c['months'])) {
            eq($a->diffInMonths($b), $c['months'], 'months');
        }
        if (isset($c['years'])) {
            eq($a->diffInYears($b), $c['years'], 'years');
        }
        if (isset($c['days'])) {
            eq($a->diffInDays($b), $c['days'], 'days');
        }
    }
});

test('Asia/Tehran from embedded tzdata', function () use ($cases) {
    foreach ($cases['timezone'] as $c) {
        $ts = (new DateTimeImmutable($c['utc']))->getTimestamp();
        $p = Taqvim::toJalaliParts($ts);
        eq([$p['year'], $p['month'], $p['day']], $c['jalali'], $c['utc']);
        eq([$p['hour'], $p['minute'], $p['second']], $c['time'], $c['utc']);
        eq($p['offset'], $c['offset'], $c['utc']);
        if (empty($c['ambiguous'])) {
            $back = Taqvim::fromJalaliParts($p['year'], $p['month'], $p['day'], $p['hour'], $p['minute'], $p['second']);
            eq($back->getTimestamp(), $ts, 'back ' . $c['utc']);
        }
    }
    $nowruz = new DateTimeImmutable('2025-03-20T20:30:00Z');
    eq(Taqvim::formatDateTime($nowruz, 'YYYY/MM/DD HH:mm Z', 'en'), '1404/01/01 00:00 +03:30');
    eq(JalaliDate::fromDateTime($nowruz)->toString(), '1404/01/01');
    eq(JalaliDate::fromDateTime($nowruz, 'UTC')->toString(), '1403/12/30');
    eq((new JalaliDate(1404, 1, 1))->toDateTime()->getTimestamp(), $nowruz->getTimestamp());
    eq((new JalaliDate(1404, 1, 1))->toDateTime()->format('Y-m-d H:i P'), '2025-03-21 00:00 +03:30');
});

test('properties', function () {
    $d = new JalaliDate(1404, 1, 15);
    eq($d->dayOfWeek(), 6);
    eq($d->dayOfYear(), 15);
    eq($d->season(), 1);
    eq((new JalaliDate(1403, 12, 30))->dayOfYear(), 366);
    eq($d->startOfWeek()->toString(), '1404/01/09');
    eq($d->endOfWeek()->toString(), '1404/01/15');
    eq((new JalaliDate(1404, 12, 1))->endOfMonth()->toString(), '1404/12/29');
    eq($d->toISODate(), '2025-04-04');
    eq(json_encode(['d' => $d]), '{"d":"1404\/01\/15"}');
    eq((string) $d, '1404/01/15');
    eq(JalaliDate::compare(new JalaliDate(1404, 1, 1), new JalaliDate(1404, 1, 2)), -1);
    try {
        $d->year = 1405;
        check(false, 'readonly');
    } catch (Error) {
        check(true, 'readonly');
    }
});

test('holidays and business days', function () use ($cases) {
    eq(array_map(fn ($h) => $h['date']->toString(), Holidays::solarHolidays(1404)), $cases['holidays_1404']);
    eq(Holidays::isHoliday(new JalaliDate(1404, 1, 15)), true);
    eq(Holidays::isHoliday(new JalaliDate(1404, 1, 16)), false);
    eq(Holidays::isHoliday(new JalaliDate(1404, 1, 16), [new JalaliDate(1404, 1, 16)]), true);
    eq(Holidays::addBusinessDays(new JalaliDate(1403, 12, 28), 1)->toString(), '1403/12/30');
});

test('digits and normalisation', function () {
    eq(Locale::toPersianDigits('1404/01/15'), '۱۴۰۴/۰۱/۱۵');
    eq(Locale::toLatinDigits('۱۴۰۴/٠١/۱۵'), '1404/01/15');
    eq(Locale::normalizePersian("علي ك\u{200C}تاب"), 'علی کتاب');
});

printf("\n%d checks, %d failures\n", $checks, $failures);
exit($failures === 0 ? 0 : 1);
