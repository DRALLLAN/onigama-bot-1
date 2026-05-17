<?php
declare(strict_types=1);

class MarketData
{
    private string $goldApiKey;
    private Logger $logger;

    private array $symbolMap = [
        'XAUUSD' => ['goldapi' => 'XAU/USD', 'type' => 'gold'],
        'EURUSD' => ['goldapi' => null,       'type' => 'forex', 'fx' => 'EUR', 'base' => 'USD'],
        'GBPUSD' => ['goldapi' => null,       'type' => 'forex', 'fx' => 'GBP', 'base' => 'USD'],
        'USDJPY' => ['goldapi' => null,       'type' => 'forex', 'fx' => 'USD', 'base' => 'JPY'],
        'USDCHF' => ['goldapi' => null,       'type' => 'forex', 'fx' => 'USD', 'base' => 'CHF'],
    ];

    public function __construct(string $goldApiKey, Logger $logger)
    {
        $this->goldApiKey = $goldApiKey;
        $this->logger     = $logger;
    }

    public function getMarketData(string $symbol): string
    {
        $symbol = strtoupper(trim($symbol));

        if ($symbol === 'XAUUSD') {
            return $this->fetchGold();
        }

        if (isset($this->symbolMap[$symbol])) {
            return $this->fetchForex($symbol);
        }

        return '';
    }

    public function getSessionInfo(): string
    {
        $utcHour    = (int) gmdate('G');
        $utcMinute  = (int) gmdate('i');
        $dayOfWeek  = gmdate('l');
        $utcTime    = gmdate('H:i') . ' UTC';
        $totalMins  = $utcHour * 60 + $utcMinute;

        // تشخیص سشن فعال
        if ($totalMins >= 0 && $totalMins < 120) {
            $session = 'Late New York / Early Asian';
            $energy  = 'Low — thin liquidity, avoid major entries';
            $focus   = 'USDJPY, AUDUSD';
            $behavior = 'Consolidation, range-bound, stop hunts possible';
        } elseif ($totalMins >= 120 && $totalMins < 480) {
            $session = 'Asian Session';
            $energy  = 'Low-Medium — JPY pairs most active';
            $focus   = 'USDJPY, USDCHF, AUDUSD';
            $behavior = 'Range formation, accumulation phase, liquidity building';
        } elseif ($totalMins >= 480 && $totalMins < 600) {
            $session = 'London Pre-Market (Silver Bullet)';
            $energy  = 'High — major moves begin here';
            $focus   = 'GBPUSD, EURUSD, XAUUSD';
            $behavior = 'Smart money accumulation, false moves before real direction';
        } elseif ($totalMins >= 600 && $totalMins < 960) {
            $session = 'London Session';
            $energy  = 'Very High — highest institutional activity';
            $focus   = 'EURUSD, GBPUSD, XAUUSD';
            $behavior = 'Trend formation, OB sweeps, FVG fills, real direction established';
        } elseif ($totalMins >= 960 && $totalMins < 1080) {
            $session = 'New York Session (London Overlap)';
            $energy  = 'Very High — peak volatility window';
            $focus   = 'XAUUSD, EURUSD, GBPUSD';
            $behavior = 'Maximum volume, ICT Silver Bullet 10-11 AM NY, reversals possible';
        } elseif ($totalMins >= 1080 && $totalMins < 1320) {
            $session = 'New York Afternoon';
            $energy  = 'Medium — volume declining';
            $focus   = 'USDJPY, USDCHF';
            $behavior = 'Profit taking, ranging, avoid late entries';
        } else {
            $session = 'Market Close / Pre-Asian';
            $energy  = 'Very Low — avoid trading';
            $focus   = 'Prepare watchlist for next session';
            $behavior = 'Review journal, plan tomorrow';
        }

        return
            "Current UTC Time: {$utcTime}\n" .
            "Day: {$dayOfWeek}\n" .
            "Active Session: {$session}\n" .
            "Energy Level: {$energy}\n" .
            "Focus Instruments: {$focus}\n" .
            "Typical Behavior: {$behavior}";
    }

