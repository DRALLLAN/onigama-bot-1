<?php
declare(strict_types=1);
class TwelveData
{
    private string $goldApiKey;
    private Logger $logger;
    public function __construct(string $apiKey, Logger $logger)
    {
        $this->goldApiKey = getenv('GOLDAPI_KEY') ?: $apiKey;
        $this->logger     = $logger;
    }
    public function getGoldCandles(string $interval = '15min', int $count = 5): string
    {
        $ch = curl_init('https://www.goldapi.io/api/XAU/USD');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => [
                'x-access-token: ' . $this->goldApiKey,
                'Content-Type: application/json',
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);
        if ($error || $httpCode !== 200) {
            $this->logger->error("خطای goldapi: $error | کد: $httpCode");
            return '';
        }
        $data = json_decode($response, true);
        if (empty($data['price'])) {
            $this->logger->error("داده نامعتبر: $response");
            return '';
        }
        $price     = $data['price'];
        $open      = $data['open_price']  ?? 'N/A';
        $high      = $data['high_price']  ?? 'N/A';
        $low       = $data['low_price']   ?? 'N/A';
        $change    = $data['ch']          ?? 'N/A';
        $changePct = $data['chp']         ?? 'N/A';
        $ask       = $data['ask']         ?? 'N/A';
        $bid       = $data['bid']         ?? 'N/A';
        $time      = date('Y-m-d H:i') . ' UTC';
        return
            "XAUUSD Live Market Data:\n" .
            "Time: {$time}\n" .
            "Price: {$price} | Ask: {$ask} | Bid: {$bid}\n" .
            "Open: {$open} | High: {$high} | Low: {$low}\n" .
            "Change: {$change} ({$changePct}%)\n" .
            "Analyze this live data with institutional precision.";
    }
}
