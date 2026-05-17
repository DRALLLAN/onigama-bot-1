<?php

declare(strict_types=1);

// متغیرهای محیطی — در Railway از داشبورد تنظیم می‌شوند
define('TELEGRAM_TOKEN',  getenv('TELEGRAM_TOKEN')  ?: '');
define('OPENROUTER_KEY',  getenv('OPENROUTER_KEY')  ?: '');
define('TWELVEDATA_KEY',  getenv('TWELVEDATA_KEY')  ?: '');
define('WEBHOOK_SECRET',  getenv('WEBHOOK_SECRET')  ?: '');

// تنظیمات مدل
define('AI_MODEL',      'mistralai/mistral-7b-instruct:free');
define('AI_MODEL_PAID', 'anthropic/claude-3-haiku');

// بررسی متغیرهای ضروری
foreach (['TELEGRAM_TOKEN', 'OPENROUTER_KEY', 'TWELVEDATA_KEY'] as $key) {
    if (!constant($key)) {
        http_response_code(500);
        error_log("متغیر محیطی $key تنظیم نشده است.");
        exit;
    }
}
