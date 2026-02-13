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
  - `SHOPIFY_ADMIN_TOKEN`
  - `GROK_API_KEY`
  - `APP_API_KEY`

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


## ¿Qué token usar exactamente en `SHOPIFY_ADMIN_TOKEN`?
- Debes usar **Admin API access token** de la Custom App (normalmente inicia con `shpat_`).
- **No** uses `Client ID` ni `Client Secret` (`shpss_...`) en ese campo.

Si en tu app solo ves `Client ID` y `Secret`, te falta instalar la app o abrir la sección correcta:
1. Shopify Admin → **Settings** → **Apps and sales channels** → **Develop apps**.
2. Abre tu app personalizada.
3. En **Configuration**, agrega scopes Admin API (mínimo `read_products` y `read_collections`).
4. Pulsa **Install app** (o **Reinstall app** si cambiaste scopes).
5. Ve a **API credentials** → **Admin API access token** → **Reveal token once**.
6. Copia ese valor `shpat_...` a `.env` en `SHOPIFY_ADMIN_TOKEN`.

> Con los datos que compartiste (`Client ID` + `Secret shpss_...`), todavía **no** es el token que este script necesita para consultar Admin GraphQL.

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
