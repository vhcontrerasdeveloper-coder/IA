<?php

declare(strict_types=1);

namespace VitaminAssistant;

final class GrokClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly string $endpoint
    ) {
    }

    public function chat(array $messages, float $temperature = 0.25): string
    {
        $payload = [
            'model' => $this->model,
            'temperature' => $temperature,
            'messages' => $messages,
        ];

        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => 45,
        ]);

        $result = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($result === false || $status >= 400) {
            throw new \RuntimeException('Error consultando Grok/xAI: ' . ($error ?: 'HTTP ' . $status));
        }

        $decoded = json_decode($result, true);
        $content = $decoded['choices'][0]['message']['content'] ?? null;

        if (!is_string($content) || trim($content) === '') {
            throw new \RuntimeException('Respuesta vacía de Grok/xAI');
        }

        return trim($content);
    }
}
