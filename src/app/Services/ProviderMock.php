<?php

namespace App\Services;

use Illuminate\Support\Str;

class ProviderMock
{
    /**
     * Заглушка провайдера для отправки уведомлений
     * 90% успешных отправок, 10% ошибок
     */
    public function send(string $channel, string $recipient, string $message): array
    {
        // Имитация задержки сети (100-500 мс)
        usleep(rand(100000, 500000));
        
        // 90% успеха, 10% ошибки
        $success = random_int(1, 100) > 10;
        
        if ($success) {
            return [
                'status' => 'sent',
                'response' => 'Message accepted by provider',
                'provider_message_id' => (string) Str::uuid()
            ];
        } else {
            return [
                'status' => 'bounced',
                'response' => 'Provider error: Invalid recipient or rate limit exceeded',
            ];
        }
    }
}