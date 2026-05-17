<?php
declare(strict_types=1);

class ImageGenerator
{
    private Logger $logger;
    private string $logoPath;
    private string $outDir;

    // رنگ‌های برند
    private array $DARK     = [6,   6,   12];
    private array $CARD     = [14,  14,  24];
    private array $BORDER   = [30,  30,  52];
    private array $GOLD     = [201, 185, 110];
    private array $GOLD_DIM = [130, 118, 68];
    private array $WHITE    = [235, 235, 250];
    private array $DESC     = [175, 175, 205];
    private array $LABEL    = [120, 120, 155];
    private array $RED      = [210, 70,  70];
    private array $GREEN    = [55,  185, 115];
    private array $TEAL     = [40,  175, 168];

    public function __construct(Logger $logger)
    {
        $this->logger   = $logger;
        $this->logoPath = __DIR__ . '/../logo_transparent.png';
        $this->outDir   = sys_get_temp_dir() . '/onigama_imgs/';
        if (!is_dir($this->outDir)) mkdir($this->outDir, 0755, true);
    }

    public function market(string $symbol, array $rows, array $priceData = []): ?string
    {
        if (!extension_loaded('gd')) {
            $this->logger->error('GD extension not available');
            return null;
        }

        $W = 1080; $H = 1350;
        $img = imagecreatetruecolor($W, $H);

        $cDark   = $this->alloc($img, $this->DARK);
        $cCard   = $this->alloc($img, $this->CARD);
        $cBorder = $this->alloc($img, $this->BORDER);
        $cGold   = $this->alloc($img, $this->GOLD);
        $cGoldD  = $this->alloc($img, $this->GOLD_DIM);
        $cWhite  = $this->alloc($img, $this->WHITE);
        $cDesc   = $this->alloc($img, $this->DESC);
        $cLabel  = $this->alloc($img, $this->LABEL);
        $cRed    = $this->alloc($img, $this->RED);
        $cGreen  = $this->alloc($img, $this->GREEN);
        $cTeal   = $this->alloc($img, $this->TEAL);

        $colorMap = [
            'red'   => $cRed,
            'gold'  => $cGold,
            'teal'  => $cTeal,
            'green' => $cGreen,
            'white' => $cWhite,
        ];

        // پس‌زمینه
        imagefill($img, 0, 0, $cDark);

        // نوار طلایی بالا
        imagefilledrectangle($img, 0, 0, $W, 5, $cGold);

        // لوگو
        $this->pasteLogo($img, $W, 38, 130);

        // خط جداکننده
        imagefilledrectangle($img, 80, 195, $W-80, 196, $cBorder);

        // برچسب ماژول
        $modLabel = 'ONIGAMA  .  INTELLIGENCE';
        imagestring($img, 3, $W/2 - strlen($modLabel)*4, 218, $modLabel, $cGoldD);

        // عنوان
        $title = "{$symbol} -- ICT/SMC Analysis";
        imagestring($img, 5, $W/2 - strlen($title)*5, 258, $title, $cWhite);

        // قیمت
        $price = $priceData['price'] ?? '';
        if ($price) {
            imagestring($img, 5, $W/2 - strlen($price)*6, 328, $price, $cGold);
        }

        // تغییر
        $change  = $priceData['change']     ?? '';
        $changep = $priceData['change_pct'] ?? '';
        if ($change) {
            $signColor = str_contains($change, '-') ? $cRed : $cGreen;
            imagestring($img, 4, 200, 405, $change, $signColor);
            imagestring($img, 4, 650, 405, $changep . '%', $signColor);
        }

        // High/Low بار
        $high = $priceData['high'] ?? '';
        $low  = $priceData['low']  ?? '';
        if ($high || $low) {
            imagefilledrectangle($img, 80, 428, $W-80, 470, $cCard);
            imagerectangle($img, 80, 428, $W-80, 470, $cBorder);
            imagestring($img, 4, 110, 447, "L  {$low}", $cRed);
            imagestring($img, 4, $W-200, 447, "H  {$high}", $cGreen);
        }

        imagefilledrectangle($img, 80, 486, $W-80, 487, $cBorder);

        // کارت‌های تحلیل
        $y = 500;
        foreach ($rows as $row) {
            $rowColor = $colorMap[$row['color'] ?? 'white'] ?? $cWhite;
            $this->drawCard($img, $y, $row['icon'] ?? '', $row['label'] ?? '',
                           $row['value'] ?? '', $row['desc'] ?? '', $rowColor,
                           $cCard, $cBorder, $cLabel, $cDesc);
            $y += 114;
        }

        // فوتر
        imagefilledrectangle($img, 80, $H-90, $W-80, $H-89, $cBorder);
        imagefilledrectangle($img, 0, $H-5, $W, $H, $cGold);
        $footerText = 'ONIGAMA AI BRAIN';
        imagestring($img, 4, $W/2 - strlen($footerText)*5, $H-68, $footerText, $cGoldD);
        $subText = 'bot.onigama.com  #' . $symbol;
        imagestring($img, 3, $W/2 - strlen($subText)*4, $H-38, $subText, $cDesc);

        return $this->save($img, "market_{$symbol}");
    }

