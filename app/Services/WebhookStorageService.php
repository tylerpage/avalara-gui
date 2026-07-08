<?php

namespace App\Services;

use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class WebhookStorageService
{
    public function __construct(
        private readonly ShopwareWebhookParser $parser,
        private readonly ShopwareAdminApiService $shopware,
    ) {}

    public function store(Request $request): WebhookEvent
    {
        $payload = $request->json()->all();

        if ($payload === []) {
            $payload = $request->all();
        }

        $parsed = $this->parser->parse($payload, $this->relevantHeaders($request));
        $parsed = $this->resolveOrderContext($parsed);

        return WebhookEvent::create([
            ...$parsed,
            'received_at' => Carbon::now(),
        ]);
    }

    /**
     * @return array{total: int, return_related: int}
     */
    public function counts(): array
    {
        return [
            'total' => WebhookEvent::query()->count(),
            'return_related' => WebhookEvent::query()->returnRelated()->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array<string, mixed>
     */
    private function resolveOrderContext(array $parsed): array
    {
        if (! $this->shopware->isConfigured()) {
            return $parsed;
        }

        try {
            if ($parsed['shopware_return_id'] && ! $parsed['shopware_order_id']) {
                $return = $this->shopware->getOrderReturn($parsed['shopware_return_id']);

                if ($return !== null) {
                    $parsed['shopware_order_id'] = $return['orderId'] ?? $parsed['shopware_order_id'];
                    $parsed['shopware_order_number'] = $return['orderNumber'] ?? $parsed['shopware_order_number'];
                }
            }

            if ($parsed['shopware_order_id'] && ! $parsed['shopware_order_number']) {
                $order = $this->shopware->getOrder($parsed['shopware_order_id']);
                $parsed['shopware_order_number'] = $order['orderNumber'] ?? $parsed['shopware_order_number'];
            }
        } catch (\RuntimeException) {
            // Keep stored webhook even if Shopware lookup fails.
        }

        return $parsed;
    }

    /**
     * @return array<string, mixed>
     */
    private function relevantHeaders(Request $request): array
    {
        $headers = [];

        foreach ($request->headers->all() as $name => $values) {
            $normalized = strtolower($name);

            if (in_array($normalized, ['host', 'content-type', 'user-agent', 'shopware-app-signature', 'sw-version'], true)) {
                $headers[$normalized] = is_array($values) ? implode(', ', $values) : $values;
            }
        }

        return $headers;
    }
}
