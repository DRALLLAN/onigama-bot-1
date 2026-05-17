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

    // ─── ارسال تصویر یا متن در صورت شکست ────────────────────
    private function sendImageOrText(int|string $chatId, ?string $imgPath, string $fallbackText): void
    {
        if ($imgPath && file_exists($imgPath)) {
            $sent = $this->telegram->sendPhoto($chatId, $imgPath);
            if (!$sent) {
                $this->telegram->sendMessage($chatId, $fallbackText);
            }
        } else {
            $this->telegram->sendMessage($chatId, $fallbackText);
        }
    }

    private function handleHelp(int|string $chatId): void
    {
        $this->memory->clearHistory($chatId);
        $this->telegram->sendMessage($chatId, Prompts::help());
    }

    private function handleClear(int|string $chatId): void
    {
        $this->memory->clearHistory($chatId);
        $this->telegram->sendMessage($chatId,
            "🧹 *حافظه مکالمه پاک شد.*\n\nمکالمه جدید را شروع کن."
        );
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

        $marketData = $this->market->getMarketData($symbol);
        if (!$marketData) {
            $this->telegram->sendMessage($chatId, '⚠️ دریافت داده‌های بازار ناموفق بود.');
            return;
        }

        $analysis  = $this->openRouter->chat($prompts[$symbol], $marketData, 400);
        $rows      = $this->imgGen->parseAnalysis($analysis);
        $priceData = $this->market->getPriceData($symbol);

        $imgPath   = $this->imgGen->market($symbol, $rows, $priceData);
        $fallback  = MessageFormatter::market($symbol, $analysis);

        $this->sendImageOrText($chatId, $imgPath, $fallback);
    }

    private function handleMTF(int|string $chatId, string $symbol): void
    {
        $symbol   = strtoupper(trim($symbol)) ?: 'XAUUSD';
        $data     = $this->market->getMarketData($symbol);
        if (!$data) { $this->telegram->sendMessage($chatId, '⚠️ داده دریافت نشد.'); return; }

        $analysis = $this->openRouter->chat(Prompts::mtf(), "Symbol: {$symbol}\n{$data}", 450);
        $rows     = $this->imgGen->parseAnalysis($analysis);
        $imgPath  = $this->imgGen->text('MULTI-TIMEFRAME', "{$symbol} — Top-Down Analysis", 'MTF', $rows);
        $fallback = MessageFormatter::mtf($symbol, $analysis);

        $this->sendImageOrText($chatId, $imgPath, $fallback);
    }

    private function handleSession(int|string $chatId): void
    {
        $data     = $this->market->getSessionInfo();
        $analysis = $this->openRouter->chat(Prompts::session(), $data, 350);
        $rows     = $this->imgGen->parseAnalysis($analysis);
        $imgPath  = $this->imgGen->text('SESSION', 'Market Session Intelligence', 'SESSION', $rows);
        $fallback = MessageFormatter::session($analysis);

        $this->sendImageOrText($chatId, $imgPath, $fallback);
    }

    private function handleNews(int|string $chatId): void
    {
        $data     = $this->market->getEconomicEvents();
        $analysis = $this->openRouter->chat(Prompts::news(), $data, 350);
        $rows     = $this->imgGen->parseAnalysis($analysis);
        $imgPath  = $this->imgGen->text('ECONOMIC', 'High-Impact Events Today', 'NEWS', $rows);
        $fallback = MessageFormatter::news($analysis);

        $this->sendImageOrText($chatId, $imgPath, $fallback);
    }

    private function handleSetup(int|string $chatId, string $param): void
    {
        if (!$param) {
            $this->telegram->sendMessage($chatId, "📝 ستاپ را توضیح بده:\n`/setup XAUUSD sell 15m, OB at 4650, target 4511`");
            return;
        }
        $analysis = $this->openRouter->chat(Prompts::setup(), $param, 450);
        $rows     = $this->imgGen->parseAnalysis($analysis);
        $imgPath  = $this->imgGen->text('SETUP REVIEW', 'Trade Setup Analysis', 'SETUP', $rows);
        $fallback = MessageFormatter::setup($analysis);

        $this->sendImageOrText($chatId, $imgPath, $fallback);
    }

    private function handleJournal(int|string $chatId, string $param): void
    {
        if (!$param) {
            $this->telegram->sendMessage($chatId, "📝 معامله‌ات را ثبت کن:\n`/journal buy XAUUSD 4511 tp 4580 sl 4490`");
            return;
        }
        $analysis = $this->openRouter->chat(Prompts::journal(), $param, 450);
        $rows     = $this->imgGen->parseAnalysis($analysis);
        $imgPath  = $this->imgGen->text('JOURNAL', 'Trade Review', 'JOURNAL', $rows);
        $fallback = MessageFormatter::journal($analysis);

        $this->sendImageOrText($chatId, $imgPath, $fallback);
    }

    private function handleChecklist(int|string $chatId): void
    {
        $session  = $this->market->getSessionInfo();
        $analysis = $this->openRouter->chat(Prompts::checklist(), $session, 500);
        $rows     = $this->imgGen->parseAnalysis($analysis);
        $imgPath  = $this->imgGen->text('PROTOCOL', 'Daily Trading Checklist', 'CHECKLIST', $rows);
        $fallback = MessageFormatter::checklist($analysis);

        $this->sendImageOrText($chatId, $imgPath, $fallback);
    }

    private function handleReel(int|string $chatId, string $param): void
    {
        if (!$param) {
            $this->telegram->sendMessage($chatId, "📝 موضوع ریل:\n`/reel trading psychology`");
            return;
        }
        $analysis = $this->openRouter->chat(Prompts::reel(), $param, 400);
        $rows     = $this->imgGen->parseAnalysis($analysis);
        $imgPath  = $this->imgGen->text('CONTENT', 'Instagram Reel Strategy', 'REEL', $rows);
        $fallback = MessageFormatter::reel($analysis);

        $this->sendImageOrText($chatId, $imgPath, $fallback);
    }

    private function handlePsych(int|string $chatId, string $param): void
    {
        $msg      = $param ?: 'Help me build mental discipline for trading.';
        $analysis = $this->openRouter->chat(Prompts::psychology(), $msg, 450);
        $rows     = $this->imgGen->parseAnalysis($analysis);
        $imgPath  = $this->imgGen->text('NEUROTRADER', 'Trading Psychology', 'PSYCH', $rows);
        $fallback = MessageFormatter::psychology($analysis);

        $this->sendImageOrText($chatId, $imgPath, $fallback);
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