    public function text(string $module, string $title, string $tag, array $rows): ?string
    {
        if (!extension_loaded('gd')) return null;

        $W = 1080; $H = 1350;
        $img = imagecreatetruecolor($W, $H);

        $cDark   = $this->alloc($img, $this->DARK);
        $cCard   = $this->alloc($img, $this->CARD);
        $cBorder = $this->alloc($img, $this->BORDER);
        $cGold   = $this->alloc($img, $this->GOLD);
        $cGoldD  = $this->alloc($img, $this->GOLD_DIM);
        $cWhite  = $this->alloc($img, $this->WHITE);
        $cDesc   = $this->alloc($img, $this->DESC);
        $cLabel  = $this->alloc($img, $this->LABEL);
        $cRed    = $this->alloc($img, $this->RED);
        $cGreen  = $this->alloc($img, $this->GREEN);
        $cTeal   = $this->alloc($img, $this->TEAL);

        $colorMap = [
            'red'   => $cRed,
            'gold'  => $cGold,
            'teal'  => $cTeal,
            'green' => $cGreen,
            'white' => $cWhite,
        ];

        imagefill($img, 0, 0, $cDark);
        imagefilledrectangle($img, 0, 0, $W, 5, $cTeal);
        $this->pasteLogo($img, $W, 38, 130);
        imagefilledrectangle($img, 80, 195, $W-80, 196, $cBorder);

        imagestring($img, 3, $W/2 - strlen($module)*4, 210, "ONIGAMA  .  {$module}", $cGoldD);
        imagestring($img, 5, $W/2 - strlen($title)*5, 248, $title, $cWhite);
        imagefilledrectangle($img, 80, 285, $W-80, 286, $cBorder);

        $y = 300;
        foreach ($rows as $row) {
            $rowColor = $colorMap[$row['color'] ?? 'white'] ?? $cWhite;
            $this->drawCard($img, $y, $row['icon'] ?? '', $row['label'] ?? '',
                           $row['value'] ?? '', $row['desc'] ?? '', $rowColor,
                           $cCard, $cBorder, $cLabel, $cDesc);
            $y += 114;
            if ($y > $H - 160) break;
        }

        imagefilledrectangle($img, 80, $H-90, $W-80, $H-89, $cBorder);
        imagefilledrectangle($img, 0, $H-5, $W, $H, $cGold);
        $footerText = 'ONIGAMA AI BRAIN';
        imagestring($img, 4, $W/2 - strlen($footerText)*5, $H-68, $footerText, $cGoldD);
        $subText = 'bot.onigama.com  #' . $tag;
        imagestring($img, 3, $W/2 - strlen($subText)*4, $H-38, $subText, $cDesc);

        return $this->save($img, "text_{$tag}");
    }

    public function parseAnalysis(string $text): array
    {
        $rows    = [];
        $lines   = explode("\n", trim($text));
        $iconMap = [
            '📊' => ['label' => 'STRUCTURE', 'color' => 'red'],
            '🎯' => ['label' => 'BIAS',      'color' => 'red'],
            '⚡' => ['label' => 'KEY LEVEL', 'color' => 'gold'],
            '💧' => ['label' => 'LIQUIDITY', 'color' => 'teal'],
            '🔔' => ['label' => 'NOTE',      'color' => 'white'],
            '🌐' => ['label' => 'DAILY BIAS','color' => 'gold'],
            '✅' => ['label' => 'VALID',     'color' => 'green'],
            '⚠️' => ['label' => 'RISKS',     'color' => 'red'],
            '💡' => ['label' => 'LESSON',    'color' => 'teal'],
            '🕐' => ['label' => 'SESSION',   'color' => 'teal'],
            '📅' => ['label' => 'EVENTS',    'color' => 'gold'],
            '💥' => ['label' => 'IMPACT',    'color' => 'red'],
            '📋' => ['label' => 'RESULT',    'color' => 'green'],
            '🛡' => ['label' => 'INVALID',   'color' => 'red'],
        ];

        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) continue;
            foreach ($iconMap as $icon => $meta) {
                if (str_starts_with($line, $icon)) {
                    $content = trim(substr($line, mb_strlen($icon)));
                    $content = preg_replace('/^[A-Z\s\/]+:\s*/', '', $content);
                    $parts   = explode(' — ', $content, 2);
                    if (count($parts) === 1) $parts = explode(': ', $content, 2);
                    $rows[] = [
                        'icon'  => $icon,
                        'label' => $meta['label'],
                        'value' => mb_substr(trim($parts[0] ?? $content), 0, 40),
                        'desc'  => mb_substr(trim($parts[1] ?? ''), 0, 60),
                        'color' => $meta['color'],
                    ];
                    break;
                }
            }
        }
        return $rows;
    }

    private function drawCard($img, int $y, string $icon, string $label,
                              string $value, string $desc, $rowColor,
                              $cCard, $cBorder, $cLabel, $cDesc): void
    {
        imagefilledrectangle($img, 60, $y, 1020, $y+104, $cCard);
        imagerectangle($img, 60, $y, 1020, $y+104, $cBorder);
        imagefilledrectangle($img, 60, $y+14, 65, $y+90, $rowColor);
        imagestring($img, 3, 90, $y+14, $label, $cLabel);
        imagestring($img, 4, 90, $y+40, mb_substr($value, 0, 45), $rowColor);
        imagestring($img, 3, 90, $y+70, mb_substr($desc, 0, 62), $cDesc);
    }

    private function pasteLogo($img, int $W, int $y, int $size): void
    {
        if (!file_exists($this->logoPath)) return;
        $logo = @imagecreatefrompng($this->logoPath);
        if (!$logo) return;
        $lw = imagesx($logo);
        $lh = imagesy($logo);
        $newH = $size;
        $newW = (int)($newH * $lw / $lh);
        $x    = ($W - $newW) / 2;
        imagecopyresampled($img, $logo, (int)$x, $y, 0, 0, $newW, $newH, $lw, $lh);
        imagedestroy($logo);
    }

    private function alloc($img, array $rgb)
    {
        return imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
    }

    private function save($img, string $name): string
    {
        $path = $this->outDir . $name . '.jpg';
        imagejpeg($img, $path, 95);
        imagedestroy($img);
        return $path;
    }
}
