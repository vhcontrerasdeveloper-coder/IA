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
Sos un asistente de ventas para una tienda Shopify.
Tu tarea es ayudar al cliente a encontrar productos y resolver dudas de compra.
Reglas:
- Responder siempre en español claro y breve.
- No inventar stock o precios si no están en el catálogo provisto.
- Si faltan datos, decilo explícitamente.
- Proponer próximos pasos concretos (ver producto, variantes, checkout).

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

            $min = $p['priceRangeV2']['minVariantPrice']['amount'] ?? null;
            $max = $p['priceRangeV2']['maxVariantPrice']['amount'] ?? null;
            $currency = $p['priceRangeV2']['minVariantPrice']['currencyCode'] ?? '';

            $priceText = 'precio no disponible';
            if ($min !== null && $max !== null) {
                $priceText = sprintf('%s %s - %s %s', $min, $currency, $max, $currency);
            }

            $lines[] = sprintf(
                '- %s (/%s) | stock total: %s | rango: %s',
                $title,
                $handle,
                (string) $inventory,
                $priceText
            );
        }

        return implode("\n", $lines);
    }
}
