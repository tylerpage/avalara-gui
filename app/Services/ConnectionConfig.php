<?php

namespace App\Services;

use App\Models\IntegrationSetting;

class ConnectionConfig
{
    public const SHOPWARE_URL = 'shopware_url';

    public const SHOPWARE_CLIENT_ID = 'shopware_client_id';

    public const SHOPWARE_CLIENT_SECRET = 'shopware_client_secret';

    public const AVALARA_ACCOUNT_NUMBER = 'avalara_account_number';

    public const AVALARA_LICENSE_KEY = 'avalara_license_key';

    public const AVALARA_COMPANY_CODE = 'avalara_company_code';

    public const AVALARA_IS_LIVE = 'avalara_is_live';

    public const AUTHNET_API_LOGIN_ID = 'authnet_api_login_id';

    public const AUTHNET_TRANSACTION_KEY = 'authnet_transaction_key';

    public const AUTHNET_IS_LIVE = 'authnet_is_live';

    public static function shopwareUrl(): ?string
    {
        return self::trimOrNull(IntegrationSetting::get(self::SHOPWARE_URL));
    }

    public static function shopwareClientId(): ?string
    {
        return self::trimOrNull(IntegrationSetting::get(self::SHOPWARE_CLIENT_ID));
    }

    public static function shopwareClientSecret(): ?string
    {
        return self::trimOrNull(IntegrationSetting::get(self::SHOPWARE_CLIENT_SECRET));
    }

    public static function avalaraAccountNumber(): ?string
    {
        return self::trimOrNull(IntegrationSetting::get(self::AVALARA_ACCOUNT_NUMBER));
    }

    public static function avalaraLicenseKey(): ?string
    {
        return self::trimOrNull(IntegrationSetting::get(self::AVALARA_LICENSE_KEY));
    }

    public static function avalaraCompanyCode(): ?string
    {
        return self::trimOrNull(IntegrationSetting::get(self::AVALARA_COMPANY_CODE));
    }

    public static function avalaraIsLive(): bool
    {
        return filter_var(IntegrationSetting::get(self::AVALARA_IS_LIVE, false), FILTER_VALIDATE_BOOL);
    }

    public static function authnetApiLoginId(): ?string
    {
        return self::trimOrNull(IntegrationSetting::get(self::AUTHNET_API_LOGIN_ID));
    }

    public static function authnetTransactionKey(): ?string
    {
        return self::trimOrNull(IntegrationSetting::get(self::AUTHNET_TRANSACTION_KEY));
    }

    public static function authnetIsLive(): bool
    {
        return filter_var(IntegrationSetting::get(self::AUTHNET_IS_LIVE, false), FILTER_VALIDATE_BOOL);
    }

    public static function isShopwareConfigured(): bool
    {
        return filled(self::shopwareUrl())
            && filled(self::shopwareClientId())
            && filled(self::shopwareClientSecret());
    }

    public static function isAvalaraConfigured(): bool
    {
        return filled(self::avalaraAccountNumber())
            && filled(self::avalaraLicenseKey())
            && filled(self::avalaraCompanyCode());
    }

    public static function isAuthnetConfigured(): bool
    {
        return filled(self::authnetApiLoginId())
            && filled(self::authnetTransactionKey());
    }

    public static function avalaraBaseUrl(): string
    {
        return self::avalaraIsLive()
            ? 'https://rest.avatax.com'
            : 'https://sandbox-rest.avatax.com';
    }

    public static function authnetBaseUrl(): string
    {
        return self::authnetIsLive()
            ? 'https://api.authorize.net/xml/v1/request.api'
            : 'https://apitest.authorize.net/xml/v1/request.api';
    }

    private static function trimOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
