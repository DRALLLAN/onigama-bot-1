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

    /**
     * ارسال پیام متنی
     */
    public function sendMessage(int|string $chatId, string $text, string $parseMode = 'Markdown'): bool
    {
        if (mb_strlen($text) > 4096) {
            $text = mb_substr($text, 0, 4090) . '...';
        }

        $payload = [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => $parseMode,
        ];

        $result = $this->post('/sendMessage', $payload);

        if (!($result['ok'] ?? false)) {
            $payload['parse_mode'] = '';
            $result = $this->post('/sendMessage', $payload);
        }

        return $result['ok'] ?? false;
    }

    /**
     * ارسال تصویر با کپشن
     */
    public function sendPhoto(int|string $chatId, string $imagePath, string $caption = ''): bool
    {
        if (!file_exists($imagePath)) {
            $this->logger->error("فایل تصویر یافت نشد: $imagePath");
            return false;
        }

        $ch = curl_init($this->baseUrl . '/sendPhoto');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => [
                'chat_id'    => $chatId,
                'photo'      => new CURLFile($imagePath, 'image/jpeg', 'analysis.jpg'),
                'caption'    => mb_substr($caption, 0, 1024),
                'parse_mode' => 'Markdown',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->logger->error("خطای ارسال تصویر: $error");
            return false;
        }

        $result = json_decode($response, true);

        if (!($result['ok'] ?? false)) {
            $this->logger->error("تلگرام تصویر را رد کرد: " . $response);
            return false;
        }

        // پاک کردن فایل موقت
        @unlink($imagePath);
        return true;
    }

    public function sendTyping(int|string $chatId): void
    {
        $this->post('/sendChatAction', [
            'chat_id' => $chatId,
            'action'  => 'upload_photo',
        ]);
    }

    public function setWebhook(string $url): array
    {
        return $this->post('/setWebhook', [
            'url'                  => $url,
            'allowed_updates'      => ['message'],
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
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
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
