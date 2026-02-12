# Deploy en `public_html` (cPanel/shared hosting)

Esta guía te permite subir el asistente PHP a `public_html` **sin exponer secretos**.

## Estructura recomendada

```text
/home/USUARIO/
  ia-shopify/                 # código privado (fuera de web root)
    src/
    public/
      chat.php
    .env
  public_html/
    ia-chat/                  # carpeta pública
      chat.php -> symlink o copia desde ia-shopify/public/chat.php
```

> Si no podés usar symlink en tu hosting, copiá `public/chat.php` dentro de `public_html/ia-chat/chat.php`.

## 1) Subir archivos

1. Subí todo el repo a una carpeta privada, por ejemplo `/home/USUARIO/ia-shopify`.
2. Publicá solo el endpoint `chat.php` en `public_html/ia-chat/chat.php`.

## 2) Crear `.env` fuera de `public_html`

Crear: `/home/USUARIO/ia-shopify/.env`

Ejemplo:

```dotenv
SHOPIFY_STORE_DOMAIN=mitienda.myshopify.com
SHOPIFY_ADMIN_ACCESS_TOKEN=shpat_xxx
SHOPIFY_API_VERSION=2024-10
SHOPIFY_APP_PROXY_SHARED_SECRET=xxx

GROK_API_KEY=xai-xxx
GROK_MODEL=grok-2-latest
GROK_BASE_URL=https://api.x.ai/v1

CHAT_MAX_PRODUCTS=8
APP_ENV_PATH=/home/USUARIO/ia-shopify/.env
```

## 3) Configurar carga de `.env`

El endpoint ahora soporta:

- `APP_ENV_PATH` (si está definida, usa esa ruta)
- fallback a `../.env`

En hosting compartido conviene fijar `APP_ENV_PATH` en el `.env` o como variable de entorno del panel.

## 4) Ajustar `require_once` si moviste solo `chat.php`

Si `chat.php` quedó en `public_html/ia-chat/chat.php` y `src/` sigue fuera:

```php
require_once '/home/USUARIO/ia-shopify/src/Env.php';
// ... resto de requires absolutos
```

Alternativa mejor: mantener `public/` y `src/` juntos fuera de `public_html` y publicar con symlink/alias.

## 5) Permisos

- Archivos: `644`
- Carpetas: `755`
- `.env`: `600` o `640`

## 6) Probar endpoint

```bash
curl -X POST "https://tudominio.com/ia-chat/chat.php" \
  -H "Content-Type: application/json" \
  -d '{"question":"Quiero algo para relajarme"}'
```

## 7) Shopify App Proxy

En Shopify App:

- Subpath prefix: `apps`
- Subpath: `ai-chat`
- Proxy URL: `https://tudominio.com/ia-chat/chat.php`

Y en el theme:

```js
fetch('/apps/ai-chat', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ question: '¿Qué me recomendás para dormir mejor?' })
})
```

## Seguridad clave

- Nunca subas `.env` dentro de `public_html`.
- Nunca expongas `SHOPIFY_ADMIN_ACCESS_TOKEN` o `GROK_API_KEY` en frontend.
- Activá validación HMAC (`SHOPIFY_APP_PROXY_SHARED_SECRET`) en producción.
