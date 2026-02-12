<?php

declare(strict_types=1);

namespace IA;

use RuntimeException;

final class Http
{
    /**
     * @param array<string, string> $headers
     * @param array<string, mixed>|null $jsonBody
     * @return array{status:int, body:string}
     */
    public static function request(string $method, string $url, array $headers = [], ?array $jsonBody = null): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('No se pudo inicializar cURL');
        }

        $httpHeaders = [];
        foreach ($headers as $k => $v) {
            $httpHeaders[] = $k . ': ' . $v;
        }

        if ($jsonBody !== null) {
            $payload = json_encode($jsonBody, JSON_UNESCAPED_UNICODE);
            if ($payload === false) {
                throw new RuntimeException('No se pudo serializar JSON');
            }
            $httpHeaders[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $httpHeaders,
            CURLOPT_TIMEOUT => 25,
        ]);

        $responseBody = curl_exec($ch);
        if ($responseBody === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Error HTTP: ' . $error);
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['status' => $status, 'body' => $responseBody];
    }
}
