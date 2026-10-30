<div dir="rtl">

# تقویم (taqvim): کتابخانهٔ دقیق تاریخ شمسی برای جاوااسکریپت، تایپ‌اسکریپت، پایتون و PHP

تبدیل تاریخ شمسی و میلادی، خواندن تاریخ‌هایی که کاربر فارسی‌زبان تایپ می‌کند، نمایش فارسی، دری و انگلیسی، حساب درست ماه و سال، و ساعت درست تهران؛ با یک رفتار یکسان در سه زبان.

[English](README.md#english) · [گزارش دقت و مقایسه](docs/ACCURACY.md) · [جدول نوروز سال ۱ تا ۲۹۹۹](reference/nowruz.csv)

## فهرست

* [چرا یک کتابخانهٔ جدید؟](#چرا-یک-کتابخانهٔ-جدید)
* [نصب](#نصب)
* [جاوااسکریپت و تایپ‌اسکریپت](#جاوااسکریپت-و-تایپاسکریپت)
* [پایتون](#پایتون)
* [PHP و لاراول](#php-و-لاراول)
* [قالب‌های نمایش تاریخ](#قالبهای-نمایش-تاریخ)
* [متن‌هایی که خوانده می‌شوند](#متنهایی-که-خوانده-میشوند)
* [تعطیلات و روز کاری](#تعطیلات-و-روز-کاری)
* [محدودیت‌ها](#محدودیتها)

## چرا یک کتابخانهٔ جدید؟

کتابخانه‌های موجود هر کدام بخشی از کار را حل می‌کنند و چند تا از آن‌ها باگ‌های جدی دارند ([گزارش کامل با روش آزمون](docs/ACCURACY.md)):

* **سال کبیسهٔ اشتباه:** کتابخانه‌هایی که با فرمول ریاضی کار می‌کنند، از تقویم واقعی فاصله می‌گیرند. مثلاً کتابخانهٔ پایتونی `khayyam` نوروز ۱۴۰۴ را ۲۰ مارس ۲۰۲۵ می‌داند، در حالی که ۲۱ مارس بود. یعنی همهٔ روزهای سال ۱۴۰۴ در آن یک روز جابه‌جاست. `persian-date` هم برای سال ۱۳۴۲ اشتباه می‌کند، و `verta` در PHP با قاعدهٔ ثابت ۳۳ ساله، سال‌های ۱۶۳۴ و ۱۶۶۷ را اشتباه حساب می‌کند. تقویم از **جدول نجومی اعتدال بهاری** برای سال‌های ۱ تا ۲۹۹۹ استفاده می‌کند که با داده‌های JPL ناسا بررسی شده است.
* **تاریخ ناموجود:** ۳۰ اسفند ۱۴۰۴ وجود ندارد. چند کتابخانه آن را بی‌صدا به ۱ فروردین ۱۴۰۵ تبدیل می‌کنند یا معتبر می‌دانند. تقویم آن را **رد می‌کند** و دلیلش را می‌گوید.
* **ارقام و صفحه‌کلید فارسی:** `۱۴۰۴/۰۱/۱۵` در بعضی کتابخانه‌ها خطا می‌دهد یا سال ۶۲۱ برمی‌گرداند. تقویم ارقام فارسی و عربی، «ي» و «ك» عربی، نیم‌فاصله، نام ماه‌ها، روز هفته و «ه.ش» را می‌فهمد.
* **ساعت تهران:** ساعت تابستانی ایران از ۱۴۰۱ لغو شد، ولی دستگاه‌ها و نسخه‌های PHP که اطلاعات منطقهٔ زمانی‌شان قدیمی است، هنوز یک ساعت جلو می‌روند. تقویم تاریخچهٔ منطقهٔ زمانی تهران را درون خودش دارد.
* **یک قاعده برای سه زبان:** معمولاً فرانت‌اند و بک‌اند کتابخانه‌های متفاوت با قواعد متفاوت دارند. تقویم یک مشخصه و یک مجموعه آزمون مشترک دارد (`reference/test-cases.json`) و هر سه نسخه باید آن را رد کنند.

## نصب

</div>

```bash
npm install taqvim                  # JavaScript / TypeScript
pip install taqvim                  # Python
composer require rahimixyz/taqvim   # PHP
```

<div dir="rtl">

## جاوااسکریپت و تایپ‌اسکریپت

</div>

```ts
import { JalaliDate, toJalali, toGregorian, formatDateTime } from 'taqvim';

toJalali(2025, 3, 21);            // { year: 1404, month: 1, day: 1 }
toGregorian(1403, 12, 30);        // { year: 2025, month: 3, day: 20 }

const d = new JalaliDate(1404, 1, 15);
d.format('dddd D MMMM YYYY');                  // 'جمعه ۱۵ فروردین ۱۴۰۴'
d.format('D MMMM YYYY', { locale: 'fa-AF' });  // '۱۵ حمل ۱۴۰۴'  (نام ماه‌های افغانستان)
d.addMonths(1).toString();                     // '1404/02/15'
d.toISODate();                                 // '2025-04-04'

JalaliDate.parse('شنبه ۱۶ فروردین ۱۴۰۴ ه.ش');  // 1404/01/16
JalaliDate.today();                            // امروز به وقت تهران
formatDateTime(new Date(), 'YYYY/MM/DD HH:mm');

new JalaliDate(1404, 12, 30);  // خطا: ۱۴۰۴ کبیسه نیست و ۳۰ اسفند ندارد
```

<div dir="rtl">

## پایتون

</div>

```python
from datetime import date, timedelta
from taqvim import JalaliDate, to_jalali, to_gregorian

to_jalali(2025, 3, 21)          # YMD(year=1404, month=1, day=1)
to_gregorian(1403, 12, 30)      # YMD(year=2025, month=3, day=20)

d = JalaliDate(1404, 1, 15)
d.format('dddd D MMMM YYYY')    # 'جمعه ۱۵ فروردین ۱۴۰۴'
d.add_months(1)                 # JalaliDate(1404, 2, 15)
d.to_date()                     # datetime.date(2025, 4, 4)
d + timedelta(days=1)           # JalaliDate(1404, 1, 16)

JalaliDate.parse('۱۵ فروردین ۱۴۰۴')
JalaliDate.today()              # امروز به وقت تهران
```

<div dir="rtl">

در جنگو: تاریخ را در دیتابیس به‌صورت معمولی (`DateField`) ذخیره کنید و فقط در نمایش و ورودی فرم تبدیلش کنید: `JalaliDate.from_date(obj.created).format('D MMMM YYYY')`.

## PHP و لاراول

**نیاز:** PHP 8.1 یا بالاتر. هیچ افزونه یا کتابخانهٔ دیگری لازم نیست.

### نصب با Composer

</div>

```bash
composer require rahimixyz/taqvim
```

<div dir="rtl">

اگر Composer بسته را پیدا نکرد، مخزن گیت‌هاب را به `composer.json` پروژه اضافه کنید و دوباره `composer require` بزنید:

</div>

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/rahimixyz/persian-calendar-jalali-shamsi" }
    ],
    "require": { "rahimixyz/taqvim": "^1.0" }
}
```

<div dir="rtl">

### بدون Composer

پوشهٔ `php/` همین مخزن را در پروژه کپی کنید و این خط را بالای فایل بگذارید. این روش برای هاست‌های اشتراکی و وردپرس مناسب است:

</div>

```php
require __DIR__ . '/php/autoload.php';
```

<div dir="rtl">

### استفاده

</div>

```php
use Taqvim\JalaliDate;
use Taqvim\Taqvim;

// تبدیل ساده
Taqvim::toJalali(2025, 3, 21);       // ['year' => 1404, 'month' => 1, 'day' => 1]
Taqvim::toGregorian(1403, 12, 30);   // ['year' => 2025, 'month' => 3, 'day' => 20]

// کار با تاریخ
$d = new JalaliDate(1404, 1, 15);
$d->format('dddd D MMMM YYYY');           // 'جمعه ۱۵ فروردین ۱۴۰۴'
$d->format('D MMMM YYYY', 'fa-AF');       // '۱۵ حمل ۱۴۰۴'
$d->addMonths(1)->toString();             // '1404/02/15'
$d->toISODate();                          // '2025-04-04'
$d->toDateTime()->format('Y-m-d H:i P');  // '2025-04-04 00:00 +03:30'

// امروز و تاریخ از DateTime
JalaliDate::today();                       // امروز به وقت تهران
JalaliDate::fromDateTime(new DateTime());  // تاریخ شمسی یک DateTime
Taqvim::formatDateTime(time(), 'dddd D MMMM YYYY، HH:mm');

// خواندن تاریخ از ورودی کاربر
$x = JalaliDate::tryParse('۱۵ فروردین ۱۴۰۴');  // null اگر نامعتبر باشد
```

<div dir="rtl">

### نمونه در لاراول

بهترین روش این است که تاریخ را در دیتابیس **میلادی** ذخیره کنید، چون ستون‌های `DATE` و `TIMESTAMP` و همهٔ ابزارها با آن کار می‌کنند، و فقط در **نمایش** و **ورودی فرم** به شمسی تبدیلش کنید:

</div>

```php
use Taqvim\JalaliDate;
use Taqvim\InvalidDateException;

// نمایش تاریخ در Blade
{{ JalaliDate::fromDateTime($order->created_at)->format('D MMMM YYYY') }}

// گرفتن تاریخ شمسی از فرم و ذخیرهٔ میلادی
try {
    $order->delivery_date = JalaliDate::parse($request->input('delivery_date'))->toISODate();
} catch (InvalidDateException $e) {
    return back()->withErrors(['delivery_date' => 'تاریخ واردشده معتبر نیست.']);
}

// یک accessor در مدل
public function getCreatedAtJalaliAttribute(): string
{
    return JalaliDate::fromDateTime($this->created_at)->format('D MMMM YYYY');
}
```

<div dir="rtl">

### نکته دربارهٔ ساعت تهران در PHP

`DateTimeZone('Asia/Tehran')` در خود PHP به اطلاعات منطقهٔ زمانی‌ای وابسته است که با PHP کامپایل شده؛ در نسخه‌ها یا هاست‌های قدیمی هنوز ساعت تابستانی دارد. توابع تقویم (`fromDateTime`، `toDateTime`، `formatDateTime`) برای تهران از جدول داخلی خودشان استفاده می‌کنند و همیشه درست‌اند. `toDateTime()` هم یک `DateTimeImmutable` با اختلاف ثابت `+03:30` برمی‌گرداند تا PHP دوباره حسابش را خراب نکند.

## قالب‌های نمایش تاریخ

| نشانه | نتیجه | نشانه | نتیجه |
|---|---|---|---|
| `YYYY` | ۱۴۰۴ | `dddd` | شنبه |
| `YY` | ۰۴ | `ddd` | ش |
| `MMMM` | فروردین | `d` | شمارهٔ روز هفته (شنبه = ۱) |
| `MM` | ۰۱ | `QQQQ` | بهار |
| `M` | ۱ | `HH:mm:ss` | ساعت (با `formatDateTime`) |
| `DD` | ۰۵ | `A` | ق.ظ / ب.ظ |
| `D` | ۵ | `Z` | +۰۳:۳۰ |
| `DDD` | روز چندم سال | `[متن]` | متن ثابت |

زبان‌ها: `fa` (پیش‌فرض)، `fa-AF` (نام ماه‌های دری: حمل، ثور…) و `en`. ارقام: `persian` (پیش‌فرض فارسی)، `latin` و `arabic`.

## متن‌هایی که خوانده می‌شوند

`1404/01/15`، `1404-1-15`، `15/01/1404`، `۱۴۰۴/۰۱/۱۵`، `١٤٠٤/٠١/١٥`، `۱۵ فروردین ۱۴۰۴`، `شنبه ۱۶ فروردین ۱۴۰۴ ه.ش`، `۲۰ امرداد ۱۴۰۳`، `۱۵ حمل ۱۴۰۴`، `15 Farvardin 1404`.

سال دورقمی، مثل `04/01/15`، حدس زده نمی‌شود و خطا می‌دهد. پیام خطا دقیقاً می‌گوید چه چیزی اشتباه است.

## تعطیلات و روز کاری

`solarHolidays(سال)`، `isHoliday(تاریخ)`، `addBusinessDays(تاریخ، تعداد)`. در PHP این توابع در کلاس `Holidays` هستند.

تعطیلات **ثابت شمسی** ایران پوشش داده می‌شوند: ۱ تا ۴ فروردین، ۱۲ و ۱۳ فروردین، ۱۴ و ۱۵ خرداد، ۲۲ بهمن و ۲۹ اسفند. تعطیلات **قمری** هر سال با رؤیت ماه اعلام می‌شوند و از پیش قابل محاسبه نیستند؛ آن‌ها را با پارامتر `extra` بدهید. جمعه به‌طور پیش‌فرض تعطیل است.

## محدودیت‌ها

* بازهٔ پشتیبانی: سال ۱ تا ۲۹۹۹ شمسی.
* چند سال از نظر نجومی مرزی هستند، یعنی تحویل سال نزدیک ظهر است. نزدیک‌ترینشان سال ۱۴۷۰ است. این سال‌ها با `isUncertainYear` مشخص می‌شوند و پنهان نمی‌مانند.

## مجوز

MIT، © ۲۰۲۶ علیرضا رحیمی

</div>
