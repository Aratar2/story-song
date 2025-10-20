<?php

declare(strict_types=1);

namespace App\Service;

class LandingContentRepository
{
    /** @var array<string, mixed> */
    private array $config;

    private string $projectRoot;

    public function __construct(array $config, string $projectRoot)
    {
        $this->config = $config;
        $this->projectRoot = rtrim($projectRoot, '/');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getStories(): array
    {
        $stories = $this->config['stories'] ?? [];
        if (!is_array($stories)) {
            return [];
        }

        $normalized = [];
        foreach ($stories as $story) {
            if (!is_array($story)) {
                continue;
            }

            $audioFile = isset($story['audioFile']) && is_string($story['audioFile']) ? $story['audioFile'] : null;

            $audioExists = false;
            if ($audioFile !== null) {
                $normalizedAudioFile = ltrim($audioFile, '/');
                $candidatePaths = [
                    $this->projectRoot . '/' . $normalizedAudioFile,
                    $this->projectRoot . '/public/' . $normalizedAudioFile,
                ];

                foreach ($candidatePaths as $candidatePath) {
                    if (is_file($candidatePath)) {
                        $audioExists = true;
                        break;
                    }
                }
            }
            $mimeType = 'audio/mpeg';

            if ($audioFile !== null) {
                $extension = strtolower(pathinfo($audioFile, PATHINFO_EXTENSION));
                if ($extension === 'wav') {
                    $mimeType = 'audio/wav';
                } elseif ($extension === 'ogg') {
                    $mimeType = 'audio/ogg';
                }
            }

            $tags = $story['tags'] ?? [];
            if (!is_array($tags)) {
                $tags = [];
            }

            $normalized[] = [
                'title' => isset($story['title']) ? (string) $story['title'] : '',
                'description' => isset($story['description']) ? (string) $story['description'] : '',
                'audioFile' => $audioFile,
                'tags' => array_values(array_map(static fn($tag) => (string) $tag, $tags)),
                'audioExists' => $audioExists,
                'audioMimeType' => $mimeType,
            ];
        }

        return $normalized;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getSteps(): array
    {
        $steps = $this->config['steps'] ?? [];
        if (!is_array($steps)) {
            return [];
        }

        $normalized = [];
        foreach ($steps as $step) {
            if (!is_array($step)) {
                continue;
            }

            $normalized[] = [
                'title' => (string) ($step['title'] ?? ''),
                'text' => (string) ($step['text'] ?? ''),
            ];
        }

        return $normalized;
    }
}
