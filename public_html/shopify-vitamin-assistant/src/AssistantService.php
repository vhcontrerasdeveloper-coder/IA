<?php

declare(strict_types=1);

namespace VitaminAssistant;

final class AssistantService
{
    public function __construct(
        private readonly ShopifyClient $shopify,
        private readonly KnowledgeBase $knowledgeBase,
        private readonly GrokClient $grok,
        private readonly string $assistantName = 'Asistente Salud Natural',
        private readonly string $language = 'es'
    ) {
    }

    public function respond(string $userMessage, array $history = []): array
    {
        $catalog = $this->shopify->getCatalogContext();
        $protocols = $this->knowledgeBase->suggestBySymptoms($userMessage);
        $rules = $this->knowledgeBase->getGeneralRules();

        $messages = [
            [
                'role' => 'system',
                'content' => $this->buildSystemPrompt($catalog, $protocols, $rules),
            ],
        ];

        foreach (array_slice($history, -6) as $item) {
            if (!isset($item['role'], $item['content'])) {
                continue;
            }
            $messages[] = [
                'role' => $item['role'] === 'assistant' ? 'assistant' : 'user',
                'content' => (string) $item['content'],
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];
        $answer = $this->grok->chat($messages);

        return [
            'answer' => $answer,
            'matched_protocols' => $protocols,
            'catalog_generated_at' => $catalog['generated_at'] ?? null,
        ];
    }

    private function buildSystemPrompt(array $catalog, array $protocols, array $rules): string
    {
        $rulesText = implode("\n- ", $rules);
        $protocolsText = json_encode($protocols, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $catalogText = json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
Eres {$this->assistantName}, asesor virtual de una tienda Shopify de vitaminas y salud natural.
Idioma obligatorio: {$this->language}.

Reglas de comportamiento:
- {$rulesText}
- Nunca diagnostiques enfermedades.
- Si hay síntomas de alerta (dolor severo, sangre, dificultad respiratoria, embarazo de riesgo, fiebre alta persistente), recomienda consultar profesional de salud inmediatamente.
- Recomienda productos SOLO si aparecen en el catálogo entregado.
- Cuando sugieras producto, incluye: nombre, por qué ayuda, y enlace URL si existe.
- Usa tono cercano, profesional y claro.

Protocolos detectados por síntomas:
{$protocolsText}

Catálogo y colecciones de Shopify (fuente de verdad):
{$catalogText}
PROMPT;
    }
}
