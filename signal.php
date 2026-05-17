<?php
declare(strict_types=1);

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/src/Logger.php';
require_once __DIR__ . '/src/Telegram.php';
require_once __DIR__ . '/src/OpenRouter.php';
require_once __DIR__ . '/src/MarketData.php';
require_once __DIR__ . '/src/Prompts.php';
require_once __DIR__ . '/src/SignalPrompts.php';
require_once __DIR__ . '/src/SignalEngine.php';

header('Content-Type: application/json');

// احراز هویت
$key = $_SERVER['HTTP_X_PANEL_KEY'] ?? $_GET['key'] ?? '';
if ($key !== PANEL_KEY) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$logger   = new Logger();
$telegram = new Telegram(TELEGRAM_TOKEN, $logger);
$ai       = new OpenRouter(OPENROUTER_KEY, $logger);
$market   = new MarketData(GOLDAPI_KEY, $logger);
$engine   = new SignalEngine($ai, $market, $telegram, $logger);

$action = $_GET['action'] ?? 'run';

$result = match($action) {
    'run'  => $engine->run(),
    'last' => ['status' => 'success', 'data' => $engine->getLastSignal()],
    default => ['error' => 'Unknown action']
};

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
