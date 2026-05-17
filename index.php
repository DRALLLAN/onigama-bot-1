<?php
declare(strict_types=1);

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/src/Logger.php';
require_once __DIR__ . '/src/Memory.php';
require_once __DIR__ . '/src/Telegram.php';
require_once __DIR__ . '/src/OpenRouter.php';
require_once __DIR__ . '/src/TwelveData.php';
require_once __DIR__ . '/src/Router.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(200);
    echo json_encode(['status' => 'Onigama AI Brain is running.']);
    exit;
}

$input  = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update || !isset($update['message'])) {
    http_response_code(200);
    exit;
}

$logger     = new Logger();
$telegram   = new Telegram(TELEGRAM_TOKEN, $logger);
$openRouter = new OpenRouter(OPENROUTER_KEY, $logger);
$twelveData = new TwelveData(TWELVEDATA_KEY, $logger);

$router = new Router($telegram, $openRouter, $twelveData, $logger);
$router->handle($update);

http_response_code(200);
echo 'OK';
