<?php
declare(strict_types=1);

class MessageFormatter
{
    private static function header(string $module, string $title): string
    {
        $time = gmdate('H:i') . ' UTC';
        return
            "┌─────────────────────────┐\n" .
            "│ ONIGAMA · {$module}\n" .
            "│ {$title}\n" .
            "│ {$time}\n" .
            "└─────────────────────────┘\n\n";
    }

    private static function footer(string $tag): string
    {
        return
            "\n\n─────────────────────────\n" .
            "🧠 *Onigama AI Brain*\n" .
            "bot.onigama.com · #{$tag}";
    }

    public static function market(string $symbol, string $analysis): string
    {
        $labels = [
            'XAUUSD' => ['icon' => '🪙', 'name' => 'Gold Spot',    'tag' => 'XAUUSD'],
            'EURUSD' => ['icon' => '💶', 'name' => 'Euro/Dollar',   'tag' => 'EURUSD'],
            'GBPUSD' => ['icon' => '💷', 'name' => 'Pound/Dollar',  'tag' => 'GBPUSD'],
            'USDJPY' => ['icon' => '💴', 'name' => 'Dollar/Yen',    'tag' => 'USDJPY'],
            'USDCHF' => ['icon' => '🇨🇭', 'name' => 'Dollar/Franc', 'tag' => 'USDCHF'],
        ];

        $l = $labels[$symbol] ?? ['icon' => '📊', 'name' => $symbol, 'tag' => $symbol];

        return
            self::header('INTELLIGENCE', "{$l['icon']} {$symbol} — ICT/SMC Analysis") .
            $analysis .
            self::footer($l['tag']);
    }

    public static function mtf(string $symbol, string $analysis): string
    {
        return
            self::header('MULTI-TIMEFRAME', "📊 {$symbol} — Top-Down Analysis") .
            $analysis .
            self::footer('MTF');
    }

    public static function session(string $analysis): string
    {
        return
            self::header('SESSION', "🕐 Market Session Intelligence") .
            $analysis .
            self::footer('SESSION');
    }

    public static function news(string $analysis): string
    {
        return
            self::header('ECONOMIC', "📅 High-Impact Events Today") .
            $analysis .
            self::footer('NEWS');
    }

    public static function setup(string $analysis): string
    {
        return
            self::header('SETUP REVIEW', "🔍 Trade Setup Analysis") .
            $analysis .
            self::footer('SETUP');
    }

    public static function journal(string $analysis): string
    {
        return
            self::header('JOURNAL', "📋 Trade Review") .
            $analysis .
            self::footer('JOURNAL');
    }

    public static function checklist(string $analysis): string
    {
        return
            self::header('PROTOCOL', "✅ Daily Trading Checklist") .
            $analysis .
            self::footer('CHECKLIST');
    }

    public static function reel(string $analysis): string
    {
        return
            self::header('CONTENT', "🎬 Instagram Reel Strategy") .
            $analysis .
            self::footer('REEL');
    }

    public static function psychology(string $analysis): string
    {
        return
            self::header('NEUROTRADER', "🧘 Trading Psychology") .
            $analysis .
            self::footer('PSYCH');
    }
}
