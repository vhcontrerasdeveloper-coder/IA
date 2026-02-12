# Deploy en `public_html` (sin carpetas fuera)

Sí, se puede desplegar **todo dentro de `public_html`**.

## Estructura sugerida (todo público, con protección)

```text
public_html/
  ia-chat/
    chat.php
    .env
    .htaccess
    src/
      Env.php
      Http.php
      ShopifyClient.php
      GrokClient.php
      ChatAssistant.php
      ShopifyProxyVerifier.php
```

## 1) Subir archivos

Copiá a `public_html/ia-chat/`:

- `public/chat.php` -> `public_html/ia-chat/chat.php`
- carpeta `src/` completa -> `public_html/ia-chat/src/`
- `.env` -> `public_html/ia-chat/.env`
- `.htaccess` (para bloquear `.env`)

## 2) Variables `.env`

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
APP_ENV_PATH=/home/USUARIO/public_html/ia-chat/.env
APP_SRC_PATH=/home/USUARIO/public_html/ia-chat/src
```

> Si no querés rutas absolutas, podés dejar `APP_ENV_PATH` y `APP_SRC_PATH` vacías.
> El endpoint detecta automáticamente `./.env` y `./src` cuando existen.

## 3) Proteger `.env` y archivos sensibles

En `public_html/ia-chat/.htaccess`:

```apache
Options -Indexes

<Files ".env">
  Require all denied
</Files>

<FilesMatch "^(\.env|composer\.(json|lock)|\.git)">
  Require all denied
</FilesMatch>
```

## 4) Probar endpoint

```bash
curl -X POST "https://tudominio.com/ia-chat/chat.php" \
  -H "Content-Type: application/json" \
  -d '{"question":"Quiero algo para dormir mejor"}'
```

## 5) Shopify App Proxy

Configuración sugerida:

- Subpath prefix: `apps`
- Subpath: `ai-chat`
- Proxy URL: `https://tudominio.com/ia-chat/chat.php`

## Nota de seguridad

Este modo funciona, pero es menos seguro que separar código privado fuera de `public_html`.
Si podés, migrá luego a la versión con archivos sensibles fuera del web root.
