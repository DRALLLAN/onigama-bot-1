<?php
declare(strict_types=1);

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/src/Logger.php';
require_once __DIR__ . '/src/Telegram.php';
require_once __DIR__ . '/src/OpenRouter.php';
require_once __DIR__ . '/src/MarketData.php';
require_once __DIR__ . '/src/Memory.php';
require_once __DIR__ . '/src/Prompts.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Panel-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ─── احراز هویت ──────────────────────────────────────────────────────────────
$panelKey = $_SERVER['HTTP_X_PANEL_KEY'] ?? $_GET['key'] ?? '';
if ($panelKey !== PANEL_KEY) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$logger     = new Logger();
$telegram   = new Telegram(TELEGRAM_TOKEN, $logger);
$openRouter = new OpenRouter(OPENROUTER_KEY, $logger);
$market     = new MarketData(GOLDAPI_KEY, $logger);
$memory     = new Memory();

$action = $_GET['action'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

// ─── روتر API ────────────────────────────────────────────────────────────────
$response = match($action) {
    'gold'       => actionGold($market, $openRouter),
    'session'    => actionSession($market, $openRouter),
    'stats'      => actionStats($memory),
    'users'      => actionUsers($memory),
    'send'       => actionSend($telegram, $body),
    'generate'   => actionGenerate($openRouter, $body),
    default      => ['error' => 'Unknown action']
};

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// ─── توابع ────────────────────────────────────────────────────────────────────

function actionGold(MarketData $market, OpenRouter $ai): array
{
    $data = $market->getMarketData('XAUUSD');
    if (!$data) return ['error' => 'Failed to fetch gold data'];

    $analysis  = $ai->chat(Prompts::gold(), $data, 400);
    $priceData = $market->getPriceData('XAUUSD');

    return [
        'price'      => $priceData['price']      ?? 'N/A',
        'change'     => $priceData['change']      ?? 'N/A',
        'change_pct' => $priceData['change_pct']  ?? 'N/A',
        'high'       => $priceData['high']        ?? 'N/A',
        'low'        => $priceData['low']         ?? 'N/A',
        'analysis'   => $analysis,
        'time'       => gmdate('H:i') . ' UTC',
    ];
}

function actionSession(MarketData $market, OpenRouter $ai): array
{
    $data     = $market->getSessionInfo();
    $analysis = $ai->chat(Prompts::session(), $data, 300);
    return ['analysis' => $analysis, 'raw' => $data];
}

function actionStats(Memory $memory): array
{
    $storageDir = __DIR__ . '/storage/';
    $files      = glob($storageDir . 'chat_*.json') ?: [];
    $totalUsers = count($files);
    $activeToday = 0;
    $totalMessages = 0;
    $commandCount = [];

    foreach ($files as $file) {
        $data = json_decode(file_get_contents($file), true);
        $msgs = $data['messages'] ?? [];
        $totalMessages += count($msgs);

        $updated = $data['updated_at'] ?? '';
        if ($updated && strtotime($updated) > strtotime('-24 hours')) {
            $activeToday++;
        }
    }

    return [
        'total_users'    => $totalUsers,
        'active_today'   => $activeToday,
        'total_messages' => $totalMessages,
        'daily_average'  => $totalUsers > 0 ? round($totalMessages / max($totalUsers, 1)) : 0,
    ];
}

function actionUsers(Memory $memory): array
{
    $storageDir = __DIR__ . '/storage/';
    $files      = glob($storageDir . 'chat_*.json') ?: [];
    $users      = [];

    foreach ($files as $file) {
        $data     = json_decode(file_get_contents($file), true);
        $msgs     = $data['messages'] ?? [];
        $chatId   = str_replace(['chat_', '.json'], '', basename($file));
        $updated  = $data['updated_at'] ?? 'Unknown';
        $lastCmd  = 'general';

        foreach (array_reverse($msgs) as $msg) {
            if ($msg['role'] === 'user') {
                $t = trim($msg['content'] ?? '');
                if (str_starts_with($t, '/')) {
                    $lastCmd = explode(' ', $t)[0];
                }
                break;
            }
        }

        $users[] = [
            'chat_id'  => $chatId,
            'updated'  => $updated,
            'last_cmd' => $lastCmd,
            'msg_count'=> count($msgs),
        ];
    }

    usort($users, fn($a, $b) => strtotime($b['updated']) - strtotime($a['updated']));
    return ['users' => array_slice($users, 0, 20)];
}

function actionSend(Telegram $telegram, array $body): array
{
    $text    = trim($body['text'] ?? '');
    $channel = trim($body['channel'] ?? TELEGRAM_CHANNEL);

    if (!$text) return ['error' => 'Message text is required'];
    if (!$channel) return ['error' => 'Channel not configured'];

    $sent = $telegram->sendMessage($channel, $text);
    return ['success' => $sent, 'channel' => $channel];
}

function actionGenerate(OpenRouter $ai, array $body): array
{
    $type  = $body['type']  ?? 'general';
    $topic = $body['topic'] ?? '';

    $prompt = match($type) {
        'reel'   => Prompts::reel(),
        'psych'  => Prompts::psychology(),
        'gold'   => Prompts::gold(),
        default  => Prompts::general(),
    };

    $result = $ai->chat($prompt, $topic ?: 'Generate a powerful message.', 400);
    return ['content' => $result];
}
