<?php
declare(strict_types=1);
require_once __DIR__ . '/Prompts.php';
require_once __DIR__ . '/Memory.php';

class Router
{
    private Telegram   $telegram;
    private OpenRouter $openRouter;
    private MarketData $market;
    private Memory     $memory;
    private Logger     $logger;

    public function __construct(Telegram $telegram, OpenRouter $openRouter, MarketData $market, Logger $logger)
    {
        $this->telegram   = $telegram;
        $this->openRouter = $openRouter;
        $this->market     = $market;
        $this->memory     = new Memory();
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
            in_array($cmd, ['/start', '/help'])   => $this->handleHelp($chatId),
            $cmd === '/clear'                      => $this->handleClear($chatId),
            $cmd === '/gold'                       => $this->handleMarket($chatId, 'XAUUSD', Prompts::gold(), '🪙 XAUUSD — تحلیل ICT/SMC'),
            $cmd === '/eurusd'                     => $this->handleMarket($chatId, 'EURUSD', Prompts::eurusd(), '💶 EUR/USD — تحلیل ICT/SMC'),
            $cmd === '/gbpusd'                     => $this->handleMarket($chatId, 'GBPUSD', Prompts::gbpusd(), '💷 GBP/USD — تحلیل ICT/SMC'),
            $cmd === '/usdjpy'                     => $this->handleMarket($chatId, 'USDJPY', Prompts::usdjpy(), '💴 USD/JPY — تحلیل ICT/SMC'),
            $cmd === '/usdchf'                     => $this->handleMarket($chatId, 'USDCHF', Prompts::usdchf(), '🇨🇭 USD/CHF — تحلیل ICT/SMC'),
            $cmd === '/mtf'                        => $this->handleMTF($chatId, $param),
            $cmd === '/session'                    => $this->handleSession($chatId),
            $cmd === '/news'                       => $this->handleNews($chatId),
            $cmd === '/setup'                      => $this->handleSetup($chatId, $param),
            $cmd === '/journal'                    => $this->handleJournal($chatId, $param),
            $cmd === '/checklist'                  => $this->handleChecklist($chatId),
            $cmd === '/reel'                       => $this->handleReel($chatId, $param),
            $cmd === '/psych'                      => $this->handlePsych($chatId, $param),
            default                                => $this->handleGeneral($chatId, $text),
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
        $this->telegram->sendMessage($chatId, "🧹 *حافظه مکالمه پاک شد.*\n\nمکالمه جدید را شروع کن.");
    }

    private function handleMarket(int|string $chatId, string $symbol, string $prompt, string $title): void
    {
        $data = $this->market->getMarketData($symbol);
        if (!$data) {
            $this->telegram->sendMessage($chatId, '⚠️ دریافت داده‌های بازار ناموفق بود. لطفاً دوباره تلاش کن.');
            return;
        }
        $reply = $this->openRouter->chat($prompt, $data, 400);
        $this->telegram->sendMessage($chatId, "*{$title}*\n\n" . $reply);
    }

    private function handleMTF(int|string $chatId, string $symbol): void
    {
        $symbol = strtoupper(trim($symbol)) ?: 'XAUUSD';
        $data   = $this->market->getMarketData($symbol);
        if (!$data) {
            $this->telegram->sendMessage($chatId, '⚠️ داده‌های بازار دریافت نشد.');
            return;
        }
        $userMsg = "Symbol: {$symbol}\nMarket Data: {$data}\nProvide multi-timeframe ICT/SMC analysis.";
        $reply   = $this->openRouter->chat(Prompts::mtf(), $userMsg, 450);
        $this->telegram->sendMessage($chatId, "*📊 تحلیل چندتایم‌فریمی — {$symbol}*\n\n" . $reply);
    }

    private function handleSession(int|string $chatId): void
    {
        $data  = $this->market->getSessionInfo();
        $reply = $this->openRouter->chat(Prompts::session(), $data, 350);
        $this->telegram->sendMessage($chatId, "*🕐 وضعیت سشن بازار*\n\n" . $reply);
    }

    private function handleNews(int|string $chatId): void
    {
        $data  = $this->market->getEconomicEvents();
        $reply = $this->openRouter->chat(Prompts::news(), $data, 350);
        $this->telegram->sendMessage($chatId, "*📅 رویدادهای اقتصادی امروز*\n\n" . $reply);
    }

    private function handleSetup(int|string $chatId, string $param): void
    {
        if (!$param) {
            $this->telegram->sendMessage($chatId, "📝 ستاپ را توضیح بده:\n`/setup XAUUSD sell 15m, OB at 4650, target 4511, SL 4670`");
            return;
        }
        $reply = $this->openRouter->chat(Prompts::setup(), $param, 450);
        $this->telegram->sendMessage($chatId, "*🔍 تحلیل ستاپ معاملاتی*\n\n" . $reply);
    }

    private function handleJournal(int|string $chatId, string $param): void
    {
        if (!$param) {
            $this->telegram->sendMessage($chatId, "📝 معامله‌ات را ثبت کن:\n`/journal buy XAUUSD 4511 tp 4580 sl 4490 — result: win`");
            return;
        }
        $reply = $this->openRouter->chat(Prompts::journal(), $param, 450);
        $this->telegram->sendMessage($chatId, "*📋 ژورنال معاملاتی*\n\n" . $reply);
    }

    private function handleChecklist(int|string $chatId): void
    {
        $session = $this->market->getSessionInfo();
        $reply   = $this->openRouter->chat(Prompts::checklist(), "Current market context:\n{$session}", 500);
        $this->telegram->sendMessage($chatId, "*📋 چک‌لیست روزانه اونیگاما*\n\n" . $reply);
    }

    private function handleReel(int|string $chatId, string $param): void
    {
        if (!$param) {
            $this->telegram->sendMessage($chatId, "📝 موضوع ریل:\n`/reel trading psychology`");
            return;
        }
        $reply = $this->openRouter->chat(Prompts::reel(), $param, 400);
        $this->telegram->sendMessage($chatId, "*🎬 ایده ریل اونیگاما*\n\n" . $reply);
    }

    private function handlePsych(int|string $chatId, string $param): void
    {
        $msg   = $param ?: 'Help me build mental discipline for trading.';
        $reply = $this->openRouter->chat(Prompts::psychology(), $msg, 450);
        $this->telegram->sendMessage($chatId, "*🧘 روانشناسی معاملاتی — Neurotrader*\n\n" . $reply);
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
