"""Month and weekday names, digit conversion and Persian text normalisation."""
from __future__ import annotations

import re

MONTHS = {
    'fa': ('فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'),
    'fa-AF': ('حمل', 'ثور', 'جوزا', 'سرطان', 'اسد', 'سنبله', 'میزان', 'عقرب', 'قوس', 'جدی', 'دلو', 'حوت'),
    'en': ('Farvardin', 'Ordibehesht', 'Khordad', 'Tir', 'Mordad', 'Shahrivar', 'Mehr', 'Aban', 'Azar', 'Dey',
           'Bahman', 'Esfand'),
}
# index 0 = Saturday
WEEKDAYS = {
    'fa': ('شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'),
    'fa-AF': ('شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'),
    'en': ('Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'),
}
SHORT_WEEKDAYS = {
    'fa': ('ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'),
    'fa-AF': ('ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'),
    'en': ('Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'),
}
SEASONS = {
    'fa': ('بهار', 'تابستان', 'پاییز', 'زمستان'),
    'fa-AF': ('بهار', 'تابستان', 'خزان', 'زمستان'),
    'en': ('Spring', 'Summer', 'Autumn', 'Winter'),
}
MERIDIEM = {'fa': ('ق.ظ', 'ب.ظ'), 'fa-AF': ('ق.ظ', 'ب.ظ'), 'en': ('AM', 'PM')}

_PERSIAN = '۰۱۲۳۴۵۶۷۸۹'
_ARABIC = '٠١٢٣٤٥٦٧٨٩'
_TO_LATIN = str.maketrans({**{c: str(i) for i, c in enumerate(_PERSIAN)}, **{c: str(i) for i, c in enumerate(_ARABIC)},
                           '٫': '.', '٬': ','})
_TO_PERSIAN = str.maketrans({str(i): c for i, c in enumerate(_PERSIAN)})
_TO_ARABIC = str.maketrans({str(i): c for i, c in enumerate(_ARABIC)})


def default_digits(locale: str) -> str:
    return 'latin' if locale == 'en' else 'persian'


def to_latin_digits(text) -> str:
    """Persian and Arabic-Indic digits -> 0-9."""
    return str(text).translate(_TO_LATIN)


def to_persian_digits(text) -> str:
    return to_latin_digits(text).translate(_TO_PERSIAN)


def convert_digits(text, to: str) -> str:
    s = to_latin_digits(text)
    if to == 'latin':
        return s
    if to == 'persian':
        return s.translate(_TO_PERSIAN)
    if to == 'arabic':
        return s.translate(_TO_ARABIC)
    raise ValueError("digits must be 'persian', 'arabic' or 'latin'")


_MARKS = re.compile('[​-‏‪-‮⁦-⁩؜﻿]')
_HARAKAT = re.compile('[ً-ٰٟ]')


def normalize_persian(text: str) -> str:
    """Arabic ي/ى/ك -> Persian ی/ک, strip tatweel, diacritics, ZWNJ and RTL marks,
    collapse whitespace, convert digits to Latin."""
    s = to_latin_digits(text)
    s = s.translate(str.maketrans({'ي': 'ی', 'ى': 'ی', 'ك': 'ک', 'ة': 'ه', 'أ': 'ا', 'إ': 'ا', 'ٱ': 'ا', 'ـ': None}))
    s = _HARAKAT.sub('', s)
    s = _MARKS.sub('', s)
    return re.sub(r'\s+', ' ', s).strip()
