<?php

namespace App\Services;

class ShopwareWebhookParser
{
    private const RETURN_ENTITIES = [
        'order_return',
        'order_return_line_item',
    ];

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     * @return array{
     *     event_name: string,
     *     shopware_event_id: ?string,
     *     source_url: ?string,
     *     shopware_shop_id: ?string,
     *     is_return_related: bool,
     *     shopware_return_id: ?string,
     *     shopware_order_id: ?string,
     *     payload: array<string, mixed>,
     *     headers: array<string, mixed>
     * }
     */
    public function parse(array $payload, array $headers): array
    {
        $eventName = (string) ($payload['data']['event'] ?? $payload['event'] ?? 'unknown');
        $source = is_array($payload['source'] ?? null) ? $payload['source'] : [];

        $returnId = null;
        $orderId = null;

        foreach ($this->payloadItems($payload) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $entity = (string) ($item['entity'] ?? '');
            $primaryKey = $item['primaryKey'] ?? null;

            if (! is_string($primaryKey) || $primaryKey === '') {
                continue;
            }

            if ($entity === 'order_return') {
                $returnId = $primaryKey;
            }

            if ($entity === 'order') {
                $orderId = $primaryKey;
            }
        }

        return [
            'event_name' => $eventName,
            'shopware_event_id' => is_string($source['eventId'] ?? null) ? $source['eventId'] : null,
            'source_url' => is_string($source['url'] ?? null) ? $source['url'] : null,
            'shopware_shop_id' => is_string($source['shopId'] ?? null) ? $source['shopId'] : null,
            'is_return_related' => $this->isReturnRelated($eventName, $payload),
            'shopware_return_id' => $returnId,
            'shopware_order_id' => $orderId,
            'payload' => $payload,
            'headers' => $headers,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isReturnRelated(string $eventName, array $payload): bool
    {
        if (str_contains($eventName, 'order_return')) {
            return true;
        }

        foreach ($this->payloadItems($payload) as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (in_array($item['entity'] ?? '', self::RETURN_ENTITIES, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<mixed>
     */
    private function payloadItems(array $payload): array
    {
        $items = $payload['data']['payload'] ?? $payload['payload'] ?? [];

        return is_array($items) ? array_values($items) : [];
    }
}
