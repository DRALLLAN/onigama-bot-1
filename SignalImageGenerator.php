<?php
declare(strict_types=1);

class SignalImageGenerator
{
    private string $tempDir;
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->tempDir = sys_get_temp_dir();
        $this->logger  = $logger;
    }

    /**
     * تولید تصویر سیگنال با طراحی حرفه‌ای
     */
    public function generate(array $signal, array $priceData): ?string
    {
        $isBuy = $signal['signal'] === 'BUY';
        $outputPath = $this->tempDir . '/signal_' . time() . '.png';

        // فراخوانی اسکریپت پایتون
        $signalJson = json_encode($signal, JSON_UNESCAPED_UNICODE);
        $priceJson  = json_encode($priceData, JSON_UNESCAPED_UNICODE);

        $cmd = sprintf(
            "python3 %s/generate_signal_image.py %s %s %s 2>&1",
            escapeshellarg(__DIR__),
            escapeshellarg($signalJson),
            escapeshellarg($priceJson),
            escapeshellarg($outputPath)
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($outputPath)) {
            $this->logger->error("تولید تصویر ناموفق: " . implode("\n", $output));
            return null;
        }

        return $outputPath;
    }
}
