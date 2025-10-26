<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\SongRequest;
use Doctrine\ORM\EntityManagerInterface;

class SongRequestService
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param array{ name?: string, contact?: string, occasion?: string, story?: string, tone?: string, story_later?: string } $formData
     */
    public function createFromFormData(array $formData): SongRequest
    {
        $request = new SongRequest(
            $this->normalizeValue($formData['name'] ?? ''),
            (string) ($formData['contact'] ?? ''),
            $this->normalizeValue($formData['occasion'] ?? ''),
            $this->normalizeValue($formData['tone'] ?? ''),
            $this->normalizeValue($formData['story'] ?? ''),
            ($formData['story_later'] ?? '0') === '1'
        );

        $this->entityManager->persist($request);
        $this->entityManager->flush();

        return $request;
    }

    private function normalizeValue(?string $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        return $trimmed;
    }
}
