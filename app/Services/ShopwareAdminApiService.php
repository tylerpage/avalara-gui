<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ShopwareAdminApiService
{
    private const TOKEN_CACHE_KEY = 'shopware_admin_access_token';

    public function isConfigured(): bool
    {
        return ConnectionConfig::isShopwareConfigured();
    }

    public function testConnection(): array
    {
        $token = $this->getAccessToken();

        $response = $this->client($token)->get('/api/_info/version');

        if (! $response->successful()) {
            throw new RuntimeException('Shopware connection failed: '.$response->body());
        }

        return $response->json() ?? [];
    }

    /**
     * @return array{total: int, data: list<array<string, mixed>>}
     */
    public function searchOrders(?string $orderNumber = null, int $page = 1, int $limit = 25): array
    {
        $body = [
            'limit' => max(1, min($limit, 100)),
            'page' => max(1, $page),
            'sort' => [
                ['field' => 'orderDateTime', 'order' => 'DESC'],
            ],
            'associations' => [
                'currency' => [],
                'orderCustomer' => [],
                'stateMachineState' => [],
            ],
        ];

        if ($orderNumber !== null && $orderNumber !== '') {
            $body['filter'] = [
                [
                    'type' => 'contains',
                    'field' => 'orderNumber',
                    'value' => $orderNumber,
                ],
            ];
        }

        $response = $this->client()->post('/api/search/order', $body);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to search orders: '.$response->body());
        }

        $payload = $response->json();
        $orders = collect($payload['data'] ?? [])->map(fn (array $order) => $this->summarizeOrder($order))->values()->all();

        return [
            'total' => (int) ($payload['total'] ?? count($orders)),
            'data' => $orders,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getOrder(string $orderId): array
    {
        $response = $this->client()->post('/api/search/order', [
            'limit' => 1,
            'filter' => [
                [
                    'type' => 'equals',
                    'field' => 'id',
                    'value' => $orderId,
                ],
            ],
            'associations' => [
                'lineItems' => [],
                'deliveries' => [
                    'associations' => [
                        'shippingOrderAddress' => [
                            'associations' => [
                                'country' => [],
                            ],
                        ],
                        'shippingMethod' => [],
                    ],
                ],
                'transactions' => [
                    'associations' => [
                        'paymentMethod' => [],
                    ],
                ],
                'currency' => [],
                'orderCustomer' => [],
                'stateMachineState' => [],
                'returns' => [
                    'associations' => [
                        'lineItems' => [
                            'associations' => [
                                'lineItem' => [],
                            ],
                        ],
                        'state' => [],
                    ],
                ],
            ],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to load order: '.$response->body());
        }

        $order = $response->json('data.0');

        if (! is_array($order)) {
            throw new RuntimeException('Order not found.');
        }

        return $this->formatOrderDetail($order);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getReturnsAvalaraForOrder(string $orderId): array
    {
        $response = $this->client()->get('/api/_action/returns-avalara/order/'.$orderId);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to load Avalara return data: '.$response->body());
        }

        return $response->json()['data'] ?? [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getReturnsAuthnetForOrder(string $orderId): array
    {
        $response = $this->client()->get('/api/_action/returns-authnet/order/'.$orderId);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to load Authorize.net return data: '.$response->body());
        }

        return $response->json()['data'] ?? [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRemainingRefundable(string $orderId): ?array
    {
        $response = $this->client()->get('/api/_action/returns-advanced/order/'.$orderId.'/remaining-refundable');

        if (! $response->successful()) {
            return null;
        }

        return $response->json();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getOrderReturn(string $returnId): ?array
    {
        $response = $this->client()->post('/api/search/order-return', [
            'limit' => 1,
            'filter' => [
                [
                    'type' => 'equals',
                    'field' => 'id',
                    'value' => $returnId,
                ],
            ],
            'associations' => [
                'lineItems' => [
                    'associations' => [
                        'lineItem' => [],
                    ],
                ],
                'state' => [],
                'order' => [],
            ],
        ]);

        if (! $response->successful()) {
            return null;
        }

        $return = $response->json('data.0');

        if (! is_array($return)) {
            return null;
        }

        return $this->formatOrderReturn($return);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function searchReturnsAvalara(?string $orderNumber = null, ?string $returnNumber = null, int $limit = 50, int $offset = 0): ?array
    {
        $response = $this->client()->get('/api/_action/returns-avalara/search', array_filter([
            'orderNumber' => $orderNumber,
            'returnNumber' => $returnNumber,
            'limit' => $limit,
            'offset' => $offset,
        ], fn ($value) => $value !== null && $value !== ''));

        if (! $response->successful()) {
            return null;
        }

        return $response->json();
    }

    private function client(?string $token = null): PendingRequest
    {
        $baseUrl = rtrim((string) ConnectionConfig::shopwareUrl(), '/');

        if ($baseUrl === '') {
            throw new RuntimeException('Shopware URL is not configured.');
        }

        return Http::baseUrl($baseUrl)
            ->acceptJson()
            ->asJson()
            ->withToken($token ?? $this->getAccessToken());
    }

    private function getAccessToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, now()->addMinutes(9), function (): string {
            $baseUrl = rtrim((string) ConnectionConfig::shopwareUrl(), '/');
            $clientId = ConnectionConfig::shopwareClientId();
            $clientSecret = ConnectionConfig::shopwareClientSecret();

            if ($baseUrl === '' || ! filled($clientId) || ! filled($clientSecret)) {
                throw new RuntimeException('Shopware credentials are not configured.');
            }

            $response = Http::baseUrl($baseUrl)
                ->asForm()
                ->withBasicAuth($clientId, $clientSecret)
                ->post('/api/oauth/token', [
                    'grant_type' => 'client_credentials',
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('Shopware OAuth failed: '.$response->body());
            }

            $token = $response->json('access_token');

            if (! is_string($token) || $token === '') {
                throw new RuntimeException('Shopware OAuth response did not include an access token.');
            }

            return $token;
        });
    }

    /**
     * @param  array<string, mixed>  $order
     * @return array<string, mixed>
     */
    private function summarizeOrder(array $order): array
    {
        $attributes = $order['attributes'] ?? $order;

        return [
            'id' => $order['id'] ?? $attributes['id'] ?? null,
            'orderNumber' => $attributes['orderNumber'] ?? null,
            'orderDateTime' => $attributes['orderDateTime'] ?? null,
            'amountTotal' => $attributes['amountTotal'] ?? null,
            'state' => $order['stateMachineState']['attributes']['technicalName']
                ?? $order['stateMachineState']['technicalName']
                ?? null,
            'customer' => trim(implode(' ', array_filter([
                $order['orderCustomer']['attributes']['firstName'] ?? $order['orderCustomer']['firstName'] ?? null,
                $order['orderCustomer']['attributes']['lastName'] ?? $order['orderCustomer']['lastName'] ?? null,
            ]))),
            'currency' => $order['currency']['attributes']['isoCode'] ?? $order['currency']['isoCode'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $return
     * @return array<string, mixed>
     */
    private function formatOrderReturn(array $return): array
    {
        $attributes = $return['attributes'] ?? $return;
        $order = $return['order'] ?? [];
        $orderAttributes = $order['attributes'] ?? $order;

        return [
            'id' => $return['id'] ?? $attributes['id'] ?? null,
            'returnNumber' => $attributes['returnNumber'] ?? null,
            'requestedAt' => $attributes['requestedAt'] ?? null,
            'orderId' => $order['id'] ?? $orderAttributes['id'] ?? $attributes['orderId'] ?? null,
            'orderNumber' => $orderAttributes['orderNumber'] ?? null,
            'state' => $return['state']['attributes']['technicalName'] ?? $return['state']['technicalName'] ?? null,
            'lineItems' => collect($return['lineItems'] ?? [])->map(function (array $returnLine): array {
                $attrs = $returnLine['attributes'] ?? $returnLine;
                $orderLine = $returnLine['lineItem'] ?? [];
                $orderLineAttrs = $orderLine['attributes'] ?? $orderLine;
                $price = $attrs['price'] ?? $returnLine['price'] ?? [];

                return [
                    'id' => $returnLine['id'] ?? $attrs['id'] ?? null,
                    'quantity' => $attrs['quantity'] ?? null,
                    'label' => $orderLineAttrs['label'] ?? null,
                    'productNumber' => $orderLineAttrs['payload']['productNumber'] ?? null,
                    'unitPrice' => $price['unitPrice'] ?? null,
                    'totalPrice' => $price['totalPrice'] ?? null,
                ];
            })->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $order
     * @return array<string, mixed>
     */
    private function formatOrderDetail(array $order): array
    {
        $attributes = $order['attributes'] ?? $order;
        $lineItems = collect($order['lineItems'] ?? [])->map(function (array $lineItem): array {
            $attrs = $lineItem['attributes'] ?? $lineItem;
            $price = $attrs['price'] ?? $lineItem['price'] ?? [];
            $taxes = collect($price['calculatedTaxes'] ?? [])->map(fn (array $tax) => [
                'taxRate' => $tax['taxRate'] ?? null,
                'tax' => $tax['tax'] ?? null,
            ])->values()->all();

            return [
                'id' => $lineItem['id'] ?? $attrs['id'] ?? null,
                'type' => $attrs['type'] ?? null,
                'label' => $attrs['label'] ?? null,
                'quantity' => $attrs['quantity'] ?? null,
                'unitPrice' => $price['unitPrice'] ?? null,
                'totalPrice' => $price['totalPrice'] ?? null,
                'productNumber' => $attrs['payload']['productNumber'] ?? null,
                'taxes' => $taxes,
                'taxTotal' => collect($taxes)->sum('tax'),
            ];
        })->values()->all();

        $returns = collect($order['returns'] ?? [])->map(function (array $return): array {
            $attrs = $return['attributes'] ?? $return;

            return [
                'id' => $return['id'] ?? $attrs['id'] ?? null,
                'returnNumber' => $attrs['returnNumber'] ?? null,
                'requestedAt' => $attrs['requestedAt'] ?? null,
                'state' => $return['state']['attributes']['technicalName'] ?? $return['state']['technicalName'] ?? null,
                'lineItems' => collect($return['lineItems'] ?? [])->map(function (array $returnLine): array {
                    $attrs = $returnLine['attributes'] ?? $returnLine;
                    $orderLine = $returnLine['lineItem'] ?? [];
                    $orderLineAttrs = $orderLine['attributes'] ?? $orderLine;
                    $price = $attrs['price'] ?? $returnLine['price'] ?? [];

                    return [
                        'id' => $returnLine['id'] ?? $attrs['id'] ?? null,
                        'quantity' => $attrs['quantity'] ?? null,
                        'label' => $orderLineAttrs['label'] ?? null,
                        'productNumber' => $orderLineAttrs['payload']['productNumber'] ?? null,
                        'unitPrice' => $price['unitPrice'] ?? null,
                        'totalPrice' => $price['totalPrice'] ?? null,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        $transactions = collect($order['transactions'] ?? [])->map(function (array $transaction): array {
            $attrs = $transaction['attributes'] ?? $transaction;
            $customFields = $attrs['customFields'] ?? $transaction['customFields'] ?? [];
            $authnet = is_array($customFields['paradoxlabs_authnet'] ?? null)
                ? $customFields['paradoxlabs_authnet']
                : [];

            return [
                'id' => $transaction['id'] ?? $attrs['id'] ?? null,
                'amount' => $attrs['amount']['totalPrice'] ?? $transaction['amount']['totalPrice'] ?? null,
                'state' => $transaction['stateMachineState']['attributes']['technicalName']
                    ?? $transaction['stateMachineState']['technicalName']
                    ?? null,
                'paymentMethod' => $transaction['paymentMethod']['attributes']['name']
                    ?? $transaction['paymentMethod']['name']
                    ?? null,
                'handlerIdentifier' => $transaction['paymentMethod']['attributes']['handlerIdentifier']
                    ?? $transaction['paymentMethod']['handlerIdentifier']
                    ?? null,
                'authnetTransId' => $authnet['transaction']['transId'] ?? null,
                'authnetRefunds' => collect($authnet['refunds'] ?? [])->map(function (mixed $refund): array {
                    if (! is_array($refund)) {
                        return [];
                    }

                    return [
                        'transId' => $refund['transactionResponse']['transId'] ?? null,
                        'responseCode' => $refund['transactionResponse']['responseCode'] ?? null,
                    ];
                })->filter(fn (array $refund) => $refund !== [])->values()->all(),
            ];
        })->values()->all();

        return [
            'id' => $order['id'] ?? $attributes['id'] ?? null,
            'orderNumber' => $attributes['orderNumber'] ?? null,
            'orderDateTime' => $attributes['orderDateTime'] ?? null,
            'amountTotal' => $attributes['amountTotal'] ?? null,
            'amountNet' => $attributes['amountNet'] ?? null,
            'shippingTotal' => $attributes['shippingTotal'] ?? null,
            'taxStatus' => $attributes['taxStatus'] ?? null,
            'state' => $order['stateMachineState']['attributes']['technicalName']
                ?? $order['stateMachineState']['technicalName']
                ?? null,
            'salesChannelId' => $attributes['salesChannelId'] ?? null,
            'customer' => [
                'email' => $order['orderCustomer']['attributes']['email'] ?? $order['orderCustomer']['email'] ?? null,
                'name' => trim(implode(' ', array_filter([
                    $order['orderCustomer']['attributes']['firstName'] ?? $order['orderCustomer']['firstName'] ?? null,
                    $order['orderCustomer']['attributes']['lastName'] ?? $order['orderCustomer']['lastName'] ?? null,
                ]))),
            ],
            'currency' => $order['currency']['attributes']['isoCode'] ?? $order['currency']['isoCode'] ?? null,
            'lineItems' => $lineItems,
            'returns' => $returns,
            'transactions' => $transactions,
            'authnetTransId' => collect($transactions)->pluck('authnetTransId')->filter()->first(),
        ];
    }
}
