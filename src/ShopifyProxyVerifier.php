<?php

declare(strict_types=1);

namespace IA;

final class ShopifyProxyVerifier
{
    /**
     * @param array<string, string> $query
     */
    public static function isValid(array $query, string $sharedSecret): bool
    {
        if ($sharedSecret === '' || !isset($query['signature'])) {
            return false;
        }

        $signature = $query['signature'];
        unset($query['signature']);
        ksort($query);

        $pairs = [];
        foreach ($query as $k => $v) {
            $pairs[] = sprintf('%s=%s', $k, str_replace('%2F', '/', rawurlencode($v)));
        }

        $data = implode('', $pairs);
        $calculated = hash_hmac('sha256', $data, $sharedSecret);

        return hash_equals($calculated, $signature);
    }
}
