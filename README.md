# Asistente Chat IA para Shopify (PHP + Grok)

Sí: este backend está preparado para **Grok (xAI)** y ahora responde con foco en **bienestar** usando información real de catálogo.

## ¿Qué hace?

- Expone `POST /chat.php` para preguntas de clientes.
- Toma productos desde Shopify Admin API (GraphQL).
- Usa título, descripción, tags, tipo y colecciones de cada producto.
- Construye recomendaciones orientadas a bienestar.
- Devuelve JSON (`answer`, `products_in_context`).

## 1) Requisitos

- PHP 8.1+
- App en Shopify con Admin API token
- API key de xAI (Grok)

## 2) Configurar variables

```bash
cp .env.example .env
```

Completá:

- `SHOPIFY_STORE_DOMAIN` (ej: `mitienda.myshopify.com`)
- `SHOPIFY_ADMIN_ACCESS_TOKEN`
- `SHOPIFY_API_VERSION` (ej: `2024-10`)
- `SHOPIFY_APP_PROXY_SHARED_SECRET` (opcional, recomendado con App Proxy)
- `GROK_API_KEY`
- `GROK_MODEL` (ej: `grok-2-latest`)
- `GROK_BASE_URL` (default: `https://api.x.ai/v1`)
- `CHAT_MAX_PRODUCTS`

## 3) Levantar backend local

```bash
php -S 0.0.0.0:8080 -t public
```

Endpoint local:

- `POST http://localhost:8080/chat.php`

Body ejemplo:

```json
{
  "question": "Quiero mejorar descanso y estrés, ¿qué me recomendás?"
}
```

## 4) Recomendaciones orientadas a bienestar

El asistente prioriza productos según:

- Colecciones (ej: Relax, Sueño, Autocuidado, Fitness, Aromaterapia)
- Descripción de producto (beneficios y usos declarados)
- Tags y tipo de producto
- Disponibilidad y rango de precio

Importante: el asistente evita inventar beneficios médicos o datos no presentes.

## 5) Integración en Shopify (paso a paso)

### Opción recomendada: App Proxy

1. En tu app de Shopify: **App setup > App proxy**.
2. Configurá:
   - Subpath prefix: `apps`
   - Subpath: `ai-chat`
   - Proxy URL: `https://tu-backend.com/chat.php`
3. Guardá y copiá el shared secret en `SHOPIFY_APP_PROXY_SHARED_SECRET`.
4. En el theme, agregá snippet/bloque con input y botón.
5. Consumí el endpoint con `fetch('/apps/ai-chat', ...)`.

Ejemplo mínimo (Liquid + JS):

```liquid
<div id="ia-chat">
  <input id="ia-q" type="text" placeholder="¿Qué objetivo de bienestar tenés?" />
  <button id="ia-send">Preguntar</button>
  <pre id="ia-out"></pre>
</div>

<script>
  document.getElementById('ia-send').addEventListener('click', async () => {
    const question = document.getElementById('ia-q').value.trim();
    if (!question) return;

    const res = await fetch('/apps/ai-chat', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ question })
    });

    const data = await res.json();
    document.getElementById('ia-out').textContent = data.answer || data.error || 'Sin respuesta';
  });
</script>
```

## 6) Seguridad recomendada

- No exponer `SHOPIFY_ADMIN_ACCESS_TOKEN` ni `GROK_API_KEY` en frontend.
- Mantener validación HMAC de App Proxy en producción.
- Aplicar rate limiting.
- Registrar errores sin secretos.

## 7) Próximas mejoras

- Filtrar catálogo por colección objetivo (bienestar específico)
- Historial por cliente (Redis/DB)
- FAQ semántica
- Métricas de conversión por recomendación
