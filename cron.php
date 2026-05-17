<?php
/**
 * این فایل توسط Railway Cron هر ۴ ساعت اجرا می‌شود
 * تنظیم: در Railway → Settings → Cron Jobs
 * Schedule: 0 */4 * * *
 */
declare(strict_types=1);

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/src/Logger.php';
require_once __DIR__ . '/src/Telegram.php';
require_once __DIR__ . '/src/OpenRouter.php';
require_once __DIR__ . '/src/MarketData.php';
require_once __DIR__ . '/src/Prompts.php';
require_once __DIR__ . '/src/SignalPrompts.php';
require_once __DIR__ . '/src/SignalEngine.php';

$logger   = new Logger();
$telegram = new Telegram(TELEGRAM_TOKEN, $logger);
$ai       = new OpenRouter(OPENROUTER_KEY, $logger);
$market   = new MarketData(GOLDAPI_KEY, $logger);
$engine   = new SignalEngine($ai, $market, $telegram, $logger);

$result = $engine->run();
$logger->info("Cron signal result: " . json_encode($result));

echo json_encode($result);
