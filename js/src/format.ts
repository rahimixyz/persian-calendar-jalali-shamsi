import { convertDigits, defaultDigits, Digits, Locale, MERIDIEM, MONTHS, SEASONS, WEEKDAYS } from './locale.js';
import { formatOffset } from './timezone.js';

export interface FormatOptions {
  /** 'fa' (default, Iranian month names), 'fa-AF' (Dari month names: حمل، ثور…), or 'en'. */
  locale?: Locale;
  /** Digits for numbers; defaults to Persian for 'fa'/'fa-AF' and Latin for 'en'. */
  digits?: Digits;
}

export interface FormatFields {
  year: number;
  month: number;
  day: number;
  weekday: number; // 0 = Saturday
  dayOfYear: number; // 1-based
  week: number;
  hour?: number;
  minute?: number;
  second?: number;
  offset?: number;
}

const SHORT_WEEKDAYS: Record<Locale, readonly string[]> = {
  fa: ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'],
  'fa-AF': ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'],
  en: ['Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
};

/**
 * Tokens:
 * YYYY 1404 · YY 04 · M 1 · MM 01 · MMMM فروردین · D 5 · DD 05 · DDD day of year
 * d weekday 1–7 (Saturday = 1) · ddd ش / Sat · dddd شنبه / Saturday · w ww week of year
 * Q season 1–4 · QQQQ بهار / Spring
 * H HH h hh m mm s ss A (time) · Z +03:30 · ZZ +0330 · [text] literal
 */
export const TOKEN_RE = /\[([^\]]*)\]|YYYY|YY|MMMM|MM|M|DDD|DD|D|dddd|ddd|d|ww|w|QQQQ|Q|HH|H|hh|h|mm|m|ss|s|A|ZZ|Z/g;

const pad = (n: number, w = 2) => String(n).padStart(w, '0');

export function formatFields(f: FormatFields, pattern: string, opts: FormatOptions = {}): string {
  const locale: Locale = opts.locale ?? 'fa';
  if (!(locale in MONTHS)) throw new RangeError(`Unknown locale "${locale}". Use 'fa', 'fa-AF' or 'en'.`);
  const digits = opts.digits ?? defaultDigits(locale);
  const num = (s: string) => convertDigits(s, digits);
  const time = (token: string, v: number | undefined): number => {
    if (v === undefined) {
      throw new Error(`Token "${token}" needs a time. Use formatDateTime() for dates with a time of day.`);
    }
    return v;
  };
  return pattern.replace(TOKEN_RE, (tok: string, literal?: string) => {
    if (literal !== undefined) return literal;
    switch (tok) {
      case 'YYYY': return num(pad(f.year, 4));
      case 'YY': return num(pad(f.year % 100));
      case 'MMMM': return MONTHS[locale][f.month - 1];
      case 'MM': return num(pad(f.month));
      case 'M': return num(String(f.month));
      case 'DDD': return num(String(f.dayOfYear));
      case 'DD': return num(pad(f.day));
      case 'D': return num(String(f.day));
      case 'dddd': return WEEKDAYS[locale][f.weekday];
      case 'ddd': return SHORT_WEEKDAYS[locale][f.weekday];
      case 'd': return num(String(f.weekday + 1));
      case 'ww': return num(pad(f.week));
      case 'w': return num(String(f.week));
      case 'QQQQ': return SEASONS[locale][Math.floor((f.month - 1) / 3)];
      case 'Q': return num(String(Math.floor((f.month - 1) / 3) + 1));
      case 'HH': return num(pad(time(tok, f.hour)));
      case 'H': return num(String(time(tok, f.hour)));
      case 'hh': return num(pad(time(tok, f.hour) % 12 || 12));
      case 'h': return num(String(time(tok, f.hour) % 12 || 12));
      case 'mm': return num(pad(time(tok, f.minute)));
      case 'm': return num(String(time(tok, f.minute)));
      case 'ss': return num(pad(time(tok, f.second)));
      case 's': return num(String(time(tok, f.second)));
      case 'A': return MERIDIEM[locale][time(tok, f.hour) < 12 ? 0 : 1];
      case 'Z': return num(formatOffset(time(tok, f.offset)));
      case 'ZZ': return num(formatOffset(time(tok, f.offset), false));
      default: return tok;
    }
  });
}
