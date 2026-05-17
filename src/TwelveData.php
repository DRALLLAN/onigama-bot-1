<?php

declare(strict_types=1);

class TwelveData
{
    private string $apiKey;
    private Logger $logger;
    private string $endpoint = 'https://api.twelvedata.com';

    public function __construct(string $apiKey, Logger $logger)
    {
        $this->apiKey = $apiKey;
        $this->logger = $logger;
    }

    /**
     * دریافت آخرین شمع‌های XAUUSD
     * خروجی: متن آماده برای ارسال به هوش مصنوعی
     */
    public function getGoldCandles(string $interval = '15min', int $count = 5): string
    {
        $url = $this->endpoint . '/time_series?' . http_build_query([
            'symbol'     => 'XAUUSD',
            'interval'   => $interval,
            'outputsize' => $count,
            'apikey'     => $this->apiKey,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->logger->error("خطای cURL دوازده‌داده: $error");
            return '';
        }

        $data = json_decode($response, true);

        if (empty($data['values']) || !is_array($data['values'])) {
            $this->logger->error("داده نامعتبر از دوازده‌داده: " . $response);
            return '';
        }

        // ساخت متن OHLC برای هوش مصنوعی
        $lines = [];
        foreach ($data['values'] as $i => $candle) {
            $num     = $i + 1;
            $label   = $i === 0 ? ' (newest)' : '';
            $lines[] = "Candle {$num}{$label}: " .
                       "Time:{$candle['datetime']} " .
                       "O:{$candle['open']} " .
                       "H:{$candle['high']} " .
                       "L:{$candle['low']} " .
                       "C:{$candle['close']}";
        }

        return implode("\n", $lines);
    }
}
