<?php
/**
 * PHP side of the comparison (php scripts/compare/nowruz.php).
 *
 * 1. PHP's built-in intl extension (ICU persian calendar): Nowruz of every year 1–2999.
 * 2. The fixed 33-year rule used by hekmatinasser/verta (via hekmatinasser/jalali,
 *    Traits/Validation.php: leap iff year % 33 in [1,5,9,13,17,22,26,30]) re-implemented
 *    here from its source and compared with the astronomical leap years.
 *
 * morilog/jalali (CalendarUtils::jalaliCal) uses the same Borkowski break table as jalaali-js
 * (-61, 9, 38, 199, … 3178), which matches the reference in every certain year.
 */
declare(strict_types=1);

$ref = json_decode(file_get_contents(__DIR__ . '/../../reference/nowruz.json'), true);

// ---------------------------------------------------------------- intl
if (class_exists('IntlDateFormatter')) {
    $fmt = new IntlDateFormatter('en_US@calendar=persian', IntlDateFormatter::NONE, IntlDateFormatter::NONE,
        'UTC', IntlDateFormatter::TRADITIONAL, 'y-M-d');
    $bad = [];
    foreach ($ref as $r) {
        $got = null;
        for ($d = 18; $d <= 24; $d++) {
            $ts = (new DateTimeImmutable(sprintf('%04d-03-%02d 12:00:00', $r['jy'] + 621, $d), new DateTimeZone('UTC')))->getTimestamp();
            if ($fmt->format($ts) === "{$r['jy']}-1-1") {
                $got = sprintf('%04d-03-%02d', $r['jy'] + 621, $d);
                break;
            }
        }
        if ($got !== $r['nowruz'] && !$r['uncertain']) {
            $bad[] = $r['jy'];
        }
    }
    printf("PHP intl (ICU %s): wrong Nowruz in %d certain years%s; in 1300-1500: %s\n", INTL_ICU_VERSION, count($bad),
        $bad ? sprintf(' (%d..%d)', $bad[0], end($bad)) : '',
        implode(' ', array_filter($bad, fn ($y) => $y >= 1300 && $y <= 1500)) ?: 'none');
}

// ---------------------------------------------------------------- 33-year rule (verta)
$rule = fn (int $y): bool => in_array($y % 33, [1, 5, 9, 13, 17, 22, 26, 30], true);
$wrong = [];
foreach ($ref as $r) {
    if (!$r['uncertain'] && $rule($r['jy']) !== $r['leap']) {
        $wrong[] = $r['jy'];
    }
}
$near = array_values(array_filter($wrong, fn ($y) => $y >= 1200 && $y <= 1600));
printf("verta 33-year rule: leap flag wrong in %d certain years; in 1200-1600: %s\n", count($wrong),
    implode(' ', $near) ?: 'none');
