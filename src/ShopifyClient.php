<?php

declare(strict_types=1);

namespace IA;

use RuntimeException;

final class ShopifyClient
{
    public function __construct(
        private readonly string $storeDomain,
        private readonly string $adminAccessToken,
        private readonly string $apiVersion
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopProducts(int $limit = 8): array
    {
        $query = <<<'GQL'
query TopProducts($first: Int!) {
  products(first: $first, sortKey: UPDATED_AT, reverse: true) {
    edges {
      node {
        id
        title
        handle
        description
        tags
        totalInventory
        productType
        collections(first: 6) {
          nodes {
            title
            handle
          }
        }
        priceRangeV2 {
          minVariantPrice { amount currencyCode }
          maxVariantPrice { amount currencyCode }
        }
      }
    }
  }
}
GQL;

        $url = sprintf(
            'https://%s/admin/api/%s/graphql.json',
            $this->storeDomain,
            $this->apiVersion
        );

        $response = Http::request('POST', $url, [
            'X-Shopify-Access-Token' => $this->adminAccessToken,
        ], [
            'query' => $query,
            'variables' => ['first' => $limit],
        ]);

        if ($response['status'] >= 400) {
            throw new RuntimeException('Shopify respondió con error HTTP ' . $response['status']);
        }

        $json = json_decode($response['body'], true);
        if (!is_array($json)) {
            throw new RuntimeException('Respuesta inválida de Shopify');
        }

        if (isset($json['errors'])) {
            throw new RuntimeException('GraphQL error desde Shopify');
        }

        $edges = $json['data']['products']['edges'] ?? [];
        if (!is_array($edges)) {
            return [];
        }

        $products = [];
        foreach ($edges as $edge) {
            if (!isset($edge['node']) || !is_array($edge['node'])) {
                continue;
            }
            $products[] = $edge['node'];
        }

        return $products;
    }
}
