/** Month and weekday names, digit conversion and Persian text normalisation. */

export type Locale = 'fa' | 'fa-AF' | 'en';
export type Digits = 'persian' | 'arabic' | 'latin';

export const MONTHS: Record<Locale, readonly string[]> = {
  fa: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'],
  // Dari (Afghanistan) solar month names, same calendar
  'fa-AF': ['حمل', 'ثور', 'جوزا', 'سرطان', 'اسد', 'سنبله', 'میزان', 'عقرب', 'قوس', 'جدی', 'دلو', 'حوت'],
  en: ['Farvardin', 'Ordibehesht', 'Khordad', 'Tir', 'Mordad', 'Shahrivar', 'Mehr', 'Aban', 'Azar', 'Dey', 'Bahman', 'Esfand'],
};

/** Index 0 = Saturday. */
export const WEEKDAYS: Record<Locale, readonly string[]> = {
  fa: ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'],
  'fa-AF': ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'],
  en: ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
};

export const SEASONS: Record<Locale, readonly string[]> = {
  fa: ['بهار', 'تابستان', 'پاییز', 'زمستان'],
  'fa-AF': ['بهار', 'تابستان', 'خزان', 'زمستان'],
  en: ['Spring', 'Summer', 'Autumn', 'Winter'],
};

export const MERIDIEM: Record<Locale, readonly [string, string]> = {
  fa: ['ق.ظ', 'ب.ظ'],
  'fa-AF': ['ق.ظ', 'ب.ظ'],
  en: ['AM', 'PM'],
};

export function defaultDigits(locale: Locale): Digits {
  return locale === 'en' ? 'latin' : 'persian';
}

const PERSIAN = '۰۱۲۳۴۵۶۷۸۹';
const ARABIC = '٠١٢٣٤٥٦٧٨٩';

/** Convert any Latin, Persian or Arabic-Indic digits in a string to the requested digit set. */
export function convertDigits(text: string | number, to: Digits): string {
  const s = toLatinDigits(String(text));
  if (to === 'latin') return s;
  const set = to === 'persian' ? PERSIAN : ARABIC;
  return s.replace(/[0-9]/g, (d) => set[+d]);
}

export function toPersianDigits(text: string | number): string {
  return convertDigits(text, 'persian');
}

/** Persian ۰-۹ and Arabic-Indic ٠-٩ → 0-9. Also turns the Arabic decimal/thousands separators into '.' and ','. */
export function toLatinDigits(text: string | number): string {
  return String(text)
    .replace(/[۰-۹]/g, (d) => String(d.charCodeAt(0) - 0x06f0))
    .replace(/[٠-٩]/g, (d) => String(d.charCodeAt(0) - 0x0660))
    .replace(/٫/g, '.')
    .replace(/٬/g, ',');
}

/**
 * Normalise Persian text typed on different keyboards: Arabic ي/ى/ك → Persian ی/ک,
 * removes tatweel, directional marks and diacritics, collapses spaces, converts digits to Latin.
 * ZWNJ is turned into nothing so "سه‌شنبه" and "سهشنبه" compare equal.
 */
export function normalizePersian(text: string): string {
  return toLatinDigits(text)
    .replace(/[يى]/g, 'ی')
    .replace(/ك/g, 'ک')
    .replace(/ة/g, 'ه')
    .replace(/[أإٱ]/g, 'ا')
    .replace(/ـ/g, '')
    .replace(/[ً-ٰٟ]/g, '')
    .replace(/[​-‏‪-‮⁦-⁩؜﻿]/g, '')
    .replace(/\s+/g, ' ')
    .trim();
}
