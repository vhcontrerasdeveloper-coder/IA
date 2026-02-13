<?php

declare(strict_types=1);

use VitaminAssistant\AssistantService;
use VitaminAssistant\Env;
use VitaminAssistant\GrokClient;
use VitaminAssistant\Http;
use VitaminAssistant\KnowledgeBase;
use VitaminAssistant\ShopifyClient;

require_once dirname(__DIR__) . '/bootstrap.php';

$allowedOrigins = array_filter(array_map('trim', explode(',', Env::get('APP_ALLOWED_ORIGINS', '') ?? '')));
Http::setupCors($allowedOrigins);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Http::jsonResponse(['error' => 'Método no permitido'], 405);
    exit;
}

$apiKey = Env::get('APP_API_KEY', '');
$receivedKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($apiKey === '' || !hash_equals($apiKey, $receivedKey)) {
    Http::jsonResponse(['error' => 'No autorizado'], 401);
    exit;
}

$input = Http::getJsonInput();
$message = trim((string) ($input['message'] ?? ''));
$history = is_array($input['history'] ?? null) ? $input['history'] : [];

if ($message === '') {
    Http::jsonResponse(['error' => 'El campo message es obligatorio'], 422);
    exit;
}

try {
    $shopify = new ShopifyClient(
        Env::get('SHOPIFY_STORE_DOMAIN', '') ?? '',
        Env::get('SHOPIFY_ADMIN_TOKEN', '') ?? '',
        Env::get('SHOPIFY_API_VERSION', '2026-01') ?? '2026-01',
        dirname(__DIR__) . '/cache',
        (int) (Env::get('CATALOG_CACHE_TTL', '900') ?? 900)
    );

    $knowledge = new KnowledgeBase(dirname(__DIR__) . '/data/knowledge_base.json');
    $grok = new GrokClient(
        Env::get('GROK_API_KEY', '') ?? '',
        Env::get('GROK_MODEL', 'grok-3-latest') ?? 'grok-3-latest',
        Env::get('GROK_API_URL', 'https://api.x.ai/v1/chat/completions') ?? 'https://api.x.ai/v1/chat/completions'
    );

    $assistant = new AssistantService(
        $shopify,
        $knowledge,
        $grok,
        Env::get('ASSISTANT_NAME', 'Asistente Salud Natural') ?? 'Asistente Salud Natural',
        Env::get('ASSISTANT_LANGUAGE', 'es') ?? 'es'
    );

    $response = $assistant->respond($message, $history);
    Http::jsonResponse(['ok' => true] + $response);
} catch (Throwable $e) {
    Http::jsonResponse([
        'ok' => false,
        'error' => 'No fue posible procesar la consulta',
        'details' => Env::get('APP_ENV', 'production') === 'production' ? null : $e->getMessage(),
    ], 500);
}
