<?php

declare(strict_types=1);

use IA\ChatAssistant;
use IA\Env;
use IA\GrokClient;
use IA\ShopifyClient;
use IA\ShopifyProxyVerifier;

require_once __DIR__ . '/../src/Env.php';
require_once __DIR__ . '/../src/Http.php';
require_once __DIR__ . '/../src/ShopifyClient.php';
require_once __DIR__ . '/../src/GrokClient.php';
require_once __DIR__ . '/../src/ChatAssistant.php';
require_once __DIR__ . '/../src/ShopifyProxyVerifier.php';

header('Content-Type: application/json; charset=utf-8');

$envPath = Env::get('APP_ENV_PATH', __DIR__ . '/../.env');
Env::load($envPath ?? (__DIR__ . '/../.env'));

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$sharedSecret = Env::get('SHOPIFY_APP_PROXY_SHARED_SECRET', '');
if ($sharedSecret !== '') {
    $query = [];
    foreach ($_GET as $k => $v) {
        $query[(string) $k] = is_array($v) ? '' : (string) $v;
    }

    if (!ShopifyProxyVerifier::isValid($query, $sharedSecret)) {
        http_response_code(401);
        echo json_encode(['error' => 'Firma de Shopify inválida']);
        exit;
    }
}

$raw = file_get_contents('php://input');
$input = json_decode($raw ?: '{}', true);
$question = is_array($input) ? trim((string) ($input['question'] ?? '')) : '';

if ($question === '') {
    http_response_code(422);
    echo json_encode(['error' => 'El campo question es requerido']);
    exit;
}

$storeDomain = Env::get('SHOPIFY_STORE_DOMAIN');
$shopifyToken = Env::get('SHOPIFY_ADMIN_ACCESS_TOKEN');
$shopifyApiVersion = Env::get('SHOPIFY_API_VERSION', '2024-10');
$grokApiKey = Env::get('GROK_API_KEY');
$grokModel = Env::get('GROK_MODEL', 'grok-2-latest');
$grokBaseUrl = Env::get('GROK_BASE_URL', 'https://api.x.ai/v1');
$maxProducts = (int) (Env::get('CHAT_MAX_PRODUCTS', '8') ?? '8');

if (!$storeDomain || !$shopifyToken || !$grokApiKey) {
    http_response_code(500);
    echo json_encode(['error' => 'Faltan variables de entorno obligatorias']);
    exit;
}

try {
    $shopify = new ShopifyClient($storeDomain, $shopifyToken, $shopifyApiVersion);
    $products = $shopify->getTopProducts($maxProducts > 0 ? $maxProducts : 8);

    $assistant = new ChatAssistant();
    $systemPrompt = $assistant->buildSystemPrompt($products);

    $grok = new GrokClient($grokApiKey, $grokModel, $grokBaseUrl);
    $answer = $grok->reply($systemPrompt, $question);

    echo json_encode([
        'answer' => $answer,
        'products_in_context' => count($products),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'No se pudo generar respuesta',
        'detail' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
