<?php

declare(strict_types=1);

class Memory
{
    private string $storageDir;
    private int    $maxMessages = 10; // حداکثر پیام‌های ذخیره شده برای هر کاربر

    public function __construct()
    {
        $this->storageDir = __DIR__ . '/../storage/';

        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    /**
     * دریافت تاریخچه مکالمه یک کاربر
     */
    public function getHistory(int|string $chatId): array
    {
        $file = $this->filePath($chatId);

        if (!file_exists($file)) {
            return [];
        }

        $data = json_decode(file_get_contents($file), true);
        return $data['messages'] ?? [];
    }

    /**
     * اضافه کردن پیام کاربر به تاریخچه
     */
    public function addUserMessage(int|string $chatId, string $text): void
    {
        $this->addMessage($chatId, 'user', $text);
    }

    /**
     * اضافه کردن پاسخ هوش مصنوعی به تاریخچه
     */
    public function addAssistantMessage(int|string $chatId, string $text): void
    {
        $this->addMessage($chatId, 'assistant', $text);
    }

    /**
     * پاک کردن تاریخچه یک کاربر
     */
    public function clearHistory(int|string $chatId): void
    {
        $file = $this->filePath($chatId);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * ساخت آرایه پیام‌ها برای ارسال به OpenRouter
     * شامل سیستم پرامپت + تاریخچه + پیام جدید
     */
    public function buildMessages(int|string $chatId, string $systemPrompt, string $userMessage): array
    {
        $history = $this->getHistory($chatId);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        // اضافه کردن تاریخچه
        foreach ($history as $msg) {
            $messages[] = $msg;
        }

        // اضافه کردن پیام جدید
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        return $messages;
    }

    // ─── توابع داخلی ──────────────────────────────────────────────────────────

    private function addMessage(int|string $chatId, string $role, string $content): void
    {
        $file = $this->filePath($chatId);

        $data = ['messages' => []];
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true) ?? ['messages' => []];
        }

        $data['messages'][] = [
            'role'    => $role,
            'content' => mb_substr($content, 0, 1000), // حداکثر ۱۰۰۰ کاراکتر برای هر پیام
        ];

        // نگه داشتن فقط آخرین N پیام
        if (count($data['messages']) > $this->maxMessages) {
            $data['messages'] = array_slice($data['messages'], -$this->maxMessages);
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private function filePath(int|string $chatId): string
    {
        return $this->storageDir . 'chat_' . $chatId . '.json';
    }
}
