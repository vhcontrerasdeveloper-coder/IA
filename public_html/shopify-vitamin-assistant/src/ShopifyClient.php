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
        private readonly int $cacheTtl
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

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0775, true);
        }
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
                'X-Shopify-Access-Token: ' . $this->adminToken,
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

    private function truncate(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $max - 1)) . '…';
    }
}
