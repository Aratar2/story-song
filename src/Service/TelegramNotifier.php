<?php

declare(strict_types=1);

namespace App\Service;

class TelegramNotifier
{
    private ?string $token;
    private ?string $chatId;
    private ?string $lastError = null;

    public function __construct(?string $token, ?string $chatId)
    {
        $this->token = $token !== '' ? $token : null;
        $this->chatId = $chatId !== '' ? $chatId : null;
    }

    /**
     * @param array<int, string> $lines
     */
    public function sendMessage(array $lines): bool
    {
        $this->lastError = null;

        if ($this->token === null || $this->chatId === null) {
            $this->lastError = 'TELEGRAM_BOT_TOKEN или TELEGRAM_CHAT_ID не заданы.';
            error_log($this->lastError);

            return false;
        }

        $payload = [
            'chat_id' => $this->chatId,
            'text' => implode("\n", $lines),
            'disable_web_page_preview' => true,
        ];

        $endpoint = sprintf('https://api.telegram.org/bot%s/sendMessage', $this->token);
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
        ]);

        $response = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $this->lastError = 'Не удалось выполнить запрос к Telegram: ' . curl_error($ch);
            error_log($this->lastError);
            curl_close($ch);

            return false;
        }

        curl_close($ch);

        $responseData = json_decode($response, true);
        $telegramOk = is_array($responseData) && ($responseData['ok'] ?? false) === true;

        if ($httpStatus >= 400 || !$telegramOk) {
            if (isset($responseData['description'])) {
                $this->lastError = 'Ошибка Telegram API: ' . $responseData['description'];
            } else {
                $this->lastError = 'Не получилось отправить заявку через Telegram API.';
            }

            if ($this->lastError !== null) {
                error_log($this->lastError);
            }

            return false;
        }

        return true;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }
}
