<?php

declare(strict_types=1);

class Telegram
{
    private string $token;
    private Logger $logger;
    private string $baseUrl;

    public function __construct(string $token, Logger $logger)
    {
        $this->token   = $token;
        $this->logger  = $logger;
        $this->baseUrl = "https://api.telegram.org/bot{$token}";
    }

    public function sendMessage(int|string $chatId, string $text, string $parseMode = 'Markdown'): bool
    {
        // تلگرام محدودیت ۴۰۹۶ کاراکتر دارد
        if (mb_strlen($text) > 4096) {
            $text = mb_substr($text, 0, 4090) . '...';
        }

        $payload = [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => $parseMode,
        ];

        $result = $this->post('/sendMessage', $payload);

        if (!$result['ok']) {
            // اگر Markdown خطا داد، بدون فرمت دوباره امتحان می‌کنیم
            $this->logger->warning("ارسال Markdown ناموفق، تلاش مجدد بدون فرمت");
            $payload['parse_mode'] = '';
            $result = $this->post('/sendMessage', $payload);
        }

        return $result['ok'] ?? false;
    }

    public function sendTyping(int|string $chatId): void
    {
        $this->post('/sendChatAction', [
            'chat_id' => $chatId,
            'action'  => 'typing',
        ]);
    }

    public function setWebhook(string $url): array
    {
        return $this->post('/setWebhook', [
            'url'             => $url,
            'allowed_updates' => ['message'],
            'drop_pending_updates' => true,
        ]);
    }

    private function post(string $endpoint, array $data): array
    {
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->logger->error("خطای cURL تلگرام: $error");
            return ['ok' => false];
        }

        return json_decode($response, true) ?? ['ok' => false];
    }
}
