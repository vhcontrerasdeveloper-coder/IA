<?php

declare(strict_types=1);

namespace VitaminAssistant;

final class KnowledgeBase
{
    private array $knowledge;

    public function __construct(private readonly string $filePath)
    {
        $this->knowledge = $this->load();
    }

    public function suggestBySymptoms(string $message): array
    {
        $messageNorm = mb_strtolower($message);
        $matches = [];

        foreach ($this->knowledge['symptom_protocols'] ?? [] as $protocol) {
            $score = 0;
            foreach ($protocol['keywords'] ?? [] as $keyword) {
                if (str_contains($messageNorm, mb_strtolower((string) $keyword))) {
                    $score++;
                }
            }

            if ($score > 0) {
                $protocol['score'] = $score;
                $matches[] = $protocol;
            }
        }

        usort($matches, static fn(array $a, array $b): int => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

        return array_slice($matches, 0, 3);
    }

    public function getGeneralRules(): array
    {
        return $this->knowledge['assistant_rules'] ?? [];
    }

    private function load(): array
    {
        if (!is_readable($this->filePath)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($this->filePath), true);
        return is_array($decoded) ? $decoded : [];
    }
}
