<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AuthorizeNetApiService
{
    public function isConfigured(): bool
    {
        return ConnectionConfig::isAuthnetConfigured();
    }

    public function testConnection(): array
    {
        $response = $this->client()->post('', [
            'authenticateTestRequest' => [
                'merchantAuthentication' => $this->merchantAuthentication(),
            ],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Authorize.net connection failed: '.$response->body());
        }

        $payload = $this->decodeResponse($response);

        if (! $this->isSuccessfulResult($payload)) {
            throw new RuntimeException('Authorize.net authentication failed: '.$this->resultMessage($payload, $response->body()));
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getTransactionDetails(string $transId): ?array
    {
        $response = $this->client()->post('', [
            'getTransactionDetailsRequest' => [
                'merchantAuthentication' => $this->merchantAuthentication(),
                'transId' => $transId,
            ],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to load Authorize.net transaction: '.$response->body());
        }

        $payload = $this->decodeResponse($response);

        if (! $this->isSuccessfulResult($payload)) {
            $message = $this->resultMessage($payload, 'Unknown error');
            if (str_contains(strtolower($message), 'not found')) {
                return null;
            }

            throw new RuntimeException('Authorize.net transaction lookup failed: '.$message);
        }

        return $this->formatTransaction($payload['transaction'] ?? []);
    }

    /**
     * @param  array<string, mixed>  $transaction
     * @return array<string, mixed>
     */
    private function formatTransaction(array $transaction): array
    {
        $rawLineItems = $transaction['lineItems']['lineItem'] ?? [];
        if (isset($rawLineItems['itemId']) || isset($rawLineItems['name'])) {
            $rawLineItems = [$rawLineItems];
        }

        $lineItems = collect(is_array($rawLineItems) ? $rawLineItems : [])->map(function (mixed $line): array {
            if (! is_array($line)) {
                return [];
            }

            return [
                'itemId' => $line['itemId'] ?? null,
                'name' => $line['name'] ?? null,
                'description' => $line['description'] ?? null,
                'quantity' => $line['quantity'] ?? null,
                'unitPrice' => $line['unitPrice'] ?? null,
                'taxable' => $line['taxable'] ?? null,
            ];
        })->filter(fn (array $line) => $line !== [])->values()->all();

        $rawRefunds = $transaction['refunds'] ?? [];
        if (isset($rawRefunds['refundId'])) {
            $rawRefunds = [$rawRefunds];
        }

        $refunds = collect(is_array($rawRefunds) ? $rawRefunds : [])->map(function (mixed $refund): array {
            if (! is_array($refund)) {
                return [];
            }

            return [
                'refundId' => $refund['refundId'] ?? null,
                'refundAmount' => $refund['refundAmount'] ?? null,
                'refundDate' => $refund['refundDate'] ?? null,
            ];
        })->filter(fn (array $refund) => $refund !== [])->values()->all();

        return [
            'transId' => $transaction['transId'] ?? null,
            'transactionType' => $transaction['transactionType'] ?? null,
            'transactionStatus' => $transaction['transactionStatus'] ?? null,
            'submitTimeUTC' => $transaction['submitTimeUTC'] ?? null,
            'submitTimeLocal' => $transaction['submitTimeLocal'] ?? null,
            'settleAmount' => $transaction['settleAmount'] ?? null,
            'authAmount' => $transaction['authAmount'] ?? null,
            'order' => [
                'invoiceNumber' => $transaction['order']['invoiceNumber'] ?? null,
                'description' => $transaction['order']['description'] ?? null,
            ],
            'payment' => [
                'creditCard' => [
                    'cardNumber' => $transaction['payment']['creditCard']['cardNumber'] ?? null,
                    'cardType' => $transaction['payment']['creditCard']['cardType'] ?? null,
                ],
            ],
            'lineItems' => $lineItems,
            'refunds' => $refunds,
        ];
    }

    /**
     * @return array{name: string, transactionKey: string}
     */
    private function merchantAuthentication(): array
    {
        return [
            'name' => (string) ConnectionConfig::authnetApiLoginId(),
            'transactionKey' => (string) ConnectionConfig::authnetTransactionKey(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(\Illuminate\Http\Client\Response $response): array
    {
        $body = ltrim($response->body(), "\xEF\xBB\xBF");

        $payload = json_decode($body, true);

        return is_array($payload) ? $payload : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isSuccessfulResult(array $payload): bool
    {
        return ($payload['messages']['resultCode'] ?? null) === 'Ok';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resultMessage(array $payload, string $fallback): string
    {
        $message = $payload['messages']['message'] ?? null;

        if (is_array($message) && isset($message['text'])) {
            return (string) $message['text'];
        }

        if (is_array($message) && isset($message[0]['text'])) {
            return (string) $message[0]['text'];
        }

        return $fallback;
    }

    private function client()
    {
        $apiLoginId = ConnectionConfig::authnetApiLoginId();
        $transactionKey = ConnectionConfig::authnetTransactionKey();

        if (! filled($apiLoginId) || ! filled($transactionKey)) {
            throw new RuntimeException('Authorize.net credentials are not configured.');
        }

        return Http::baseUrl(ConnectionConfig::authnetBaseUrl())
            ->acceptJson()
            ->asJson();
    }
}