    public function getEconomicEvents(): string
    {
        // رویدادهای ثابت بر اساس روز هفته
        $day   = gmdate('l');
        $date  = gmdate('Y-m-d');
        $events = $this->getWeeklyEvents($day);

        return
            "Date: {$date} ({$day})\n" .
            "High-Impact Events Today:\n" . $events;
    }

    private function getWeeklyEvents(string $day): string
    {
        $events = [
            'Monday'    => "- No major scheduled events\n- Watch: Weekend geopolitical gaps\n- Focus: Market sentiment reset",
            'Tuesday'   => "- 15:00 UTC: US Consumer Confidence\n- Watch: Any Fed member speeches\n- Risk: Medium",
            'Wednesday' => "- 12:30 UTC: US ADP Employment\n- 14:30 UTC: US Crude Oil Inventories\n- 18:00 UTC: FOMC Minutes (if scheduled)\n- Risk: HIGH",
            'Thursday'  => "- 12:30 UTC: US Jobless Claims\n- 12:30 UTC: ECB statements (if scheduled)\n- Risk: High — major USD and EUR moves expected",
            'Friday'    => "- 12:30 UTC: NFP / US Jobs Report (first Friday)\n- 12:30 UTC: US Average Hourly Earnings\n- Risk: EXTREME — avoid positions before release\n- Onigama Rule: No new trades 30min before NFP",
            'Saturday'  => "- Markets closed\n- Review week performance\n- Plan next week strategy",
            'Sunday'    => "- Forex markets open 21:00 UTC\n- Watch: Weekend news impact on open\n- Prepare: Mark key levels before Asian open",
        ];

        return $events[$day] ?? 'No data available';
    }

    private function fetchGold(): string
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
            $this->logger->error("خطای goldapi: $error | $httpCode");
            return '';
        }

        $data = json_decode($response, true);
        if (empty($data['price'])) return '';

        $price  = $data['price'];
        $open   = $data['open_price']  ?? 'N/A';
        $high   = $data['high_price']  ?? 'N/A';
        $low    = $data['low_price']   ?? 'N/A';
        $ch2    = $data['ch']          ?? 'N/A';
        $chp    = $data['chp']         ?? 'N/A';
        $ask    = $data['ask']         ?? 'N/A';
        $bid    = $data['bid']         ?? 'N/A';
        $time   = gmdate('Y-m-d H:i') . ' UTC';

        return
            "Symbol: XAUUSD (Gold Spot)\n" .
            "Time: {$time}\n" .
            "Price: {$price} | Ask: {$ask} | Bid: {$bid}\n" .
            "Open: {$open} | High: {$high} | Low: {$low}\n" .
            "Change: {$ch2} ({$chp}%)\n" .
            "Analyze with institutional ICT/SMC precision.";
    }

    private function fetchForex(string $symbol): string
    {
        // استفاده از ExchangeRate API رایگان
        $map = [
            'EURUSD' => ['from' => 'EUR', 'to' => 'USD'],
            'GBPUSD' => ['from' => 'GBP', 'to' => 'USD'],
            'USDJPY' => ['from' => 'USD', 'to' => 'JPY'],
            'USDCHF' => ['from' => 'USD', 'to' => 'CHF'],
        ];

        $pair = $map[$symbol];
        $url  = "https://open.er-api.com/v6/latest/{$pair['from']}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            $this->logger->error("خطای forex API: $error | $httpCode");
            return '';
        }

        $data = json_decode($response, true);
        if (empty($data['rates'][$pair['to']])) return '';

        $price = $data['rates'][$pair['to']];
        $time  = gmdate('Y-m-d H:i') . ' UTC';

        return
            "Symbol: {$symbol}\n" .
            "Time: {$time}\n" .
            "Current Rate: {$price}\n" .
            "Base: {$pair['from']} | Quote: {$pair['to']}\n" .
            "Note: Daily rate — use for directional bias analysis.\n" .
            "Analyze with institutional ICT/SMC precision.";
    }
}
