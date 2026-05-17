<?php
declare(strict_types=1);

define('TELEGRAM_TOKEN', getenv('TELEGRAM_TOKEN') ?: '');
define('OPENROUTER_KEY', getenv('OPENROUTER_KEY') ?: '');
define('TWELVEDATA_KEY', getenv('TWELVEDATA_KEY') ?: '');
define('GOLDAPI_KEY',    getenv('GOLDAPI_KEY')    ?: '');
define('WEBHOOK_SECRET', getenv('WEBHOOK_SECRET') ?: '');
define('AI_MODEL',       'openrouter/auto');

foreach (['TELEGRAM_TOKEN', 'OPENROUTER_KEY', 'GOLDAPI_KEY'] as $key) {
    if (!constant($key)) {
        http_response_code(500);
        error_log("متغیر محیطی $key تنظیم نشده است.");
        exit;
    }
}
