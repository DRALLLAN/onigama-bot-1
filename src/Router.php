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

        // تشخیص زبان
        $lang = $this->detectLanguage($text);

        $cmd   = strtolower(explode(' ', $text)[0]);
        $param = trim(substr($text, strlen($cmd)));

        match(true) {
            in_array($cmd, ['/start', '/help']) => $this->handleHelp($chatId, $lang),
            $cmd === '/clear'                   => $this->handleClear($chatId, $lang),
            $cmd === '/gold'                    => $this->handleMarket($chatId, 'XAUUSD', $lang),
            $cmd === '/eurusd'                  => $this->handleMarket($chatId, 'EURUSD', $lang),
            $cmd === '/gbpusd'                  => $this->handleMarket($chatId, 'GBPUSD', $lang),
            $cmd === '/usdjpy'                  => $this->handleMarket($chatId, 'USDJPY', $lang),
            $cmd === '/usdchf'                  => $this->handleMarket($chatId, 'USDCHF', $lang),
            $cmd === '/mtf'                     => $this->handleMTF($chatId, $param, $lang),
            $cmd === '/session'                 => $this->handleSession($chatId, $lang),
            $cmd === '/news'                    => $this->handleNews($chatId, $lang),
            $cmd === '/setup'                   => $this->handleSetup($chatId, $param, $lang),
            $cmd === '/journal'                 => $this->handleJournal($chatId, $param, $lang),
            $cmd === '/checklist'               => $this->handleChecklist($chatId, $lang),
            $cmd === '/reel'                    => $this->handleReel($chatId, $param, $lang),
            $cmd === '/psych'                   => $this->handlePsych($chatId, $param, $lang),
            default                             => $this->handleGeneral($chatId, $text, $lang),
        };
    }

    // ─── تشخیص زبان ──────────────────────────────────────────────────────────
    private function detectLanguage(string $text): string
    {
        // حروف فارسی/عربی
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return 'fa';
        }
        // حروف عربی
        if (preg_match('/[\x{0750}-\x{077F}]/u', $text)) {
            return 'ar';
        }
        return 'en';
    }

    // ─── دستورالعمل زبان برای هوش مصنوعی ────────────────────────────────────
    private function langInstruction(string $lang): string
    {
        return match($lang) {
            'fa' => "\n\nمهم: پاسخ را کاملاً به زبان فارسی بنویس. از اصطلاحات تخصصی معاملاتی انگلیسی مانند ICT، SMC، OB، FVG، BOS، CHoCH می‌توانی استفاده کنی ولی توضیحات باید فارسی باشد.",
            'ar' => "\n\nمهم: اكتب الإجابة باللغة العربية بالكامل. يمكنك استخدام المصطلحات التقنية الإنجليزية مثل ICT وSMC.",
            default => '',
        };
    }

    // ─── هدرهای پیام بر اساس زبان ────────────────────────────────────────────
    private function headers(string $lang): array
    {
        return match($lang) {
            'fa' => [
                'market'    => '🪙 *تحلیل بازار — ICT/SMC*',
                'mtf'       => '📊 *تحلیل چندتایم‌فریمی*',
                'session'   => '🕐 *وضعیت سشن بازار*',
                'news'      => '📅 *رویدادهای اقتصادی امروز*',
                'setup'     => '🔍 *تحلیل ستاپ معاملاتی*',
                'journal'   => '📋 *ژورنال معاملاتی*',
                'checklist' => '✅ *چک‌لیست روزانه اونیگاما*',
                'reel'      => '🎬 *ایده ریل اونیگاما*',
                'psych'     => '🧘 *روانشناسی معاملاتی*',
                'clear'     => "🧹 *حافظه مکالمه پاک شد.*\n\nمکالمه جدید را شروع کن.",
                'no_data'   => '⚠️ دریافت داده‌های بازار ناموفق بود. لطفاً دوباره تلاش کن.',
                'no_param_setup'  => "📝 ستاپ را توضیح بده:\n`/setup XAUUSD sell 15m, OB at 4650`",
                'no_param_journal'=> "📝 معامله‌ات را ثبت کن:\n`/journal buy XAUUSD 4511 tp 4580 sl 4490`",
                'no_param_reel'   => "📝 موضوع ریل:\n`/reel روانشناسی معاملاتی`",
                'no_param_psych'  => 'Help me build mental discipline for trading.',
            ],
            default => [
                'market'    => '🪙 *Market Analysis — ICT/SMC*',
                'mtf'       => '📊 *Multi-Timeframe Analysis*',
                'session'   => '🕐 *Market Session Intelligence*',
                'news'      => '📅 *Economic Events Today*',
                'setup'     => '🔍 *Trade Setup Analysis*',
                'journal'   => '📋 *Trade Journal Review*',
                'checklist' => '✅ *Onigama Daily Checklist*',
                'reel'      => '🎬 *Onigama Reel Strategy*',
                'psych'     => '🧘 *Trading Psychology*',
                'clear'     => "🧹 *Memory cleared.*\n\nStart a new conversation.",
                'no_data'   => '⚠️ Failed to fetch market data. Please try again.',
                'no_param_setup'  => "📝 Describe your setup:\n`/setup XAUUSD sell 15m, OB at 4650`",
                'no_param_journal'=> "📝 Log your trade:\n`/journal buy XAUUSD 4511 tp 4580 sl 4490`",
                'no_param_reel'   => "📝 Enter reel topic:\n`/reel trading psychology`",
                'no_param_psych'  => 'Help me build mental discipline for trading.',
            ],
        };
    }

    private function handleHelp(int|string $chatId, string $lang): void
    {
        $this->memory->clearHistory($chatId);
        $msg = $lang === 'fa' ? $this->helpFa() : Prompts::help();
        $this->telegram->sendMessage($chatId, $msg);
    }

    private function helpFa(): string
    {
        return
            "🧠 *اونیگاما هوش مصنوعی — سیستم هوش مالی*\n\n" .
            "━━━━━━━━━━━━━━━━━━━━\n" .
            "📌 *تحلیل بازار:*\n" .
            "🪙 `/gold` — تحلیل لایو XAUUSD\n" .
            "💶 `/eurusd` — تحلیل EUR/USD\n" .
            "💷 `/gbpusd` — تحلیل GBP/USD\n" .
            "💴 `/usdjpy` — تحلیل USD/JPY\n" .
            "🇨🇭 `/usdchf` — تحلیل USD/CHF\n" .
            "📊 `/mtf [نماد]` — تحلیل چندتایم‌فریمی\n\n" .
            "📌 *ابزارهای معاملاتی:*\n" .
            "🕐 `/session` — وضعیت سشن فعلی\n" .
            "📅 `/news` — رویدادهای اقتصادی امروز\n" .
            "🔍 `/setup [توضیح]` — تحلیل ستاپ\n" .
            "📋 `/journal [معامله]` — ژورنال معاملاتی\n" .
            "✅ `/checklist` — چک‌لیست روزانه\n\n" .
            "📌 *محتوا و برند:*\n" .
            "🎬 `/reel [موضوع]` — ایده ریل اینستاگرام\n" .
            "🧘 `/psych [موضوع]` — روانشناسی معاملاتی\n\n" .
            "📌 *سیستم:*\n" .
            "🗑 `/clear` — پاک کردن حافظه مکالمه\n" .
            "❓ `/help` — این راهنما\n" .
            "━━━━━━━━━━━━━━━━━━━━\n" .
            "_دقت بر تعداد. ورود تک‌تیرانداز. منطق نهادی._";
    }

    private function handleClear(int|string $chatId, string $lang): void
    {
        $this->memory->clearHistory($chatId);
        $h = $this->headers($lang);
        $this->telegram->sendMessage($chatId, $h['clear']);
    }

    private function handleMarket(int|string $chatId, string $symbol, string $lang): void
    {
        $h = $this->headers($lang);
        $prompts = [
            'XAUUSD' => Prompts::gold(),
            'EURUSD' => Prompts::eurusd(),
            'GBPUSD' => Prompts::gbpusd(),
            'USDJPY' => Prompts::usdjpy(),
            'USDCHF' => Prompts::usdchf(),
        ];

        $marketData = $this->market->getMarketData($symbol);
        if (!$marketData) {
            $this->telegram->sendMessage($chatId, $h['no_data']);
            return;
        }

        $prompt   = $prompts[$symbol] . $this->langInstruction($lang);
        $analysis = $this->openRouter->chat($prompt, $marketData, 400);

        $title    = $h['market'] . " — {$symbol}";
        $msg      = MessageFormatter::market($symbol, $analysis);
        $this->telegram->sendMessage($chatId, $msg);
    }

    private function handleMTF(int|string $chatId, string $symbol, string $lang): void
    {
        $h      = $this->headers($lang);
        $symbol = strtoupper(trim($symbol)) ?: 'XAUUSD';
        $data   = $this->market->getMarketData($symbol);
        if (!$data) { $this->telegram->sendMessage($chatId, $h['no_data']); return; }

        $prompt   = Prompts::mtf() . $this->langInstruction($lang);
        $analysis = $this->openRouter->chat($prompt, "Symbol: {$symbol}\n{$data}", 450);
        $this->telegram->sendMessage($chatId, "{$h['mtf']} — {$symbol}\n\n" . $analysis);
    }

    private function handleSession(int|string $chatId, string $lang): void
    {
        $h        = $this->headers($lang);
        $data     = $this->market->getSessionInfo();
        $prompt   = Prompts::session() . $this->langInstruction($lang);
        $analysis = $this->openRouter->chat($prompt, $data, 350);
        $this->telegram->sendMessage($chatId, "{$h['session']}\n\n" . $analysis);
    }

    private function handleNews(int|string $chatId, string $lang): void
    {
        $h        = $this->headers($lang);
        $data     = $this->market->getEconomicEvents();
        $prompt   = Prompts::news() . $this->langInstruction($lang);
        $analysis = $this->openRouter->chat($prompt, $data, 350);
        $this->telegram->sendMessage($chatId, "{$h['news']}\n\n" . $analysis);
    }

    private function handleSetup(int|string $chatId, string $param, string $lang): void
    {
        $h = $this->headers($lang);
        if (!$param) { $this->telegram->sendMessage($chatId, $h['no_param_setup']); return; }
        $prompt   = Prompts::setup() . $this->langInstruction($lang);
        $analysis = $this->openRouter->chat($prompt, $param, 450);
        $this->telegram->sendMessage($chatId, "{$h['setup']}\n\n" . $analysis);
    }

    private function handleJournal(int|string $chatId, string $param, string $lang): void
    {
        $h = $this->headers($lang);
        if (!$param) { $this->telegram->sendMessage($chatId, $h['no_param_journal']); return; }
        $prompt   = Prompts::journal() . $this->langInstruction($lang);
        $analysis = $this->openRouter->chat($prompt, $param, 450);
        $this->telegram->sendMessage($chatId, "{$h['journal']}\n\n" . $analysis);
    }

    private function handleChecklist(int|string $chatId, string $lang): void
    {
        $h        = $this->headers($lang);
        $session  = $this->market->getSessionInfo();
        $prompt   = Prompts::checklist() . $this->langInstruction($lang);
        $analysis = $this->openRouter->chat($prompt, $session, 500);
        $this->telegram->sendMessage($chatId, "{$h['checklist']}\n\n" . $analysis);
    }

    private function handleReel(int|string $chatId, string $param, string $lang): void
    {
        $h = $this->headers($lang);
        if (!$param) { $this->telegram->sendMessage($chatId, $h['no_param_reel']); return; }
        $prompt   = Prompts::reel() . $this->langInstruction($lang);
        $analysis = $this->openRouter->chat($prompt, $param, 400);
        $this->telegram->sendMessage($chatId, "{$h['reel']}\n\n" . $analysis);
    }

    private function handlePsych(int|string $chatId, string $param, string $lang): void
    {
        $h       = $this->headers($lang);
        $msg     = $param ?: $h['no_param_psych'];
        $prompt  = Prompts::psychology() . $this->langInstruction($lang);
        $analysis = $this->openRouter->chat($prompt, $msg, 450);
        $this->telegram->sendMessage($chatId, "{$h['psych']}\n\n" . $analysis);
    }

    private function handleGeneral(int|string $chatId, string $text, string $lang): void
    {
        $prompt   = Prompts::general() . $this->langInstruction($lang);
        $messages = $this->memory->buildMessages($chatId, $prompt, $text);
        $reply    = $this->openRouter->chatWithHistory($messages, 450);
        $this->memory->addUserMessage($chatId, $text);
        $this->memory->addAssistantMessage($chatId, $reply);
        $this->telegram->sendMessage($chatId, $reply);
    }
}
