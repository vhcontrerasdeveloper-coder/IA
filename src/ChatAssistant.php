<?php

declare(strict_types=1);

namespace IA;

final class ChatAssistant
{
    /**
     * @param array<int, array<string, mixed>> $products
     */
    public function buildSystemPrompt(array $products): string
    {
        $catalogContext = $this->buildCatalogContext($products);

        return <<<PROMPT
Sos un asistente de ventas para una tienda Shopify, orientado a bienestar.
Tu tarea es recomendar productos según objetivos de bienestar del cliente (ej: descanso, energía, relajación, cuidado personal).
Reglas:
- Responder siempre en español claro y breve.
- Basarte en colecciones, descripción y metadatos de productos del catálogo provisto.
- No inventar stock, precio, ingredientes, materiales o beneficios clínicos si no están explícitos.
- Si faltan datos, decilo explícitamente y ofrecé alternativas.
- Sugerí 2 a 4 productos relevantes cuando sea posible y explicá por qué encajan con el objetivo.
- Cerrá con un próximo paso concreto (ver producto, revisar variantes, agregar al carrito).

Catálogo disponible (resumen):
{$catalogContext}
PROMPT;
    }

    /**
     * @param array<int, array<string, mixed>> $products
     */
    private function buildCatalogContext(array $products): string
    {
        if ($products === []) {
            return '- Sin productos cargados en contexto.';
        }

        $lines = [];
        foreach ($products as $p) {
            $title = (string) ($p['title'] ?? 'Sin título');
            $handle = (string) ($p['handle'] ?? '');
            $inventory = $p['totalInventory'] ?? 'N/D';
            $description = $this->shorten((string) ($p['description'] ?? ''), 180);
            $productType = (string) ($p['productType'] ?? 'N/D');

            $min = $p['priceRangeV2']['minVariantPrice']['amount'] ?? null;
            $max = $p['priceRangeV2']['maxVariantPrice']['amount'] ?? null;
            $currency = $p['priceRangeV2']['minVariantPrice']['currencyCode'] ?? '';

            $priceText = 'precio no disponible';
            if ($min !== null && $max !== null) {
                $priceText = sprintf('%s %s - %s %s', $min, $currency, $max, $currency);
            }

            $collections = $this->extractCollectionNames($p);
            $collectionText = $collections === [] ? 'sin colección visible' : implode(', ', $collections);

            $tags = $p['tags'] ?? [];
            $tagText = is_array($tags) && $tags !== [] ? implode(', ', array_map('strval', $tags)) : 'sin tags';

            $lines[] = sprintf(
                '- %s (/%s) | tipo: %s | colecciones: %s | stock: %s | precio: %s | tags: %s | descripción: %s',
                $title,
                $handle,
                $productType,
                $collectionText,
                (string) $inventory,
                $priceText,
                $tagText,
                $description !== '' ? $description : 'sin descripción'
            );
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<string, mixed> $product
     * @return array<int, string>
     */
    private function extractCollectionNames(array $product): array
    {
        $nodes = $product['collections']['nodes'] ?? [];
        if (!is_array($nodes)) {
            return [];
        }

        $names = [];
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }

            $title = trim((string) ($node['title'] ?? ''));
            if ($title !== '') {
                $names[] = $title;
            }
        }

        return $names;
    }

    private function shorten(string $text, int $limit): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
        if ($clean === '' || mb_strlen($clean) <= $limit) {
            return $clean;
        }

        return rtrim(mb_substr($clean, 0, $limit - 1)) . '…';
    }
}
