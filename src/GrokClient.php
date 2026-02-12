<?php

declare(strict_types=1);

namespace IA;

use RuntimeException;

final class GrokClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly string $baseUrl
    ) {
    }

    public function reply(string $systemPrompt, string $userMessage): string
    {
        $url = rtrim($this->baseUrl, '/') . '/chat/completions';

        $response = Http::request('POST', $url, [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ], [
            'model' => $this->model,
            'temperature' => 0.3,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage],
            ],
        ]);

        if ($response['status'] >= 400) {
            throw new RuntimeException('Grok/xAI respondió con error HTTP ' . $response['status']);
        }

        $json = json_decode($response['body'], true);
        if (!is_array($json)) {
            throw new RuntimeException('Respuesta inválida de Grok/xAI');
        }

        $content = $json['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || trim($content) === '') {
            throw new RuntimeException('Grok/xAI no devolvió contenido');
        }

        return trim($content);
    }
}
