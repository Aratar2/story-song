<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\SongRequest;

class SongRequestFormatter
{
    /**
     * @return list<string>
     */
    public function buildTelegramMessage(SongRequest $request): array
    {
        $lines = [
            'Новая заявка на песню',
            'Имя: ' . ($request->getName() !== null && $request->getName() !== '' ? $request->getName() : 'не указано'),
            'Контакт: ' . $request->getContact(),
            'Повод: ' . ($request->getOccasion() !== null && $request->getOccasion() !== '' ? $request->getOccasion() : 'не указан'),
            'Настроение: ' . ($request->getTone() !== null && $request->getTone() !== '' ? $request->getTone() : 'не указано'),
        ];

        $story = $request->getStory();
        $wantsStoryLater = $request->shouldTellStoryLater();

        if ($story !== null && $story !== '') {
            $lines[] = 'История: ' . $story;
            if ($wantsStoryLater) {
                $lines[] = 'Комментарий: клиент хочет дополнительно рассказать историю голосовым сообщением.';
            }
        } else {
            $lines[] = 'История: клиент расскажет голосовым сообщением в мессенджере.';
        }

        $lines[] = 'Создана: ' . $request->getCreatedAt()->format('d.m.Y H:i');

        return $lines;
    }
}
