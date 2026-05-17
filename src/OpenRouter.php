<?php
declare(strict_types=1);
class OpenRouter
{
    private string $apiKey;
    private Logger $logger;
    private string $endpoint = 'https://openrouter.ai/api/v1/chat/completions';

    public function __construct(string $apiKey, Logger $logger)
    {
        $this->apiKey = $apiKey;
        $this->logger = $logger;
    }

    /**
     * ارسال پیام با تاریخچه مکالمه
     */
    public function chatWithHistory(array $messages, int $maxTokens = 450): string
    {
        $payload = [
            'model'      => AI_MODEL,
            'max_tokens' => $maxTokens,
            'messages'   => $messages,
        ];

        return $this->send($payload);
    }

    /**
     * ارسال پیام ساده بدون تاریخچه
     */
    public function chat(string $systemPrompt, string $userMessage, int $maxTokens = 450): string
    {
        $payload = [
            'model'      => AI_MODEL,
            'max_tokens' => $maxTokens,
            'messages'   => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userMessage],
            ],
        ];

        return $this->send($payload);
    }

    private function send(array $payload): string
    {
        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
                'HTTP-Referer: https://onigamafx.com',
                'X-Title: Onigama AI Brain',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->logger->error("خطای cURL: $error");
            return '⚠️ خطا در اتصال به سرویس هوش مصنوعی.';
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200 || empty($data['choices'][0]['message']['content'])) {
            $this->logger->error("پاسخ نامعتبر: HTTP $httpCode — " . $response);
            return '⚠️ پاسخی دریافت نشد. لطفاً دوباره تلاش کن.';
        }

        return trim($data['choices'][0]['message']['content']);
    }
}
