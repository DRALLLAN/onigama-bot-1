<?php

declare(strict_types=1);

require_once __DIR__ . '/Prompts.php';

class Router
{
    private Telegram   $telegram;
    private OpenRouter $openRouter;
    private TwelveData $twelveData;
    private Logger     $logger;

    public function __construct(
        Telegram   $telegram,
        OpenRouter $openRouter,
        TwelveData $twelveData,
        Logger     $logger
    ) {
        $this->telegram   = $telegram;
        $this->openRouter = $openRouter;
        $this->twelveData = $twelveData;
        $this->logger     = $logger;
    }

    public function handle(array $update): void
    {
        $message = $update['message'] ?? null;
        if (!$message) return;

        $chatId = $message['chat']['id'];
        $text   = trim($message['text'] ?? '');

        if (!$text) return;

        $this->logger->info("پیام دریافت شد از $chatId: " . mb_substr($text, 0, 80));

        // نشانه تایپ نمایش داده می‌شود
        $this->telegram->sendTyping($chatId);

        // مسیریابی بر اساس دستور
        if ($text === '/start' || $text === '/help') {
            $this->handleHelp($chatId);

        } elseif (str_starts_with($text, '/gold')) {
            $this->handleGold($chatId);

        } elseif (str_starts_with($text, '/reel')) {
            $topic = trim(substr($text, 5));
            $this->handleReel($chatId, $topic);

        } elseif (str_starts_with($text, '/psych')) {
            $topic = trim(substr($text, 6));
            $this->handlePsych($chatId, $topic);

        } else {
            $this->handleGeneral($chatId, $text);
        }
    }

    // ─── راهنما ───────────────────────────────────────────────────────────────

    private function handleHelp(int|string $chatId): void
    {
        $this->telegram->sendMessage($chatId, Prompts::help());
    }

    // ─── تحلیل طلا ───────────────────────────────────────────────────────────

    private function handleGold(int|string $chatId): void
    {
        $candles = $this->twelveData->getGoldCandles('15min', 5);

        if (!$candles) {
            $this->telegram->sendMessage(
                $chatId,
                '⚠️ دریافت داده‌های بازار ناموفق بود. لطفاً دوباره تلاش کن.'
            );
            return;
        }

        $reply = $this->openRouter->chat(
            Prompts::gold(),
            $candles,
            300
        );

        $this->telegram->sendMessage($chatId, "🪙 *XAUUSD — تحلیل ۱۵ دقیقه‌ای*\n\n" . $reply);
    }

    // ─── ایده ریل ────────────────────────────────────────────────────────────

    private function handleReel(int|string $chatId, string $topic): void
    {
        if (!$topic) {
            $this->telegram->sendMessage(
                $chatId,
                '📝 موضوع ریل را بنویس:\nمثال: `/reel trading psychology`'
            );
            return;
        }

        $reply = $this->openRouter->chat(
            Prompts::reel(),
            $topic,
            350
        );

        $this->telegram->sendMessage($chatId, "🎬 *ایده ریل اینستاگرام*\n\n" . $reply);
    }

    // ─── روانشناسی معاملاتی ──────────────────────────────────────────────────

    private function handlePsych(int|string $chatId, string $topic): void
    {
        $userMessage = $topic ?: 'Help me with trading psychology and mental discipline.';

        $reply = $this->openRouter->chat(
            Prompts::psychology(),
            $userMessage,
            400
        );

        $this->telegram->sendMessage($chatId, "🧘 *روانشناسی معاملاتی*\n\n" . $reply);
    }

    // ─── دستیار عمومی ────────────────────────────────────────────────────────

    private function handleGeneral(int|string $chatId, string $text): void
    {
        $reply = $this->openRouter->chat(
            Prompts::general(),
            $text,
            400
        );

        $this->telegram->sendMessage($chatId, $reply);
    }
}
