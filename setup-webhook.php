<?php

declare(strict_types=1);

/**
 * این فایل را یک بار اجرا کن تا وب‌هوک تلگرام ثبت شود.
 * آدرس: https://yourdomain.com/setup-webhook.php
 * بعد از اجرا این فایل را حذف کن.
 */

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/src/Logger.php';
require_once __DIR__ . '/src/Telegram.php';

$webhookUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/index.php';

$logger   = new Logger();
$telegram = new Telegram(TELEGRAM_TOKEN, $logger);
$result   = $telegram->setWebhook($webhookUrl);

header('Content-Type: application/json');
echo json_encode([
    'webhook_url' => $webhookUrl,
    'result'      => $result,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
