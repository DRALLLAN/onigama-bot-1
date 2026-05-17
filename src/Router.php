<?php
declare(strict_types=1);
require_once __DIR__ . '/Prompts.php';
require_once __DIR__ . '/Memory.php';

class Router
{
    private Telegram   $telegram;
    private OpenRouter $openRouter;
    private TwelveData $twelveData;
    private Memory     $memory;
    private Logger     $logger;

    public function __construct(Telegram $telegram, OpenRouter $openRouter, TwelveData $twelveData, Logger $logger)
    {
        $this->telegram   = $telegram;
        $this->openRouter = $openRouter;
        $this->twelveData = $twelveData;
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

        $this->logger->info("پیام از $chatId: " . mb_substr($text, 0, 80));
        $this->telegram->sendTyping($chatId);

        if ($text === '/start' || $text === '/help') {
            $this->handleHelp($chatId);
        } elseif ($text === '/clear') {
            $this->handleClear($chatId);
        } elseif (str_starts_with($text, '/gold')) {
            $this->handleGold($chatId);
        } elseif (str_starts_with($text, '/reel')) {
            $this->handleReel($chatId, trim(substr($text, 5)));
        } elseif (str_starts_with($text, '/psych')) {
            $this->handlePsych($chatId, trim(substr($text, 6)));
        } elseif (str_starts_with($text, '/checklist')) {
            $this->handleChecklist($chatId);
        } elseif (str_starts_with($text, '/setup')) {
            $this->handleSetup($chatId, trim(substr($text, 6)));
        } else {
            $this->handleGeneral($chatId, $text);
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
        $this->telegram->sendMessage($chatId, "🧹 *حافظه مکالمه پاک شد.*\n\nمی‌توانی مکالمه جدید را شروع کنی.");
    }

    private function handleGold(int|string $chatId): void
    {
        $candles = $this->twelveData->getGoldCandles();
        if (!$candles) {
            $this->telegram->sendMessage($chatId, '⚠️ دریافت داده‌های بازار ناموفق بود. لطفاً دوباره تلاش کن.');
            return;
        }
        // تحلیل طلا بدون حافظه — هر بار داده لایو جدید است
        $reply = $this->openRouter->chat(Prompts::gold(), $candles, 400);
        $this->telegram->sendMessage($chatId, "🪙 *XAUUSD — تحلیل ICT/SMC*\n\n" . $reply);
    }

    private function handleReel(int|string $chatId, string $topic): void
    {
        if (!$topic) {
            $this->telegram->sendMessage($chatId, "📝 موضوع ریل را بنویس:\nمثال: `/reel trading psychology`");
            return;
        }
        $reply = $this->openRouter->chat(Prompts::reel(), $topic, 400);
        $this->telegram->sendMessage($chatId, "🎬 *ایده ریل اونیگاما*\n\n" . $reply);
    }

    private function handlePsych(int|string $chatId, string $topic): void
    {
        $msg = $topic ?: 'Help me build mental discipline and emotional control for trading.';
        $reply = $this->openRouter->chat(Prompts::psychology(), $msg, 450);
        $this->telegram->sendMessage($chatId, "🧘 *روانشناسی معاملاتی — Neurotrader*\n\n" . $reply);
    }

    private function handleChecklist(int|string $chatId): void
    {
        $reply = $this->openRouter->chat(Prompts::checklist(), "Generate today's professional trading checklist for XAUUSD.", 450);
        $this->telegram->sendMessage($chatId, "📋 *چک‌لیست روزانه معامله‌گر اونیگاما*\n\n" . $reply);
    }

    private function handleSetup(int|string $chatId, string $topic): void
    {
        if (!$topic) {
            $this->telegram->sendMessage($chatId, "📝 ستاپ معاملاتی‌ات را توضیح بده:\nمثال: `/setup XAUUSD sell on 15m, OB at 4650, targeting 4511`");
            return;
        }
        $reply = $this->openRouter->chat(Prompts::setup(), $topic, 450);
        $this->telegram->sendMessage($chatId, "🔍 *تحلیل ستاپ معاملاتی*\n\n" . $reply);
    }

    private function handleGeneral(int|string $chatId, string $text): void
    {
        // ساخت پیام‌ها با تاریخچه مکالمه
        $messages = $this->memory->buildMessages($chatId, Prompts::general(), $text);

        $reply = $this->openRouter->chatWithHistory($messages, 450);

        // ذخیره پیام کاربر و پاسخ در حافظه
        $this->memory->addUserMessage($chatId, $text);
        $this->memory->addAssistantMessage($chatId, $reply);

        $this->telegram->sendMessage($chatId, $reply);
    }
}
