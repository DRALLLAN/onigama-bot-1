<?php
declare(strict_types=1);

require_once __DIR__ . '/SignalPrompts.php';

class SignalEngine
{
    private OpenRouter $ai;
    private MarketData $market;
    private Telegram   $telegram;
    private Logger     $logger;
    private string     $channel;
    private string     $lockFile;

    public function __construct(OpenRouter $ai, MarketData $market, Telegram $telegram, Logger $logger)
    {
        $this->ai       = $ai;
        $this->market   = $market;
        $this->telegram = $telegram;
        $this->logger   = $logger;
        $this->channel  = TELEGRAM_CHANNEL;
        $this->lockFile = sys_get_temp_dir() . '/onigama_signal.lock';
    }

    /**
     * اجرای اصلی موتور سیگنال
     * هر بار که صدا زده می‌شود یک چرخه تحلیل کامل انجام می‌دهد
     */
    public function run(): array
    {
        // بررسی قفل — جلوگیری از اجرای همزمان
        if ($this->isLocked()) {
            return ['status' => 'locked', 'message' => 'Signal engine already running'];
        }

        $this->lock();

        try {
            // دریافت داده لایو
            $marketData = $this->market->getMarketData('XAUUSD');
            $priceData  = $this->market->getPriceData('XAUUSD');
            $sessionInfo = $this->market->getSessionInfo();

            if (!$marketData) {
                $this->unlock();
                return ['status' => 'error', 'message' => 'Failed to fetch market data'];
            }

            // بررسی سشن — فقط در لندن و نیویورک سیگنال صادر کن
            $utcHour = (int) gmdate('G');
            $validSession = ($utcHour >= 7 && $utcHour <= 21);

            if (!$validSession) {
                $this->unlock();
                return ['status' => 'skip', 'message' => 'Outside trading session (07:00-21:00 UTC)'];
            }

            // ارسال به هوش مصنوعی برای تحلیل
            $userMessage =
                "XAUUSD Live Data:\n{$marketData}\n\n" .
                "Session Info:\n{$sessionInfo}\n\n" .
                "Analyze and generate signal if high-probability setup exists.";

            $rawResponse = $this->ai->chat(
                SignalPrompts::signalAnalysis(),
                $userMessage,
                500
            );

            // تجزیه JSON
            $signal = $this->parseSignal($rawResponse);

            if (!$signal) {
                $this->logger->error("سیگنال نامعتبر: " . $rawResponse);
                $this->unlock();
                return ['status' => 'error', 'message' => 'Invalid signal format'];
            }

            // فرمت‌بندی پیام
            $message = SignalPrompts::formatSignal($signal, $priceData);

            // ارسال به کانال فقط اگر سیگنال واقعی باشد
            $sent = false;
            if ($signal['signal'] !== 'NO_SIGNAL' && $signal['confidence'] !== 'Low') {
                if ($this->channel) {
                    $sent = $this->telegram->sendMessage($this->channel, $message);
                    $this->logger->info("سیگنال ارسال شد به کانال: " . $signal['signal']);
                }
            }

            // ذخیره آخرین سیگنال
            $this->saveLastSignal($signal, $priceData);

            $this->unlock();
            return [
                'status'  => 'success',
                'signal'  => $signal,
                'sent'    => $sent,
                'message' => $message,
            ];

        } catch (\Throwable $e) {
            $this->logger->error("خطای موتور سیگنال: " . $e->getMessage());
            $this->unlock();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * دریافت آخرین سیگنال ذخیره شده
     */
    public function getLastSignal(): ?array
    {
        $file = sys_get_temp_dir() . '/onigama_last_signal.json';
        if (!file_exists($file)) return null;
        return json_decode(file_get_contents($file), true);
    }

    private function parseSignal(string $raw): ?array
    {
        // پاک کردن backtick و json کد
        $clean = preg_replace('/```json|```/i', '', $raw);
        $clean = trim($clean);

        // پیدا کردن JSON در متن
        if (preg_match('/\{.*\}/s', $clean, $m)) {
            $data = json_decode($m[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }

        return null;
    }

    private function saveLastSignal(array $signal, array $priceData): void
    {
        $file = sys_get_temp_dir() . '/onigama_last_signal.json';
        file_put_contents($file, json_encode([
            'signal'    => $signal,
            'price'     => $priceData,
            'timestamp' => gmdate('Y-m-d H:i:s') . ' UTC',
        ], JSON_UNESCAPED_UNICODE));
    }

    private function isLocked(): bool
    {
        if (!file_exists($this->lockFile)) return false;
        // قفل قدیمی‌تر از ۵ دقیقه را نادیده بگیر
        return (time() - filemtime($this->lockFile)) < 300;
    }

    private function lock(): void   { file_put_contents($this->lockFile, time()); }
    private function unlock(): void { @unlink($this->lockFile); }
}
