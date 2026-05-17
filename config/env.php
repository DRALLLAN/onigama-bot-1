<?php
declare(strict_types=1);
define('TELEGRAM_TOKEN',  getenv('TELEGRAM_TOKEN')  ?: '');
define('OPENROUTER_KEY',  getenv('OPENROUTER_KEY')  ?: '');
define('TWELVEDATA_KEY',  getenv('TWELVEDATA_KEY')  ?: '');
define('WEBHOOK_SECRET',  getenv('WEBHOOK_SECRET')  ?: '');
define('AI_MODEL',        'mistralai/mistral-7b-instruct:free');
foreach (['TELEGRAM_TOKEN', 'OPENROUTER_KEY'] as $key) {
    if (!constant($key)) {
        http_response_code(500);
        error_log("متغیر محیطی $key تنظیم نشده است.");
        exit;
    }
}
