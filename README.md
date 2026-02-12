# Asistente Chat IA para Shopify (PHP + Grok)

Sí: este backend ya quedó preparado para **Grok (xAI)** en lugar de OpenAI.

## ¿Qué hace?

- Expone `POST /chat.php` para recibir preguntas de clientes.
- Toma productos recientes desde Shopify Admin API (GraphQL).
- Arma contexto de catálogo.
- Genera respuesta con Grok/xAI.
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
- `SHOPIFY_APP_PROXY_SHARED_SECRET` (opcional, pero recomendado con App Proxy)
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

Body:

```json
{
  "question": "Busco zapatillas negras, cómodas y livianas"
}
```

## 4) ¿Cómo lo integro en Shopify? (paso a paso)

### Opción recomendada: App Proxy

1. En tu app de Shopify, abrí **App setup > App proxy**.
2. Configurá por ejemplo:
   - Subpath prefix: `apps`
   - Subpath: `ai-chat`
   - Proxy URL: `https://tu-backend.com/chat.php`
3. Guardá y copiá el **shared secret** a `.env` en `SHOPIFY_APP_PROXY_SHARED_SECRET`.
4. En tu theme, agregá un bloque/snippet con un input + botón para consultar.
5. Hacé `fetch('/apps/ai-chat', { method: 'POST', ... })`.

Ejemplo mínimo para theme (Liquid + JS):

```liquid
<div id="ia-chat">
  <input id="ia-q" type="text" placeholder="¿Qué estás buscando?" />
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

### Opción alternativa: endpoint directo

Si no usás App Proxy, podés llamar al backend directo (ej. Cloudflare/Nginx), pero tené en cuenta CORS, seguridad y rate limiting.

## 5) Seguridad recomendada

- No exponer jamás `SHOPIFY_ADMIN_ACCESS_TOKEN` ni `GROK_API_KEY` al frontend.
- Mantener validación de firma App Proxy habilitada en producción.
- Aplicar rate limiting (edge o servidor).
- Loggear errores sin filtrar secretos.

## 6) Próximas mejoras

- Historial por cliente (Redis/DB)
- Reglas de negocio (envíos/cambios)
- FAQ + búsqueda semántica
- Tracking de métricas de conversación
