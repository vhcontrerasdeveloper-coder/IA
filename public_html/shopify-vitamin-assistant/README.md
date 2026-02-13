# Float Chat Asistente IA para Shopify (2026) - PHP

Implementación lista para subir a `public_html`, pensada para una tienda de vitaminas/salud natural.

## Qué hace
- Renderiza un **chat flotante** en el storefront de Shopify.
- Consulta **Grok (xAI API)** para responder en español.
- Extrae catálogo de Shopify (productos y colecciones) vía **Admin GraphQL API 2026-01**.
- Cruza síntomas con una base de conocimiento local (`data/knowledge_base.json`) para mejorar recomendaciones.

## Requisitos
- PHP `8.1+` con extensión `curl` y `mbstring`.
- Una custom app en Shopify con permisos de lectura de productos y colecciones.
- Claves:
  - `GROK_API_KEY`
  - `APP_API_KEY`
  - Shopify: **o** `SHOPIFY_ADMIN_TOKEN` **o** combo OAuth (`SHOPIFY_CLIENT_ID`, `SHOPIFY_CLIENT_SECRET`, `SHOPIFY_REFRESH_TOKEN`, `SHOPIFY_OAUTH_TOKEN_URL`)

## Estructura
- `api/chat.php`: endpoint JSON para el widget.
- `assets/widget.js`: interfaz flotante y cliente web.
- `src/*`: servicios PHP (Shopify, KB, Grok, asistente).
- `data/knowledge_base.json`: reglas y protocolos por síntomas.

## Instalación
1. Copia carpeta completa a:
   ```
   public_html/shopify-vitamin-assistant
   ```
2. Duplica `.env.example` a `.env` y configura valores reales.
3. Verifica permisos de escritura en `cache/`.

## Integración en Shopify (Tema Online Store 2.0)
En `theme.liquid` (antes de `</body>`):

```liquid
<script>
  window.VITAMIN_CHAT_CONFIG = {
    endpoint: 'https://TU-DOMINIO.com/shopify-vitamin-assistant/api/chat.php',
    apiKey: 'TU_APP_API_KEY',
    title: 'Asistente Salud Natural',
    buttonText: 'Asesor de Salud'
  };
</script>
<script src="https://TU-DOMINIO.com/shopify-vitamin-assistant/assets/widget.js" defer></script>
```


## Autenticación Shopify (versión nueva y versión clásica)
Este proyecto ahora soporta **dos formas**:

1. **Token fijo Admin API** (`SHOPIFY_ADMIN_TOKEN`):
   - Si tienes un token Admin API ya emitido (por ejemplo en flujos clásicos), colócalo directo.
2. **OAuth con refresh token** (Dev Dashboard nuevo):
   - Si tu panel solo muestra `Client ID`, `Client Secret (shpss_...)` y `Refresh token`, configura:
     - `SHOPIFY_CLIENT_ID`
     - `SHOPIFY_CLIENT_SECRET`
     - `SHOPIFY_REFRESH_TOKEN`
     - `SHOPIFY_OAUTH_TOKEN_URL`
   - El backend renovará automáticamente el access token y lo cacheará en `cache/shopify_oauth_token.json`.

> Importante: `Client ID`, `Client Secret` y `Refresh token` **no** van en `SHOPIFY_ADMIN_TOKEN`.

### Ejemplo `.env` para Shopify nuevo (Dev Dashboard)
```env
SHOPIFY_STORE_DOMAIN=tu-tienda.myshopify.com
SHOPIFY_API_VERSION=2026-01
SHOPIFY_ADMIN_TOKEN=
SHOPIFY_CLIENT_ID=xxxxxxxx
SHOPIFY_CLIENT_SECRET=shpss_xxxxxxxx
SHOPIFY_REFRESH_TOKEN=xxxxxxxx
SHOPIFY_OAUTH_TOKEN_URL=https://.../oauth/token
```

Si no sabes qué URL poner en `SHOPIFY_OAUTH_TOKEN_URL`, revisa la documentación de tu app en el panel de Shopify donde obtuviste `refresh token`.

## Endpoint
`POST /shopify-vitamin-assistant/api/chat.php`

Headers:
- `Content-Type: application/json`
- `X-Api-Key: <APP_API_KEY>`

Body:
```json
{
  "message": "tengo cansancio y me cuesta dormir",
  "history": [
    {"role":"user","content":"hola"},
    {"role":"assistant","content":"hola ¿cómo te ayudo?"}
  ]
}
```

## Seguridad recomendada
- Restringe `APP_ALLOWED_ORIGINS` en `.env`.
- Mantén `.env` fuera de acceso público (regla de servidor si aplica).
- Regenera `APP_API_KEY` periódicamente.

## Personalización
- Ajusta protocolos de síntomas en `data/knowledge_base.json`.
- Ajusta prompt y límites en `src/AssistantService.php` y `.env`.
