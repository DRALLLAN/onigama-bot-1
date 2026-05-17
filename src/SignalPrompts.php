<?php
declare(strict_types=1);

class SignalPrompts
{
    /**
     * پرامپت تخصصی تحلیل سیگنال
     * بر اساس متدولوژی ICT/SMC دکتر بهزاد قربانی
     */
    public static function signalAnalysis(): string
    {
        return
            "You are the Onigama Signal Intelligence System — an institutional-grade XAUUSD signal engine.\n\n" .

            "METHODOLOGY (strictly follow):\n" .
            "- ICT: Power of Three (PO3), Order Blocks (OB), Fair Value Gaps (FVG), Liquidity sweeps\n" .
            "- SMC: BOS (Break of Structure), CHoCH (Change of Character), Premium/Discount zones\n" .
            "- Liquidity: BSL (Buy-side), SSL (Sell-side), Equal Highs/Lows, Stop hunts\n" .
            "- Sessions: London (07:00-16:00 UTC), New York (12:00-21:00 UTC)\n" .
            "- SMA: SMA7 (momentum), SMA25 (trend filter), SMA99 (macro bias)\n\n" .

            "SIGNAL RULES:\n" .
            "1. Only generate a signal if there is HIGH PROBABILITY confluence (3+ factors)\n" .
            "2. Minimum R:R ratio must be 1:2\n" .
            "3. Entry must be near an unmitigated OB or FVG\n" .
            "4. Must align with HTF bias\n" .
            "5. If NO valid setup exists — output NO_SIGNAL\n\n" .

            "RISK MANAGEMENT (Onigama rules):\n" .
            "- SL: always beyond the OB or liquidity pool\n" .
            "- TP1: 50% at first liquidity target\n" .
            "- TP2: full target at next liquidity pool\n" .
            "- Max risk per trade: 1-2% of account\n\n" .

            "OUTPUT FORMAT — respond ONLY with valid JSON, no extra text:\n" .
            "{\n" .
            '  "signal": "BUY" | "SELL" | "NO_SIGNAL",' . "\n" .
            '  "entry": price_number,' . "\n" .
            '  "sl": price_number,' . "\n" .
            '  "tp1": price_number,' . "\n" .
            '  "tp2": price_number,' . "\n" .
            '  "rr": "1:X",' . "\n" .
            '  "bias": "Bullish|Bearish|Neutral",' . "\n" .
            '  "structure": "one line",' . "\n" .
            '  "entry_reason": "one line — specific OB/FVG/liquidity reason",' . "\n" .
            '  "session": "London|New York|Asian|Pre-Market",' . "\n" .
            '  "confidence": "High|Medium|Low",' . "\n" .
            '  "invalidation": "what would cancel this signal"' . "\n" .
            "}\n\n" .
            "CRITICAL: If confidence is Low or no confluence exists — return {\"signal\": \"NO_SIGNAL\"}";
    }

    public static function formatSignal(array $signal, array $priceData): string
    {
        if (($signal['signal'] ?? '') === 'NO_SIGNAL') {
            return
                "┌─────────────────────────┐\n" .
                "│ ONIGAMA · SIGNAL ENGINE\n" .
                "│ 🔍 XAUUSD Market Scan\n" .
                "│ " . gmdate('H:i') . " UTC\n" .
                "└─────────────────────────┘\n\n" .
                "⏳ *No valid setup detected*\n\n" .
                "Market conditions do not meet Onigama's institutional criteria.\n" .
                "Waiting for high-probability confluence...\n\n" .
                "─────────────────────────\n" .
                "🧠 *Onigama Signal Engine*\n" .
                "_Precision over frequency_";
        }

        $dir     = $signal['signal'] === 'BUY' ? '🟢 BUY' : '🔴 SELL';
        $emoji   = $signal['signal'] === 'BUY' ? '📈' : '📉';
        $conf    = $signal['confidence'] ?? 'Medium';
        $confIcon = match($conf) { 'High' => '🔥', 'Medium' => '⚡', default => '⚠️' };

        return
            "┌─────────────────────────┐\n" .
            "│ ONIGAMA · SIGNAL\n" .
            "│ {$emoji} XAUUSD {$signal['signal']}\n" .
            "│ " . gmdate('H:i') . " UTC · {$signal['session']}\n" .
            "└─────────────────────────┘\n\n" .
            "*{$dir} SIGNAL — XAUUSD*\n\n" .
            "💰 *ENTRY:* `{$signal['entry']}`\n" .
            "🛡 *STOP LOSS:* `{$signal['sl']}`\n" .
            "🎯 *TP1:* `{$signal['tp1']}`\n" .
            "🎯 *TP2:* `{$signal['tp2']}`\n" .
            "📊 *R:R:* `{$signal['rr']}`\n\n" .
            "─────────────────────────\n" .
            "📋 *ANALYSIS*\n" .
            "Bias: {$signal['bias']}\n" .
            "Structure: {$signal['structure']}\n" .
            "Entry: {$signal['entry_reason']}\n\n" .
            "{$confIcon} Confidence: *{$conf}*\n" .
            "❌ Invalidation: {$signal['invalidation']}\n\n" .
            "─────────────────────────\n" .
            "⚠️ _This is not financial advice. Trade at your own risk. Always use proper risk management._\n\n" .
            "🧠 *Onigama Signal Engine* · bot.onigama.com";
    }
}
