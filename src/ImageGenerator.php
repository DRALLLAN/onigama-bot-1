<?php
declare(strict_types=1);

class ImageGenerator
{
    private string $scriptPath;
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->scriptPath = __DIR__ . '/../generate_image.py';
        $this->logger     = $logger;
    }

    /**
     * تولید تصویر تحلیل بازار
     */
    public function market(string $symbol, array $rows, array $priceData = []): ?string
    {
        $data = [
            'mode'       => 'market',
            'symbol'     => $symbol,
            'price'      => $priceData['price']      ?? '',
            'change'     => $priceData['change']     ?? '',
            'change_pct' => $priceData['change_pct'] ?? '',
            'high'       => $priceData['high']       ?? '',
            'low'        => $priceData['low']        ?? '',
            'rows'       => $rows,
        ];
        return $this->generate($data);
    }

    /**
     * تولید تصویر متنی (سشن، نیوز، ژورنال و غیره)
     */
    public function text(string $module, string $title, string $tag, array $rows): ?string
    {
        $data = [
            'mode'   => 'text',
            'module' => $module,
            'title'  => $title,
            'tag'    => $tag,
            'rows'   => $rows,
        ];
        return $this->generate($data);
    }

    /**
     * تبدیل متن تحلیل AI به آرایه ردیف‌ها
     * خروجی هوش مصنوعی را parse می‌کند
     */
    public function parseAnalysis(string $text): array
    {
        $rows = [];
        $lines = explode("\n", trim($text));

        $iconMap = [
            '📊' => ['label' => 'STRUCTURE', 'color' => 'red'],
            '🎯' => ['label' => 'BIAS',      'color' => 'red'],
            '⚡' => ['label' => 'KEY LEVEL', 'color' => 'gold'],
            '💧' => ['label' => 'LIQUIDITY', 'color' => 'teal'],
            '🔔' => ['label' => 'NOTE',      'color' => 'white'],
            '🌐' => ['label' => 'DAILY BIAS','color' => 'gold'],
            '🛡' => ['label' => 'INVALID',   'color' => 'red'],
            '✅' => ['label' => 'VALID',     'color' => 'green'],
            '⚠️' => ['label' => 'RISKS',     'color' => 'red'],
            '💡' => ['label' => 'LESSON',    'color' => 'teal'],
            '🕐' => ['label' => 'SESSION',   'color' => 'teal'],
            '📅' => ['label' => 'EVENTS',    'color' => 'gold'],
            '💥' => ['label' => 'IMPACT',    'color' => 'red'],
            '📋' => ['label' => 'RESULT',    'color' => 'green'],
        ];

        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) continue;

            foreach ($iconMap as $icon => $meta) {
                if (str_starts_with($line, $icon)) {
                    // جدا کردن مقدار و توضیح با ':'
                    $content = trim(substr($line, mb_strlen($icon)));
                    $content = preg_replace('/^[A-Z\s\/]+:\s*/', '', $content);

                    $parts = explode(' — ', $content, 2);
                    if (count($parts) === 1) {
                        $parts = explode(': ', $content, 2);
                    }

                    $value = trim($parts[0] ?? $content);
                    $desc  = trim($parts[1] ?? '');

                    $rows[] = [
                        'icon'  => $icon,
                        'label' => $meta['label'],
                        'value' => $value,
                        'desc'  => $desc,
                        'color' => $meta['color'],
                    ];
                    break;
                }
            }
        }

        return $rows;
    }

    /**
     * اجرای اسکریپت پایتون
     */
    private function generate(array $data): ?string
    {
        $json = escapeshellarg(json_encode($data, JSON_UNESCAPED_UNICODE));
        $cmd  = "python3 {$this->scriptPath} {$json} 2>&1";

        $output = shell_exec($cmd);
        $path   = trim($output ?? '');

        if (!$path || !file_exists($path)) {
            $this->logger->error("خطای تولید تصویر: $output");
            return null;
        }

        return $path;
    }
}
