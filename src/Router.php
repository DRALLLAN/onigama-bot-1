<?php
declare(strict_types=1);
require_once __DIR__ . '/Prompts.php';
require_once __DIR__ . '/Memory.php';
require_once __DIR__ . '/MessageFormatter.php';
require_once __DIR__ . '/ImageGenerator.php';

class Router
{
    private Telegram       $telegram;
    private OpenRouter     $openRouter;
    private MarketData     $market;
    private Memory         $memory;
    private ImageGenerator $imgGen;
    private Logger         $logger;

    public function __construct(Telegram $telegram, OpenRouter $openRouter, MarketData $market, Logger $logger)
    {
        $this->telegram   = $telegram;
        $this->openRouter = $openRouter;
        $this->market     = $market;
        $this->memory     = new Memory();
        $this->imgGen     = new ImageGenerator($logger);
        $this->logger     = $logger;
    }

    public function handle(array $update): void
    {
        $message = $update['message'] ?? null;
        if (!$message) return;

        $chatId = $message['chat']['id'];
        $text   = trim($message['text'] ?? '');
        if (!$text) return;

        $this->logger->info("پیام از $chatId: " . mb_substr($text, 0, 100));
        $this->telegram->sendTyping($chatId);

        $cmd   = strtolower(explode(' ', $text)[0]);
        $param = trim(substr($text, strlen($cmd)));

        match(true) {
            in_array($cmd, ['/start', '/help']) => $this->handleHelp($chatId),
            $cmd === '/clear'                   => $this->handleClear($chatId),
            $cmd === '/gold'                    => $this->handleMarket($chatId, 'XAUUSD'),
            $cmd === '/eurusd'                  => $this->handleMarket($chatId, 'EURUSD'),
            $cmd === '/gbpusd'                  => $this->handleMarket($chatId, 'GBPUSD'),
            $cmd === '/usdjpy'                  => $this->handleMarket($chatId, 'USDJPY'),
            $cmd === '/usdchf'                  => $this->handleMarket($chatId, 'USDCHF'),
            $cmd === '/mtf'                     => $this->handleMTF($chatId, $param),
            $cmd === '/session'                 => $this->handleSession($chatId),
            $cmd === '/news'                    => $this->handleNews($chatId),
            $cmd === '/setup'                   => $this->handleSetup($chatId, $param),
            $cmd === '/journal'                 => $this->handleJournal($chatId, $param),
            $cmd === '/checklist'               => $this->handleChecklist($chatId),
            $cmd === '/reel'                    => $this->handleReel($chatId, $param),
            $cmd === '/psych'                   => $this->handlePsych($chatId, $param),
            default                             => $this->handleGeneral($chatId, $text),
        };
    }

    private function handleHelp(int|string $chatId): void
    {
        $this->memory->clearHistory($chatId);
        $this->telegram->sendMessage($chatId, Prompts::help());
    }

    private function handleClear(int|string $chatId): void
    {
        $this->memory->clearHistory($chatId);
        $this->telegram->sendMessage($chatId, "🧹 *Memory cleared.*\n\nStart a new conversation.");
    }

    private function handleMarket(int|string $chatId, string $symbol): void
    {
        $prompts = [
            'XAUUSD' => Prompts::gold(),
            'EURUSD' => Prompts::eurusd(),
            'GBPUSD' => Prompts::gbpusd(),
            'USDJPY' => Prompts::usdjpy(),
            'USDCHF' => Prompts::usdchf(),
        ];

        $data = $this->market->getMarketData($symbol);
        if (!$data) {
            $this->telegram->sendMessage($chatId, '⚠️ Failed to fetch market data. Please try again.');
            return;
        }

        $analysis = $this->openRouter->chat($prompts[$symbol], $data, 400);
        $this->telegram->sendMessage($chatId, MessageFormatter::market($symbol, $analysis));
    }

    private function handleMTF(int|string $chatId, string $symbol): void
    {
        $symbol = strtoupper(trim($symbol)) ?: 'XAUUSD';
        $data   = $this->market->getMarketData($symbol);
        if (!$data) { $this->telegram->sendMessage($chatId, '⚠️ Failed to fetch data.'); return; }
        $analysis = $this->openRouter->chat(Prompts::mtf(), "Symbol: {$symbol}\n{$data}", 450);
        $this->telegram->sendMessage($chatId, MessageFormatter::mtf($symbol, $analysis));
    }

    private function handleSession(int|string $chatId): void
    {
        $data     = $this->market->getSessionInfo();
        $analysis = $this->openRouter->chat(Prompts::session(), $data, 350);
        $this->telegram->sendMessage($chatId, MessageFormatter::session($analysis));
    }

    private function handleNews(int|string $chatId): void
    {
        $data     = $this->market->getEconomicEvents();
        $analysis = $this->openRouter->chat(Prompts::news(), $data, 350);
        $this->telegram->sendMessage($chatId, MessageFormatter::news($analysis));
    }

    private function handleSetup(int|string $chatId, string $param): void
    {
        if (!$param) {
            $this->telegram->sendMessage($chatId, "📝 Describe your setup:\n`/setup XAUUSD sell 15m, OB at 4650, target 4511`");
            return;
        }
        $analysis = $this->openRouter->chat(Prompts::setup(), $param, 450);
        $this->telegram->sendMessage($chatId, MessageFormatter::setup($analysis));
    }

    private function handleJournal(int|string $chatId, string $param): void
    {
        if (!$param) {
            $this->telegram->sendMessage($chatId, "📝 Log your trade:\n`/journal buy XAUUSD 4511 tp 4580 sl 4490`");
            return;
        }
        $analysis = $this->openRouter->chat(Prompts::journal(), $param, 450);
        $this->telegram->sendMessage($chatId, MessageFormatter::journal($analysis));
    }

    private function handleChecklist(int|string $chatId): void
    {
        $session  = $this->market->getSessionInfo();
        $analysis = $this->openRouter->chat(Prompts::checklist(), $session, 500);
        $this->telegram->sendMessage($chatId, MessageFormatter::checklist($analysis));
    }

    private function handleReel(int|string $chatId, string $param): void
    {
        if (!$param) {
            $this->telegram->sendMessage($chatId, "📝 Enter reel topic:\n`/reel trading psychology`");
            return;
        }
        $analysis = $this->openRouter->chat(Prompts::reel(), $param, 400);
        $this->telegram->sendMessage($chatId, MessageFormatter::reel($analysis));
    }

    private function handlePsych(int|string $chatId, string $param): void
    {
        $msg      = $param ?: 'Help me build mental discipline for trading.';
        $analysis = $this->openRouter->chat(Prompts::psychology(), $msg, 450);
        $this->telegram->sendMessage($chatId, MessageFormatter::psychology($analysis));
    }

    private function handleGeneral(int|string $chatId, string $text): void
    {
        $messages = $this->memory->buildMessages($chatId, Prompts::general(), $text);
        $reply    = $this->openRouter->chatWithHistory($messages, 450);
        $this->memory->addUserMessage($chatId, $text);
        $this->memory->addAssistantMessage($chatId, $reply);
        $this->telegram->sendMessage($chatId, $reply);
    }
}
