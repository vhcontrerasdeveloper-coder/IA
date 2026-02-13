<?php

declare(strict_types=1);

namespace VitaminAssistant;

final class ShopifyClient
{
    public function __construct(
        private readonly string $storeDomain,
        private readonly string $adminToken,
        private readonly string $apiVersion,
        private readonly string $cacheDir,
        private readonly int $cacheTtl,
        private readonly ?string $clientId = null,
        private readonly ?string $clientSecret = null,
        private readonly ?string $refreshToken = null,
        private readonly ?string $oauthTokenUrl = null
    ) {
    }

    public function getCatalogContext(int $maxProducts = 20, int $maxCollections = 10): array
    {
        $cacheFile = $this->cacheDir . '/catalog_' . md5($this->storeDomain) . '.json';
        if (is_readable($cacheFile) && (time() - filemtime($cacheFile)) < $this->cacheTtl) {
            $cached = json_decode((string) file_get_contents($cacheFile), true);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $query = <<<'GQL'
        {
          products(first: 60, sortKey: BEST_SELLING) {
            edges {
              node {
                id
                title
                handle
                description
                tags
                productType
                onlineStoreUrl
                variants(first: 2) {
                  edges {
                    node {
                      title
                      price
                    }
                  }
                }
              }
            }
          }
          collections(first: 30, sortKey: UPDATED_AT) {
            edges {
              node {
                id
                title
                handle
                description
              }
            }
          }
        }
        GQL;

        $response = $this->graphql($query);
        $products = [];
        $collections = [];

        foreach (($response['data']['products']['edges'] ?? []) as $edge) {
            $node = $edge['node'] ?? [];
            $variant = $node['variants']['edges'][0]['node'] ?? null;
            $products[] = [
                'title' => $node['title'] ?? '',
                'handle' => $node['handle'] ?? '',
                'description' => $this->truncate(strip_tags((string) ($node['description'] ?? '')), 420),
                'tags' => $node['tags'] ?? [],
                'productType' => $node['productType'] ?? '',
                'url' => $node['onlineStoreUrl'] ?? '',
                'price' => $variant['price'] ?? null,
            ];
        }

        foreach (($response['data']['collections']['edges'] ?? []) as $edge) {
            $node = $edge['node'] ?? [];
            $collections[] = [
                'title' => $node['title'] ?? '',
                'handle' => $node['handle'] ?? '',
                'description' => $this->truncate(strip_tags((string) ($node['description'] ?? '')), 320),
            ];
        }

        $catalog = [
            'products' => array_slice($products, 0, $maxProducts),
            'collections' => array_slice($collections, 0, $maxCollections),
            'generated_at' => date(DATE_ATOM),
        ];

        $this->ensureCacheDir();
        file_put_contents($cacheFile, json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $catalog;
    }

    private function graphql(string $query): array
    {
        $url = sprintf('https://%s/admin/api/%s/graphql.json', $this->storeDomain, $this->apiVersion);
        $payload = json_encode(['query' => $query], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Shopify-Access-Token: ' . $this->resolveAccessToken(),
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 25,
        ]);

        $result = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($result === false || $status >= 400) {
            throw new \RuntimeException('No se pudo consultar Shopify: ' . ($error ?: 'HTTP ' . $status));
        }

        $decoded = json_decode($result, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Respuesta inválida de Shopify');
        }

        return $decoded;
    }

    private function resolveAccessToken(): string
    {
        if ($this->adminToken !== '') {
            return $this->adminToken;
        }

        $cached = $this->readCachedOAuthToken();
        if ($cached !== null) {
            return $cached;
        }

        return $this->refreshOAuthAccessToken();
    }

    private function readCachedOAuthToken(): ?string
    {
        $cacheFile = $this->cacheDir . '/shopify_oauth_token.json';
        if (!is_readable($cacheFile)) {
            return null;
        }

        $cached = json_decode((string) file_get_contents($cacheFile), true);
        if (!is_array($cached)) {
            return null;
        }

        $token = (string) ($cached['access_token'] ?? '');
        $expiresAt = (int) ($cached['expires_at'] ?? 0);

        if ($token === '' || $expiresAt <= (time() + 45)) {
            return null;
        }

        return $token;
    }

    private function refreshOAuthAccessToken(): string
    {
        if ($this->clientId === null || $this->clientSecret === null || $this->refreshToken === null) {
            throw new \RuntimeException('Falta SHOPIFY_ADMIN_TOKEN o credenciales OAuth (SHOPIFY_CLIENT_ID, SHOPIFY_CLIENT_SECRET, SHOPIFY_REFRESH_TOKEN).');
        }

        $endpoint = $this->oauthTokenUrl;
        if ($endpoint === null || trim($endpoint) === '') {
            throw new \RuntimeException('Falta SHOPIFY_OAUTH_TOKEN_URL para renovar access token OAuth.');
        }

        $payload = http_build_query([
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $this->refreshToken,
        ]);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 25,
        ]);

        $result = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($result === false || $status >= 400) {
            throw new \RuntimeException('No se pudo renovar token OAuth de Shopify: ' . ($error ?: 'HTTP ' . $status));
        }

        $decoded = json_decode($result, true);
        if (!is_array($decoded) || !is_string($decoded['access_token'] ?? null)) {
            throw new \RuntimeException('Respuesta inválida al renovar token OAuth de Shopify.');
        }

        $accessToken = trim((string) $decoded['access_token']);
        if ($accessToken === '') {
            throw new \RuntimeException('Shopify devolvió access_token vacío.');
        }

        $expiresIn = (int) ($decoded['expires_in'] ?? 3600);
        $this->ensureCacheDir();
        file_put_contents(
            $this->cacheDir . '/shopify_oauth_token.json',
            json_encode([
                'access_token' => $accessToken,
                'expires_at' => time() + max(300, $expiresIn - 60),
                'updated_at' => date(DATE_ATOM),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return $accessToken;
    }

    private function ensureCacheDir(): void
    {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0775, true);
        }
    }

    private function truncate(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $max - 1)) . '…';
    }
}
