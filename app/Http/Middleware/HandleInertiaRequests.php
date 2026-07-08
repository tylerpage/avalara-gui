<?php

namespace App\Http\Middleware;

use App\Models\Dashboard;
use App\Services\DashboardContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $dashboards = app(DashboardContext::class);

        return [
            ...parent::share($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'connection' => fn () => [
                'shopwareConfigured' => \App\Services\ConnectionConfig::isShopwareConfigured(),
                'avalaraConfigured' => \App\Services\ConnectionConfig::isAvalaraConfigured(),
                'authnetConfigured' => \App\Services\ConnectionConfig::isAuthnetConfigured(),
            ],
            'reviewCounts' => fn () => app(\App\Services\OrderReviewService::class)->counts(),
            'webhookCounts' => fn () => app(\App\Services\WebhookStorageService::class)->counts(),
            'passcode' => [
                'required' => fn () => app(\App\Services\PasscodeService::class)->isRequired(),
            ],
            'dashboard' => fn () => [
                'current' => $this->serializeDashboard($dashboards->current()),
                'all' => $dashboards->all()->map(fn (Dashboard $dashboard) => $this->serializeDashboard($dashboard))->values(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDashboard(Dashboard $dashboard): array
    {
        return [
            'id' => $dashboard->id,
            'name' => $dashboard->name,
            'slug' => $dashboard->slug,
            'webhookUrl' => $dashboard->webhookUrl(),
        ];
    }
}
