<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AvalaraApiService
{
    public function isConfigured(): bool
    {
        return ConnectionConfig::isAvalaraConfigured();
    }

    /**
     * @return array<string, mixed>
     */
    public function testConnection(): array
    {
        $pingResponse = $this->client()->get('/api/v2/utilities/ping');

        if (! $pingResponse->successful()) {
            throw new RuntimeException('Avalara connection failed: '.$pingResponse->body());
        }

        $ping = $pingResponse->json() ?? [];

        if (($ping['authenticated'] ?? false) !== true) {
            throw new RuntimeException('Avalara credentials were rejected.');
        }

        $companyCode = ConnectionConfig::avalaraCompanyCode();

        if (! filled($companyCode)) {
            return [
                'authenticated' => true,
                'version' => $ping['version'] ?? null,
            ];
        }

        $company = $this->findCompanyByCode($companyCode);

        if ($company === null) {
            throw new RuntimeException(sprintf(
                'Avalara authenticated, but company code "%s" was not found in this account.',
                $companyCode,
            ));
        }

        return [
            'authenticated' => true,
            'version' => $ping['version'] ?? null,
            'companyCode' => $company['companyCode'] ?? $companyCode,
            'name' => $company['name'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getTransaction(string $documentCode, ?string $companyCode = null): ?array
    {
        $result = $this->lookupTransaction($documentCode, $companyCode);

        if ($result['error'] !== null) {
            throw new RuntimeException($result['error']);
        }

        return $result['transaction'];
    }

    /**
     * @param  array<string, mixed>  $returnEntry
     * @return array{refund: ?string, verify: ?string}
     */
    public function resolveReturnDocumentCodes(array $returnEntry, string $orderNumber): array
    {
        $returnNumber = $returnEntry['shopware']['returnNumber'] ?? null;
        $avalara = $returnEntry['avalara'] ?? [];

        if (! filled($returnNumber) || ! filled($orderNumber)) {
            return ['refund' => null, 'verify' => null];
        }

        $refundCode = $avalara['refund']['transactionCode'] ?? null;
        if (! filled($refundCode)) {
            $refundCode = $orderNumber.'-'.$returnNumber;
        }

        $verifyCode = $avalara['verification']['transactionCode'] ?? null;
        if (! filled($verifyCode)) {
            $verifyCode = $orderNumber.'-return-verify-'.$returnNumber;
        }

        return [
            'refund' => $refundCode,
            'verify' => $verifyCode,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $returnsAvalara
     * @return list<array<string, mixed>>
     */
    public function enrichReturnsWithLiveAvalara(
        array $returnsAvalara,
        string $orderNumber,
        ?string $fallbackCompanyCode = null,
    ): array {
        if (! $this->isConfigured()) {
            return $returnsAvalara;
        }

        return array_map(function (array $entry) use ($orderNumber, $fallbackCompanyCode): array {
            $companyCode = $entry['avalara']['companyCode'] ?? $fallbackCompanyCode;
            $codes = $this->resolveReturnDocumentCodes($entry, $orderNumber);

            $entry['liveAvalara'] = [
                'refund' => $codes['refund'] !== null
                    ? $this->lookupTransaction($codes['refund'], $companyCode)
                    : $this->emptyLookup(null),
                'verify' => $codes['verify'] !== null
                    ? $this->lookupTransaction($codes['verify'], $companyCode)
                    : $this->emptyLookup(null),
            ];

            return $entry;
        }, $returnsAvalara);
    }

    /**
     * @return array{
     *     documentCode: ?string,
     *     transaction: ?array<string, mixed>,
     *     notFound: bool,
     *     error: ?string
     * }
     */
    public function lookupTransaction(string $documentCode, ?string $companyCode = null): array
    {
        $companyCode ??= ConnectionConfig::avalaraCompanyCode();

        if (! filled($companyCode)) {
            return $this->emptyLookup($documentCode, 'Avalara company code is not configured.');
        }

        try {
            $response = $this->client()->get(sprintf(
                '/api/v2/companies/%s/transactions/%s',
                rawurlencode($companyCode),
                rawurlencode($documentCode),
            ), [
                '$include' => 'Lines,Details,Addresses',
            ]);
        } catch (RuntimeException $e) {
            return $this->emptyLookup($documentCode, $e->getMessage());
        }

        if ($response->status() === 404) {
            return [
                'documentCode' => $documentCode,
                'transaction' => null,
                'notFound' => true,
                'error' => null,
            ];
        }

        if (! $response->successful()) {
            return $this->emptyLookup(
                $documentCode,
                'Failed to load Avalara transaction: '.$response->body(),
            );
        }

        return [
            'documentCode' => $documentCode,
            'transaction' => $this->formatTransaction($response->json() ?? []),
            'notFound' => false,
            'error' => null,
        ];
    }

    /**
     * @return array{
     *     documentCode: ?string,
     *     transaction: null,
     *     notFound: bool,
     *     error: ?string
     * }
     */
    private function emptyLookup(?string $documentCode, ?string $error = null): array
    {
        return [
            'documentCode' => $documentCode,
            'transaction' => null,
            'notFound' => $error === null && $documentCode !== null,
            'error' => $error,
        ];
    }

    /**
     * @param  array<string, mixed>  $transaction
     * @return array<string, mixed>
     */
    private function formatTransaction(array $transaction): array
    {
        $lines = collect($transaction['lines'] ?? [])->map(function (array $line): array {
            return [
                'lineNumber' => $line['lineNumber'] ?? null,
                'itemCode' => $line['itemCode'] ?? null,
                'description' => $line['description'] ?? null,
                'quantity' => $line['quantity'] ?? null,
                'lineAmount' => $line['lineAmount'] ?? null,
                'tax' => $line['tax'] ?? null,
                'taxCalculated' => $line['taxCalculated'] ?? null,
                'taxCode' => $line['taxCode'] ?? null,
                'details' => collect($line['details'] ?? [])->map(fn (array $detail) => [
                    'jurisName' => $detail['jurisName'] ?? null,
                    'rate' => $detail['rate'] ?? null,
                    'tax' => $detail['tax'] ?? null,
                    'taxName' => $detail['taxName'] ?? null,
                ])->values()->all(),
            ];
        })->values()->all();

        return [
            'id' => $transaction['id'] ?? null,
            'code' => $transaction['code'] ?? null,
            'type' => $transaction['type'] ?? null,
            'status' => $transaction['status'] ?? null,
            'companyCode' => $transaction['companyCode'] ?? null,
            'date' => $transaction['date'] ?? null,
            'totalAmount' => $transaction['totalAmount'] ?? null,
            'totalTax' => $transaction['totalTax'] ?? null,
            'totalTaxCalculated' => $transaction['totalTaxCalculated'] ?? null,
            'currencyCode' => $transaction['currencyCode'] ?? null,
            'lines' => $lines,
        ];
    }

    private function client()
    {
        $accountNumber = ConnectionConfig::avalaraAccountNumber();
        $licenseKey = ConnectionConfig::avalaraLicenseKey();

        if (! filled($accountNumber) || ! filled($licenseKey)) {
            throw new RuntimeException('Avalara credentials are not configured.');
        }

        return Http::baseUrl(ConnectionConfig::avalaraBaseUrl())
            ->acceptJson()
            ->withBasicAuth($accountNumber, $licenseKey);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findCompanyByCode(string $companyCode): ?array
    {
        $response = $this->client()->get('/api/v2/companies', [
            '$filter' => "companyCode eq '".str_replace("'", "''", $companyCode)."'",
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to verify Avalara company code: '.$response->body());
        }

        $companies = $response->json('value') ?? [];

        if (! is_array($companies)) {
            return null;
        }

        foreach ($companies as $company) {
            if (! is_array($company)) {
                continue;
            }

            if (($company['companyCode'] ?? null) === $companyCode) {
                return $company;
            }
        }

        return null;
    }
}
