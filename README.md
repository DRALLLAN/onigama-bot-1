# Onigama AI Brain — راهنمای استقرار

## ساختار پروژه

```
onigama-bot/
├── index.php              ← نقطه ورود وب‌هوک
├── setup-webhook.php      ← ثبت وب‌هوک (یک بار اجرا)
├── nixpacks.toml          ← تنظیمات Railway
├── .gitignore
├── config/
│   └── env.php            ← بارگذاری متغیرهای محیطی
└── src/
    ├── Logger.php          ← ثبت رویدادها
    ├── Telegram.php        ← ارتباط با تلگرام
    ├── OpenRouter.php      ← ارتباط با هوش مصنوعی
    ├── TwelveData.php      ← داده‌های بازار
    ├── Prompts.php         ← پرامپت‌های سیستم
    └── Router.php          ← مسیریابی دستورها
```

---

## مرحله اول — گیت‌هاب

```bash
# ساخت مخزن جدید خصوصی در github.com
# سپس در پوشه پروژه:

git init
git add .
git commit -m "feat: Onigama AI Brain v1.0"
git remote add origin https://github.com/USERNAME/onigama-bot.git
git push -u origin main
```

---

## مرحله دوم — Railway

۱. به `railway.app` برو و با گیت‌هاب وارد شو
۲. روی **New Project** کلیک کن
۳. گزینه **Deploy from GitHub repo** را انتخاب کن
۴. مخزن `onigama-bot` را انتخاب کن
۵. Railway به‌صورت خودکار پروژه را می‌شناسد

---

## مرحله سوم — متغیرهای محیطی در Railway

در داشبورد Railway → Variables این موارد را اضافه کن:

| نام متغیر | مقدار |
|---|---|
| `TELEGRAM_TOKEN` | توکن ربات تلگرام |
| `OPENROUTER_KEY` | کلید OpenRouter |
| `TWELVEDATA_KEY` | کلید TwelveData |

---

## مرحله چهارم — دامنه

۱. در Railway → Settings → Domains
۲. یا دامنه اختصاصی خودت را وارد کن
۳. آدرس نهایی مثلاً: `https://bot.onigamafx.com`

---

## مرحله پنجم — ثبت وب‌هوک تلگرام

بعد از استقرار، این آدرس را در مرورگر باز کن:

```
https://bot.onigamafx.com/setup-webhook.php
```

باید پیام موفقیت ببینی. بعد این فایل را حذف کن.

---

## دستورهای ربات

| دستور | عملکرد |
|---|---|
| `/start` یا `/help` | راهنما |
| `/gold` | تحلیل لایو XAUUSD |
| `/reel [موضوع]` | ایده ریل اینستاگرام |
| `/psych [موضوع]` | روانشناسی معاملاتی |
| هر متن دیگری | دستیار هوشمند عمومی |

---

## آپدیت در آینده

هر بار که کد را تغییر دهی و به گیت‌هاب پوش کنی، Railway به‌صورت خودکار دیپلوی می‌کند.

```bash
git add .
git commit -m "feat: توضیح تغییر"
git push
```
