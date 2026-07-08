<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'connection' => [
                'shopwareConfigured' => \App\Services\ConnectionConfig::isShopwareConfigured(),
                'avalaraConfigured' => \App\Services\ConnectionConfig::isAvalaraConfigured(),
                'authnetConfigured' => \App\Services\ConnectionConfig::isAuthnetConfigured(),
            ],
            'reviewCounts' => fn () => app(\App\Services\OrderReviewService::class)->counts(),
            'webhookCounts' => fn () => app(\App\Services\WebhookStorageService::class)->counts(),
            'passcode' => [
                'required' => fn () => app(\App\Services\PasscodeService::class)->isRequired(),
            ],
        ];
    }
}
