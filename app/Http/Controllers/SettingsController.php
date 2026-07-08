<?php

namespace App\Http\Controllers;

use App\Models\IntegrationSetting;
use App\Services\AuthorizeNetApiService;
use App\Services\AvalaraApiService;
use App\Services\ConnectionConfig;
use App\Services\ShopwareAdminApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class SettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Settings/Edit', [
            'settings' => [
                'shopware_url' => ConnectionConfig::shopwareUrl() ?? '',
                'shopware_client_id' => ConnectionConfig::shopwareClientId() ?? '',
                'has_shopware_client_secret' => filled(ConnectionConfig::shopwareClientSecret()),
                'avalara_account_number' => ConnectionConfig::avalaraAccountNumber() ?? '',
                'has_avalara_license_key' => filled(ConnectionConfig::avalaraLicenseKey()),
                'avalara_company_code' => ConnectionConfig::avalaraCompanyCode() ?? '',
                'avalara_is_live' => ConnectionConfig::avalaraIsLive(),
                'authnet_api_login_id' => ConnectionConfig::authnetApiLoginId() ?? '',
                'has_authnet_transaction_key' => filled(ConnectionConfig::authnetTransactionKey()),
                'authnet_is_live' => ConnectionConfig::authnetIsLive(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shopware_url' => ['required', 'url', 'max:255'],
            'shopware_client_id' => ['required', 'string', 'max:255'],
            'shopware_client_secret' => ['nullable', 'string'],
            'avalara_account_number' => ['nullable', 'string', 'max:255'],
            'avalara_license_key' => ['nullable', 'string'],
            'avalara_company_code' => ['nullable', 'string', 'max:255'],
            'avalara_is_live' => ['boolean'],
            'authnet_api_login_id' => ['nullable', 'string', 'max:255'],
            'authnet_transaction_key' => ['nullable', 'string'],
            'authnet_is_live' => ['boolean'],
        ]);

        if (! filled(ConnectionConfig::shopwareClientSecret()) && blank($validated['shopware_client_secret'] ?? null)) {
            return back()->withErrors([
                'shopware_client_secret' => 'Client secret is required on first save.',
            ]);
        }

        IntegrationSetting::set(ConnectionConfig::SHOPWARE_URL, rtrim($validated['shopware_url'], '/'));

        IntegrationSetting::set(ConnectionConfig::SHOPWARE_CLIENT_ID, $validated['shopware_client_id']);

        if ($validated['shopware_client_secret'] ?? null) {
            IntegrationSetting::set(ConnectionConfig::SHOPWARE_CLIENT_SECRET, $validated['shopware_client_secret']);
        }

        IntegrationSetting::set(ConnectionConfig::AVALARA_ACCOUNT_NUMBER, $validated['avalara_account_number'] ?? '');

        if ($validated['avalara_license_key'] ?? null) {
            IntegrationSetting::set(ConnectionConfig::AVALARA_LICENSE_KEY, $validated['avalara_license_key']);
        }

        IntegrationSetting::set(ConnectionConfig::AVALARA_COMPANY_CODE, $validated['avalara_company_code'] ?? '');
        IntegrationSetting::set(ConnectionConfig::AVALARA_IS_LIVE, $validated['avalara_is_live'] ? '1' : '0');

        IntegrationSetting::set(ConnectionConfig::AUTHNET_API_LOGIN_ID, $validated['authnet_api_login_id'] ?? '');

        if ($validated['authnet_transaction_key'] ?? null) {
            IntegrationSetting::set(ConnectionConfig::AUTHNET_TRANSACTION_KEY, $validated['authnet_transaction_key']);
        }

        IntegrationSetting::set(ConnectionConfig::AUTHNET_IS_LIVE, $validated['authnet_is_live'] ? '1' : '0');

        Cache::forget('shopware_admin_access_token');

        return back()->with('success', 'Connection settings saved.');
    }

    public function testShopware(ShopwareAdminApiService $shopware): RedirectResponse
    {
        try {
            $version = $shopware->testConnection();

            return back()->with('success', 'Shopware connected. Version: '.($version['version'] ?? 'unknown'));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function testAvalara(AvalaraApiService $avalara): RedirectResponse
    {
        try {
            $result = $avalara->testConnection();

            $message = filled($result['name'] ?? null)
                ? 'Avalara connected. Company: '.$result['name'].' ('.$result['companyCode'].')'
                : 'Avalara connected. API version: '.($result['version'] ?? 'unknown');

            return back()->with('success', $message);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function testAuthnet(AuthorizeNetApiService $authnet): RedirectResponse
    {
        try {
            $authnet->testConnection();

            return back()->with('success', 'Authorize.net connected successfully.');
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
